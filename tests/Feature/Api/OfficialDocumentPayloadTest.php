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

class OfficialDocumentPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_fetch_official_document_payload(): void
    {
        [$conversion, $campusAdmin, $studyProgram] = $this->createFixture();

        Sanctum::actingAs($campusAdmin);

        $response = $this->getJson("/api/admin/conversions/{$conversion->id}/official-document");

        $response->assertOk()
            ->assertJsonPath('data.study_program.name', 'Informatika')
            ->assertJsonPath('data.official_document_meta.signatoryName', 'Dr. Ketua Informatika')
            ->assertJsonPath('data.official_document_meta.campusName', 'Universitas Uji')
            ->assertJsonPath('data.summary.acceptedSks', 4)
            ->assertJsonPath('data.academic_settings.prodiId', $studyProgram->id);
    }

    /**
     * @return array{0: Conversion, 1: User, 2: StudyProgram}
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
            'settings' => [
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
            ],
        ]);

        $studyProgram = StudyProgram::create([
            'university_id' => $university->id,
            'code' => 'IF-S1',
            'name' => 'Informatika',
            'level' => 'S1',
            'is_active' => true,
            'settings' => [
                'academic' => [
                    'kaprodiName' => 'Dr. Ketua Informatika',
                    'kaprodiTitle' => 'Ketua Program Studi',
                    'letterFormat' => 'BA/{YEAR}/{NO}/{PRODI}',
                    'minPassingGrade' => 'C',
                    'maxAcceptedSks' => 72,
                    'maxStudyYears' => 5,
                    'semesterRules' => [
                        ['semester' => 1, 'maxSks' => 20],
                    ],
                    'requiredCourses' => [],
                    'notes' => 'Dokumen resmi prodi.',
                ],
            ],
        ]);

        $course = Course::create([
            'study_program_id' => $studyProgram->id,
            'code' => 'IF101',
            'name' => 'Algoritma dan Pemrograman',
            'sks' => 4,
            'semester' => 1,
            'is_mandatory' => true,
            'level' => 'S1',
            'keywords' => ['algoritma'],
        ]);

        $campusAdmin = User::create([
            'name' => 'Campus Admin',
            'email' => 'official-doc@example.com',
            'password' => 'password',
            'role' => 'campus_admin',
            'university_id' => $university->id,
        ]);

        $student = User::create([
            'name' => 'Mahasiswa Uji',
            'email' => 'student-official@example.com',
            'password' => 'password',
            'role' => 'student',
            'profile_data' => [
                'phone' => '08123456789',
                'source_campus' => 'Kampus Asal',
            ],
        ]);

        $conversion = Conversion::create([
            'trx_id' => 'TRX-DOC-001',
            'student_id' => $student->id,
            'university_id' => $university->id,
            'study_program_id' => $studyProgram->id,
            'original_file_path' => 'transcripts/test.xlsx',
            'generated_result_path' => 'results/test.pdf',
            'status' => 'approved',
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
            'match_score' => 0.95,
            'status' => 'manual_accepted',
        ]);

        return [$conversion, $campusAdmin, $studyProgram];
    }
}
