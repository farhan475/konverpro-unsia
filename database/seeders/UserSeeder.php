<?php

namespace Database\Seeders;

use App\Models\University;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        $password = Hash::make('password'); // Password default: 'password'
        $unsia = University::where('slug', 'unsia')->first();

        // 1. SUPER ADMIN (Pemilik SaaS)
        User::create([
            'name' => 'Super Admin',
            'email' => 'root@konverpro.id',
            'password' => $password,
            'role' => 'super_admin',
            'university_id' => null,
        ]);

        // 2. ADMIN KAMPUS (UNSIA)
        User::create([
            'name' => 'Admin UNSIA',
            'email' => 'admin@unsia.ac.id',
            'password' => $password,
            'role' => 'campus_admin',
            'university_id' => $unsia->id,
        ]);

        // 3. MAHASISWA (User Umum)
        User::create([
            'name' => 'Budi Mahasiswa',
            'email' => 'student@gmail.com',
            'password' => $password,
            'role' => 'student',
            'university_id' => null,
            'profile_data' => [
                'phone' => '08123456789',
                'asal_kampus' => 'Universitas Terbuka'
            ]
        ]);
    }
}