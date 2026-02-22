<?php

namespace Database\Seeders;

use App\Models\University;
use Illuminate\Database\Seeder;

class UniversitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
        public function run()
    {
        // 1. KAMPUS SUBSIDI (Skenario Utama: UNSIA)
        University::create([
            'name' => 'Universitas Siber Asia',
            'slug' => 'unsia',
            'code' => '001',
            'billing_mode' => 'subsidy', // Kampus yang bayar
            'balance' => 10_000_000, // Deposit 10 Juta
            'cost_per_check' => 15000, // Sekali cek potong 15rb
            'student_registration_fee' => 0,
            'is_active' => true,
            'is_partner' => true,
            'logo_path' => null, // Nanti diupdate via upload
            'config' => ['theme_color' => '#0056b3']
        ]);

        // 2. KAMPUS MANDIRI (Skenario B: Mahasiswa Bayar)
        University::create([
            'name' => 'Institut Teknologi Digital',
            'slug' => 'itd',
            'code' => '002',
            'billing_mode' => 'independent', // Mahasiswa bayar sendiri
            'balance' => 0,
            'cost_per_check' => 0,
            'student_registration_fee' => 50000, // Mahasiswa bayar 50rb
            'is_active' => true,
            'is_partner' => false,
        ]);
    }
}