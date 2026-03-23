<?php

namespace Tests\Feature\Api;

use App\Models\Conversion;
use App\Models\ConversionDetail;
use App\Models\Course;
use App\Models\StudyProgram;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_conversion_detail_does_not_expose_student_identity(): void
    {
        [$conversion] = $this->createConversionFixture();

        $response = $this->getJson("/api/conversions/{$conversion->id}");

        $response->assertOk()
            ->assertJsonMissingPath('data.student')
            ->assertJsonPath('data.university.name', 'Universitas Uji')
            ->assertJsonPath('data.study_program.name', 'Informatika');
    }

    public function test_admin_conversion_detail_keeps_student_identity_for_dashboard_review(): void
    {
        [$conversion, $campusAdmin, $student] = $this->createConversionFixture();

        Sanctum::actingAs($campusAdmin);

        $response = $this->getJson("/api/admin/conversions/{$conversion->id}");

        $response->assertOk()
            ->assertJsonPath('data.student.email', $student->email)
            ->assertJsonPath('data.student.name', $student->name);
    }

    /**
     * @return array{0: Conversion, 1: User, 2: User}
     */
    private function createConversionFixture(): array
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
            'is_partner' => true,
            'settings' => [
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'lecture' => 'Hybrid',
            ],
        ]);

        $studyProgram = StudyProgram::create([
            'university_id' => $university->id,
            'code' => 'IF-S1',
            'name' => 'Informatika',
            'level' => 'S1',
            'is_active' => true,
        ]);

        $course = Course::create([
            'study_program_id' => $studyProgram->id,
            'code' => 'IF101',
            'name' => 'Algoritma dan Pemrograman',
            'sks' => 4,
            'semester' => 1,
            'is_mandatory' => false,
            'level' => 'S1',
            'keywords' => ['alpro'],
        ]);

        $campusAdmin = User::create([
            'name' => 'Campus Admin',
            'email' => 'admin-uji@example.com',
            'password' => 'password',
            'role' => 'campus_admin',
            'university_id' => $university->id,
        ]);

        $student = User::create([
            'name' => 'Mahasiswa Uji',
            'email' => 'student-uji@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        $conversion = Conversion::create([
            'trx_id' => 'TRX-UNITTEST',
            'student_id' => $student->id,
            'university_id' => $university->id,
            'study_program_id' => $studyProgram->id,
            'original_file_path' => 'transcripts/test.xlsx',
            'status' => 'review',
            'payment_status' => 'free',
            'total_sks_accepted' => 4,
        ]);

        ConversionDetail::create([
            'conversion_id' => $conversion->id,
            'src_code' => 'MK001',
            'src_name' => 'Algoritma Pemrograman',
            'src_grade' => 'A',
            'src_sks' => 4,
            'target_course_id' => $course->id,
            'match_score' => 0.92,
            'status' => 'auto_accepted',
        ]);

        return [$conversion, $campusAdmin, $student];
    }
}
