<?php

namespace Tests\Feature\Api;

use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminCampusWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_campus_with_extended_workspace_fields(): void
    {
        $superAdmin = User::create([
            'name' => 'Root Admin',
            'email' => 'root-campus-workspace@example.com',
            'password' => 'password',
            'role' => 'super_admin',
        ]);

        Sanctum::actingAs($superAdmin);

        $response = $this->postJson('/api/super-admin/campuses', [
            'name' => 'Universitas Integrasi',
            'email' => 'ops@integrasi.test',
            'plan' => 'Growth',
            'status' => 'active',
            'is_partner' => true,
            'website' => 'https://integrasi.test',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'type' => 'PTS',
            'lecture' => 'Hybrid',
            'billingMode' => 'independent',
            'regFee' => 250000,
            'tuitionFee' => 3500000,
            'internalRate' => 15000,
            'leadRate' => 10000,
            'studyPrograms' => [
                ['id' => 'draft-prodi-1', 'name' => 'Teknik Informatika', 'level' => 'S1'],
                ['id' => 'draft-prodi-2', 'name' => 'Manajemen', 'level' => 'S2'],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Universitas Integrasi')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.website', 'https://integrasi.test')
            ->assertJsonPath('data.billing_mode', 'independent')
            ->assertJsonPath('data.settings.city', 'Jakarta')
            ->assertJsonPath('data.settings.custom_rates.internal', 15000)
            ->assertJsonPath('data.settings.custom_rates.lead', 10000)
            ->assertJsonCount(2, 'data.settings.study_programs');

        $university = University::first();
        $this->assertNotNull($university);
        $this->assertSame('https://integrasi.test', $university->website);
        $this->assertSame('independent', $university->billing_mode);
        $this->assertSame('250000.00', $university->student_registration_fee);
        $this->assertSame('3500000.00', $university->student_fee);
        $this->assertTrue($university->is_active);
        $this->assertTrue($university->is_partner);
        $this->assertSame('active', $university->settings['status']);
        $this->assertSame('Jakarta', $university->settings['city']);
        $this->assertCount(2, $university->settings['study_programs']);
    }

    public function test_super_admin_can_update_campus_workspace_status_and_settings(): void
    {
        $superAdmin = User::create([
            'name' => 'Root Admin',
            'email' => 'root-campus-update@example.com',
            'password' => 'password',
            'role' => 'super_admin',
        ]);

        $university = University::create([
            'name' => 'Universitas Lama',
            'slug' => 'universitas-lama',
            'billing_mode' => 'subsidy',
            'balance' => 0,
            'cost_per_check' => 10000,
            'student_registration_fee' => 100000,
            'student_fee' => 2000000,
            'website' => 'https://lama.test',
            'is_active' => true,
            'settings' => [
                'email' => 'lama@test.id',
                'plan' => 'Starter',
                'status' => 'active',
            ],
        ]);

        Sanctum::actingAs($superAdmin);

        $response = $this->putJson('/api/super-admin/campuses/' . $university->id, [
            'name' => 'Universitas Baru',
            'email' => 'baru@test.id',
            'plan' => 'Enterprise',
            'status' => 'suspended',
            'is_partner' => true,
            'website' => 'https://baru.test',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'type' => 'PTS',
            'lecture' => 'Online',
            'billingMode' => 'independent',
            'regFee' => 300000,
            'tuitionFee' => 4500000,
            'internalRate' => 18000,
            'leadRate' => 12000,
            'studyPrograms' => [
                ['id' => 'workspace-prodi-1', 'name' => 'Sistem Informasi', 'level' => 'S1'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Universitas Baru')
            ->assertJsonPath('data.status', 'suspended')
            ->assertJsonPath('data.website', 'https://baru.test')
            ->assertJsonPath('data.billing_mode', 'independent')
            ->assertJsonPath('data.settings.province', 'Jawa Barat')
            ->assertJsonPath('data.settings.study_programs.0.name', 'Sistem Informasi');

        $university->refresh();

        $this->assertSame('Universitas Baru', $university->name);
        $this->assertSame('https://baru.test', $university->website);
        $this->assertSame('independent', $university->billing_mode);
        $this->assertFalse($university->is_active);
        $this->assertTrue($university->is_partner);
        $this->assertSame('suspended', $university->settings['status']);
        $this->assertSame('Jawa Barat', $university->settings['province']);
        $this->assertSame('18000', (string) $university->settings['custom_rates']['internal']);
    }
}
