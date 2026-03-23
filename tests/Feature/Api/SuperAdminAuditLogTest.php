<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_actions_are_written_to_audit_logs(): void
    {
        $superAdmin = User::create([
            'name' => 'Audit Admin',
            'email' => 'audit-admin@example.com',
            'password' => 'password',
            'role' => 'super_admin',
        ]);

        Sanctum::actingAs($superAdmin);

        $createResponse = $this->postJson('/api/super-admin/campuses', [
            'name' => 'Universitas Audit',
            'email' => 'audit-campus@example.com',
            'plan' => 'Growth',
        ]);

        $createResponse->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'event' => 'campus.created',
        ]);

        $logsResponse = $this->getJson('/api/super-admin/reports/audit-logs');

        $logsResponse->assertOk()
            ->assertJsonPath('data.data.0.event', 'campus.created')
            ->assertJsonPath('data.data.0.actor_name', 'Audit Admin')
            ->assertJsonPath('data.data.0.action', 'Campus Created');
    }
}
