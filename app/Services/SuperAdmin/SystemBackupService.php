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
                'generated_at' => now()->toIso8601String(),
                'generator' => config('app.name', 'KonverPro API'),
                'entity_counts' => collect($data)->map(fn ($items) => count($items))->all(),
            ],
            'data' => $data,
        ];
    }

    public function import(UploadedFile $backupFile): void
    {
        $content = file_get_contents($backupFile->getRealPath());
        $payload = json_decode((string) $content, true);
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        if (! is_array($data)) {
            throw ValidationException::withMessages([
                'backup_file' => 'Format file tidak valid.',
            ]);
        }

        DB::transaction(function () use ($data) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            if (isset($data['global_settings'])) {
                GlobalSetting::truncate();
                GlobalSetting::insert($data['global_settings']);
            }

            if (isset($data['notification_templates'])) {
                NotificationTemplate::truncate();
                NotificationTemplate::insert($data['notification_templates']);
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });
    }
}
