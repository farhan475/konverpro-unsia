<?php

namespace App\Services\SuperAdmin;

use App\Models\Conversion;
use App\Models\Course;
use App\Models\GlobalSetting;
use App\Models\NotificationTemplate;
use App\Models\StudyProgram;
use App\Models\Transaction;
use App\Models\University;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;

class SystemBackupService
{
    public function export(): array
    {
        $data = [
            'universities' => University::all(),
            'study_programs' => StudyProgram::all(),
            'courses' => Course::all(),
            'users' => User::all(),
            'conversions' => Conversion::all(),
            'transactions' => Transaction::all(),
            'global_settings' => GlobalSetting::all(),
            'notification_templates' => NotificationTemplate::all(),
        ];

        return [
            'meta' => [
                'format' => 'konverpro-backup.v1',
                'generated_at' => now()->toIso8601String(),
                'generator' => config('app.name', 'KonverPro API'),
                'entity_counts' => collect($data)->map(fn ($items) => count($items))->all(),
            ],
            'data' => $data,
        ];
    }

    public function import(UploadedFile $backupFile): array
    {
        $content = file_get_contents($backupFile->getRealPath());
        if ($content === false) {
            throw ValidationException::withMessages([
                'backup_file' => 'File backup tidak dapat dibaca.',
            ]);
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'backup_file' => 'Format JSON backup tidak valid.',
            ]);
        }

        if (
            is_array($payload)
            && is_array($payload['meta'] ?? null)
            && isset($payload['meta']['format'])
            && $payload['meta']['format'] !== 'konverpro-backup.v1'
        ) {
            throw ValidationException::withMessages([
                'backup_file' => 'Format backup tidak didukung oleh versi sistem ini.',
            ]);
        }

        $data = is_array($payload) && is_array($payload['data'] ?? null)
            ? $payload['data']
            : $payload;

        if (! is_array($data)) {
            throw ValidationException::withMessages([
                'backup_file' => 'Format file tidak valid.',
            ]);
        }

        $globalSettings = $this->normalizeImportSection($data, 'global_settings');
        $notificationTemplates = $this->normalizeImportSection($data, 'notification_templates');

        if ($globalSettings === null && $notificationTemplates === null) {
            throw ValidationException::withMessages([
                'backup_file' => 'File backup tidak memiliki bagian yang didukung untuk restore parsial.',
            ]);
        }

        $driver = DB::getDriverName();

        DB::transaction(function () use ($driver, $globalSettings, $notificationTemplates) {
            $this->disableForeignKeyChecks($driver);

            try {
                if ($globalSettings !== null) {
                    GlobalSetting::truncate();

                    if ($globalSettings !== []) {
                        GlobalSetting::insert($globalSettings);
                    }
                }

                if ($notificationTemplates !== null) {
                    NotificationTemplate::truncate();

                    if ($notificationTemplates !== []) {
                        NotificationTemplate::insert($notificationTemplates);
                    }
                }
            } finally {
                $this->enableForeignKeyChecks($driver);
            }
        });

        return [
            'restored_sections' => array_values(array_filter([
                $globalSettings !== null ? 'global_settings' : null,
                $notificationTemplates !== null ? 'notification_templates' : null,
            ])),
            'global_settings_count' => $globalSettings !== null ? count($globalSettings) : 0,
            'notification_templates_count' => $notificationTemplates !== null ? count($notificationTemplates) : 0,
        ];
    }

    private function normalizeImportSection(array $data, string $key): ?array
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        if (! is_array($data[$key])) {
            throw ValidationException::withMessages([
                'backup_file' => "Bagian {$key} pada file backup tidak valid.",
            ]);
        }

        $section = array_values($data[$key]);

        foreach ($section as $index => $item) {
            if (! is_array($item)) {
                throw ValidationException::withMessages([
                    'backup_file' => 'Item #'.($index + 1)." pada bagian {$key} tidak valid.",
                ]);
            }
        }

        return $section;
    }

    private function disableForeignKeyChecks(string $driver): void
    {
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }
    }

    private function enableForeignKeyChecks(string $driver): void
    {
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
}
