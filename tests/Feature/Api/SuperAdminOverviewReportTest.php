<?php

namespace Tests\Feature\Api;

use App\Models\Conversion;
use App\Models\StudyProgram;
use App\Models\Transaction;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminOverviewReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_fetch_overview_report(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'overview@example.com',
            'password' => 'password',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $campus = University::create([
            'name' => 'Universitas Uji',
            'slug' => 'universitas-uji',
            'billing_mode' => 'subsidy',
            'balance' => 800000,
            'cost_per_check' => 15000,
            'student_registration_fee' => 250000,
            'student_fee' => 4500000,
            'is_active' => true,
            'is_partner' => true,
        ]);

        $studyProgram = StudyProgram::create([
            'university_id' => $campus->id,
            'code' => 'IF-S1',
            'name' => 'Informatika',
            'level' => 'S1',
            'is_active' => true,
        ]);

        $student = User::create([
            'name' => 'Mahasiswa Uji',
            'email' => 'overview-student@example.com',
            'password' => 'password',
            'role' => 'student',
            'is_active' => true,
        ]);

        Conversion::create([
            'trx_id' => 'TRX-OVERVIEW-01',
            'student_id' => $student->id,
            'university_id' => $campus->id,
            'study_program_id' => $studyProgram->id,
            'original_file_path' => 'transcripts/overview.xlsx',
            'status' => 'approved',
            'payment_status' => 'free',
            'total_sks_accepted' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Transaction::create([
            'invoice_number' => 'TOPUP-OVERVIEW-01',
            'university_id' => $campus->id,
            'user_id' => $superAdmin->id,
            'type' => 'topup',
            'amount' => 1500000,
            'status' => 'success',
            'payment_method' => 'manual_admin',
        ]);

        Sanctum::actingAs($superAdmin);

        $response = $this->getJson('/api/super-admin/reports/overview');

        $response->assertOk()
            ->assertJsonPath('data.stats.totalCampuses', 1)
            ->assertJsonPath('data.stats.partnerCampuses', 1)
            ->assertJsonPath('data.stats.totalRevenue', 1500000)
            ->assertJsonCount(1, 'data.campus_heatmap')
            ->assertJsonPath('data.insights.latest_conversions.0.university_name', 'Universitas Uji');
    }
}
