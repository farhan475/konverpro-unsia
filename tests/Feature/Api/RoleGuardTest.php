<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_campus_admin_cannot_access_super_admin_routes(): void
    {
        $campusAdmin = User::create([
            'name' => 'Campus Admin',
            'email' => 'campus@example.com',
            'password' => 'password',
            'role' => 'campus_admin',
        ]);

        Sanctum::actingAs($campusAdmin);

        $this->getJson('/api/super-admin/users')
            ->assertForbidden();
    }

    public function test_super_admin_can_access_super_admin_routes(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'root@example.com',
            'password' => 'password',
            'role' => 'super_admin',
        ]);

        Sanctum::actingAs($superAdmin);

        $this->getJson('/api/super-admin/users')
            ->assertOk();
    }
}
