<?php

namespace App\Services\SuperAdmin;

use App\Models\StudyProgram;
use App\Models\University;
use App\Support\AuditLogSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CampusManagementService
{
    public function list(): Collection
    {
        return University::query()
            ->select([
                'id',
                'name',
                'slug',
                'code',
                'logo_path',
                'website',
                'billing_mode',
                'balance',
                'cost_per_check',
                'student_registration_fee',
                'student_fee',
                'settings',
                'is_active',
                'is_partner',
                'created_at',
                'updated_at',
            ])
            ->withCount('conversions')
            ->with(['studyPrograms:id,university_id,name,level'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (University $campus) => $this->serializeCampus($campus))
            ->values();
    }

    public function create(array $validated, Request $request): array
    {
        $university = University::create([
            'name' => $validated['name'],
            'slug' => $this->buildUniqueSlug($validated['name']),
            'website' => $validated['website'] ?? null,
            'billing_mode' => $validated['billingMode'] ?? 'subsidy',
            'student_fee' => $validated['tuitionFee'] ?? 0,
            'student_registration_fee' => $validated['regFee'] ?? 0,
            'cost_per_check' => $validated['internalRate'] ?? 0,
            'balance' => 0,
            'is_active' => ($validated['status'] ?? 'active') === 'active',
            'is_partner' => (bool) ($validated['is_partner'] ?? (($validated['plan'] ?? '') === 'Enterprise')),
            'settings' => $this->buildSettings([], $validated),
        ]);

        $university->loadCount('conversions')->load('studyPrograms:id,university_id,name,level');
        $serialized = $this->serializeCampus($university);

        AuditLogSupport::record($request, 'campus.created', $university, [], $serialized);

        return $serialized;
    }

    public function update(string $id, array $validated, Request $request): array
    {
        $university = University::with(['studyPrograms:id,university_id,name,level'])
            ->withCount('conversions')
            ->findOrFail($id);
        $before = $this->serializeCampus($university);

        $settings = $this->buildSettings(
            is_array($university->settings) ? $university->settings : [],
            $validated,
        );
        $status = $validated['status'] ?? ($settings['status'] ?? ($university->is_active ? 'active' : 'pending'));

        $university->update([
            'name' => $validated['name'] ?? $university->name,
            'website' => array_key_exists('website', $validated)
                ? ($validated['website'] ?: null)
                : $university->website,
            'billing_mode' => $validated['billingMode'] ?? $university->billing_mode,
            'is_active' => $status === 'active',
            'is_partner' => array_key_exists('is_partner', $validated)
                ? (bool) $validated['is_partner']
                : $university->is_partner,
            'student_registration_fee' => $validated['regFee'] ?? $university->student_registration_fee,
            'student_fee' => $validated['tuitionFee'] ?? $university->student_fee,
            'cost_per_check' => $validated['internalRate'] ?? $university->cost_per_check,
            'settings' => $settings,
        ]);

        $university->refresh()->loadCount('conversions')->load('studyPrograms:id,university_id,name,level');
        $serialized = $this->serializeCampus($university);

        AuditLogSupport::record($request, 'campus.updated', $university, $before, $serialized);

        return $serialized;
    }

    public function delete(string $id, Request $request): void
    {
        $university = University::findOrFail($id);
        $snapshot = Arr::except($university->toArray(), ['deleted_at']);
        $university->delete();

        AuditLogSupport::record($request, 'campus.deleted', $university, $snapshot, []);
    }

    public function adjustBalance(string $id, float $amount, Request $request): float
    {
        [$beforeBalance, $university] = \Illuminate\Support\Facades\DB::transaction(function () use ($id, $amount) {
            $lockedUniversity = University::query()
                ->lockForUpdate()
                ->findOrFail($id);
            $beforeBalance = (float) $lockedUniversity->balance;

            $lockedUniversity->increment('balance', $amount);

            return [$beforeBalance, $lockedUniversity->fresh()];
        });

        $before = [
            'balance' => $beforeBalance,
        ];

        AuditLogSupport::record(
            $request,
            'campus.balance_adjusted',
            $university,
            $before,
            [
                'balance' => (float) $university->balance,
                'amount' => $amount,
            ],
        );

        return (float) $university->balance;
    }

    private function buildSettings(array $currentSettings, array $validated): array
    {
        $settings = is_array($currentSettings) ? $currentSettings : [];

        foreach (['email', 'plan', 'city', 'province', 'type', 'lecture'] as $field) {
            if (array_key_exists($field, $validated)) {
                $settings[$field] = $validated[$field] ?: null;
            }
        }

        if (array_key_exists('status', $validated)) {
            $settings['status'] = $validated['status'];
        } elseif (!isset($settings['status'])) {
            $settings['status'] = 'active';
        }

        if (array_key_exists('internalRate', $validated) || array_key_exists('leadRate', $validated)) {
            $settings['custom_rates'] = array_filter([
                'internal' => $validated['internalRate'] ?? ($settings['custom_rates']['internal'] ?? null),
                'lead' => $validated['leadRate'] ?? ($settings['custom_rates']['lead'] ?? null),
            ], static fn ($value) => $value !== null && $value !== '');
        }

        if (array_key_exists('studyPrograms', $validated)) {
            $settings['study_programs'] = $this->normalizeStudyPrograms($validated['studyPrograms'] ?? []);
        }

        return $settings;
    }

    private function normalizeStudyPrograms(array $studyPrograms): array
    {
        return collect($studyPrograms)
            ->map(fn (array $studyProgram) => [
                'id' => (string) ($studyProgram['id'] ?? Str::uuid()),
                'name' => (string) $studyProgram['name'],
                'level' => (string) $studyProgram['level'],
            ])
            ->values()
            ->all();
    }

    private function serializeCampus(University $campus): array
    {
        $settings = is_array($campus->settings) ? $campus->settings : [];
        $studyPrograms = $settings['study_programs'] ?? [];

        if (empty($studyPrograms) && $campus->relationLoaded('studyPrograms')) {
            $studyPrograms = $campus->studyPrograms
                ->map(fn (StudyProgram $studyProgram) => [
                    'id' => (string) $studyProgram->id,
                    'name' => $studyProgram->name,
                    'level' => $studyProgram->level,
                ])
                ->values()
                ->all();
        }

        $settings['study_programs'] = $studyPrograms;
        $status = (string) ($settings['status'] ?? ($campus->is_active ? 'active' : 'pending'));

        return [
            ...Arr::except($campus->toArray(), ['study_programs']),
            'status' => $status,
            'settings' => $settings,
        ];
    }

    private function buildUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'campus';
        $slug = $baseSlug;

        if (University::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . Str::lower(Str::random(6));
        }

        return $slug;
    }
}
