<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class ConversionSubmissionService
{
    public function __construct(
        private readonly ConversionService $conversionService,
    ) {
    }

    public function submit(array $validated, UploadedFile $file): array
    {
        $student = User::firstOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'password' => Hash::make('password123'),
                'role' => 'student',
                'is_active' => true,
                'profile_data' => [
                    'source' => 'api_upload',
                    'phone' => $validated['phone'] ?? null,
                    'source_campus' => $validated['source_campus'] ?? null,
                ],
            ],
        );

        if (!$student->wasRecentlyCreated && (($validated['phone'] ?? null) || ($validated['source_campus'] ?? null))) {
            $profile = is_array($student->profile_data) ? $student->profile_data : [];
            $profile['phone'] = $validated['phone'] ?? ($profile['phone'] ?? null);
            $profile['source_campus'] = $validated['source_campus'] ?? ($profile['source_campus'] ?? null);
            $student->profile_data = $profile;

            if (($validated['name'] ?? null) && $student->name !== $validated['name']) {
                $student->name = $validated['name'];
            }

            $student->save();
        }

        $conversion = $this->conversionService->submitTranscript([
            'student_id' => $student->id,
            'university_id' => $validated['university_id'],
            'study_program_id' => $validated['study_program_id'],
        ], $file);

        return [
            'lead_status' => $student->wasRecentlyCreated ? 'New User Created' : 'User Found and Updated',
            'conversion' => $conversion,
        ];
    }
}
