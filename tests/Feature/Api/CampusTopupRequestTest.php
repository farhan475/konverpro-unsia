<?php

namespace Tests\Feature\Api;

use App\Models\Transaction;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampusTopupRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_campus_admin_can_create_pending_topup_request(): void
    {
        $university = University::create([
            'name' => 'Universitas Uji',
            'slug' => 'universitas-uji',
            'billing_mode' => 'subsidy',
            'balance' => 0,
            'cost_per_check' => 15000,
            'student_registration_fee' => 0,
            'student_fee' => 4500000,
            'is_active' => true,
        ]);

        $campusAdmin = User::create([
            'name' => 'Campus Admin',
            'email' => 'campus-topup@example.com',
            'password' => 'password',
            'role' => 'campus_admin',
            'university_id' => $university->id,
        ]);

        Sanctum::actingAs($campusAdmin);

        $response = $this->postJson('/api/campus/settings/topups', [
            'amount' => 750000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'topup')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('transactions', [
            'university_id' => $university->id,
            'user_id' => $campusAdmin->id,
            'type' => 'topup',
            'status' => 'pending',
        ]);

        $transaction = Transaction::first();
        $this->assertNotNull($transaction);
        $this->assertSame('750000.00', $transaction->amount);
    }
}
