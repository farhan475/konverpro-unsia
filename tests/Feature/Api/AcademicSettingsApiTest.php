<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\StudyProgram;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AcademicSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_campus_admin_can_fetch_default_academic_settings(): void
    {
        [$campusAdmin, $studyProgram] = $this->createFixture();

        Sanctum::actingAs($campusAdmin);

        $response = $this->getJson("/api/campus/settings/prodi/{$studyProgram->id}/academic-settings");

        $response->assertOk()
            ->assertJsonPath('data.prodiId', $studyProgram->id)
            ->assertJsonPath('data.prodiName', 'Informatika')
            ->assertJsonPath('data.minPassingGrade', 'C')
            ->assertJsonCount(1, 'data.requiredCourses');
    }

    public function test_campus_admin_can_update_academic_settings(): void
    {
        [$campusAdmin, $studyProgram] = $this->createFixture();

        Sanctum::actingAs($campusAdmin);

        $payload = [
            'kaprodiName' => 'Dr. Ketua Prodi',
            'kaprodiTitle' => 'Ketua Program Studi',
            'letterFormat' => 'KP/{YEAR}/{NO}/{PRODI}',
            'minPassingGrade' => 'B',
            'maxAcceptedSks' => 80,
            'maxStudyYears' => 6,
            'semesterRules' => [
                ['semester' => 1, 'maxSks' => 20],
                ['semester' => 2, 'maxSks' => 22],
            ],
            'requiredCourses' => [
                ['id' => Course::first()->id],
            ],
            'notes' => 'Dokumen disahkan oleh prodi.',
            'signatureDataUrl' => 'data:image/png;base64,abc123',
        ];

        $response = $this->putJson("/api/campus/settings/prodi/{$studyProgram->id}/academic-settings", $payload);

        $response->assertOk()
            ->assertJsonPath('data.kaprodiName', 'Dr. Ketua Prodi')
            ->assertJsonPath('data.minPassingGrade', 'B')
            ->assertJsonPath('data.requiredCourses.0.code', 'IF101');

        $studyProgram->refresh();

        $this->assertSame('Dr. Ketua Prodi', data_get($studyProgram->settings, 'academic.kaprodiName'));
        $this->assertSame('KP/{YEAR}/{NO}/{PRODI}', data_get($studyProgram->settings, 'academic.letterFormat'));
    }

    /**
     * @return array{0: User, 1: StudyProgram}
     */
    private function createFixture(): array
    {
        $university = University::create([
            'name' => 'Universitas Uji',
            'slug' => 'universitas-uji',
            'billing_mode' => 'subsidy',
            'balance' => 1000000,
            'cost_per_check' => 15000,
            'student_registration_fee' => 0,
            'student_fee' => 4500000,
            'is_active' => true,
        ]);

        $studyProgram = StudyProgram::create([
            'university_id' => $university->id,
            'code' => 'IF-S1',
            'name' => 'Informatika',
            'level' => 'S1',
            'is_active' => true,
        ]);

        Course::create([
            'study_program_id' => $studyProgram->id,
            'code' => 'IF101',
            'name' => 'Algoritma dan Pemrograman',
            'sks' => 4,
            'semester' => 1,
            'is_mandatory' => true,
            'level' => 'S1',
            'keywords' => ['algoritma'],
        ]);

        Course::create([
            'study_program_id' => $studyProgram->id,
            'code' => 'IF102',
            'name' => 'Struktur Data',
            'sks' => 3,
            'semester' => 2,
            'is_mandatory' => false,
            'level' => 'S1',
            'keywords' => ['struktur data'],
        ]);

        $campusAdmin = User::create([
            'name' => 'Campus Admin',
            'email' => 'academic-settings@example.com',
            'password' => 'password',
            'role' => 'campus_admin',
            'university_id' => $university->id,
        ]);

        return [$campusAdmin, $studyProgram];
    }
}
