<?php

namespace App\Services\Campus;

use App\Models\Course;
use App\Models\StudyProgram;
use App\Models\Transaction;
use App\Models\University;
use App\Support\AcademicSettingsSupport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CampusSettingsService
{
    public function updateProfile(University $university, array $validated, ?UploadedFile $logo = null): University
    {
        $data = Arr::only($validated, ['name', 'website']);
        $settings = is_array($university->settings) ? $university->settings : [];

        if ($logo) {
            $data['logo_path'] = $logo->store('logos', 'public');
        }

        foreach (['email', 'phone', 'address'] as $field) {
            if (array_key_exists($field, $validated)) {
                $settings[$field] = filled($validated[$field]) ? (string) $validated[$field] : null;
            }
        }

        $data['settings'] = $settings;

        $university->update($data);

        return $university->fresh();
    }

    public function createStudyProgram(string $universityId, array $validated): StudyProgram
    {
        return StudyProgram::create([
            'university_id' => $universityId,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'level' => $validated['level'],
            'is_active' => true,
        ]);
    }

    public function updateStudyProgram(string $universityId, string $id, array $validated): StudyProgram
    {
        $prodi = StudyProgram::where('id', $id)
            ->where('university_id', $universityId)
            ->firstOrFail();

        $prodi->update($validated);

        return $prodi->fresh();
    }

    public function saveAcademicSettings(string $universityId, string $id, array $validated): array
    {
        $prodi = StudyProgram::where('id', $id)
            ->where('university_id', $universityId)
            ->firstOrFail();

        $courses = Course::where('study_program_id', $prodi->id)
            ->get(['id', 'study_program_id', 'code', 'name', 'sks', 'semester', 'is_mandatory']);

        return AcademicSettingsSupport::save($prodi, $validated, $courses);
    }

    public function updateDictionary(string $universityId, string $courseId, array $validated): void
    {
        $course = Course::whereHas('studyProgram', function ($query) use ($universityId) {
            $query->where('university_id', $universityId);
        })->findOrFail($courseId);

        $course->update([
            'keywords' => $validated['keywords'] ?? null,
        ]);
    }

    public function createTopupRequest(string $universityId, string $userId, float $amount): array
    {
        $university = University::findOrFail($universityId);

        $transaction = Transaction::create([
            'invoice_number' => 'TOPUP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4)),
            'university_id' => $university->id,
            'user_id' => $userId,
            'type' => 'topup',
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'manual_admin',
            'gateway_response' => [
                'source' => 'campus_admin_request',
            ],
        ]);

        return [
            'id' => $transaction->id,
            'trx_id' => $transaction->invoice_number,
            'type' => $transaction->type,
            'amount' => (float) $transaction->amount,
            'created_at' => $transaction->created_at,
            'status' => 'pending',
            'university_id' => $transaction->university_id,
            'university_name' => $university->name,
        ];
    }
}
