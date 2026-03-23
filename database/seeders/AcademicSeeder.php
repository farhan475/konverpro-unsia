<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\StudyProgram;
use App\Models\University;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unsia = University::where('slug', 'unsia')->first();

        // 1. Buat Prodi Informatika
        $prodi = StudyProgram::create([
            'university_id' => $unsia->id,
            'code' => 'IF-S1',
            'name' => 'Informatika',
            'level' => 'S1',
            'is_active' => true
        ]);

        // 2. Buat Mata Kuliah (Data Realistis untuk Test Matching)
        $courses = [
            // Semester 1
            ['code' => 'IF101', 'name' => 'Algoritma dan Pemrograman', 'sks' => 4, 'sem' => 1, 'keywords' => ['alpro', 'dasar pemrograman', 'logika pemrograman']],
            ['code' => 'IF102', 'name' => 'Pengantar Teknologi Informasi', 'sks' => 2, 'sem' => 1, 'keywords' => ['pti', 'pengantar komputer']],
            ['code' => 'UM101', 'name' => 'Bahasa Inggris I', 'sks' => 2, 'sem' => 1, 'keywords' => ['english', 'bahasa inggris dasar']],
            
            // Semester 2
            ['code' => 'IF201', 'name' => 'Struktur Data', 'sks' => 4, 'sem' => 2, 'keywords' => ['data structure', 'strukdat']],
            ['code' => 'IF202', 'name' => 'Basis Data I', 'sks' => 3, 'sem' => 2, 'keywords' => ['database', 'sistem basis data']],
            ['code' => 'IF203', 'name' => 'Matematika Diskrit', 'sks' => 3, 'sem' => 2, 'keywords' => ['matdis', 'logika informatika']],

            // Semester 3
            ['code' => 'IF301', 'name' => 'Pemrograman Berorientasi Objek', 'sks' => 4, 'sem' => 3, 'keywords' => ['pbo', 'oop', 'object oriented']],
            ['code' => 'IF302', 'name' => 'Jaringan Komputer', 'sks' => 3, 'sem' => 3, 'keywords' => ['jarkom', 'computer network']],
            
            // MK Wajib (Tidak bisa dikonversi)
            ['code' => 'UM400', 'name' => 'Kuliah Kerja Nyata', 'sks' => 4, 'sem' => 7, 'is_mandatory' => true, 'keywords' => ['kkn', 'magang']],
            ['code' => 'IF500', 'name' => 'Skripsi', 'sks' => 6, 'sem' => 8, 'is_mandatory' => true, 'keywords' => ['tugas akhir', 'ta']],
        ];

        foreach ($courses as $c) {
            Course::create([
                'study_program_id' => $prodi->id,
                'code' => $c['code'],
                'name' => $c['name'],
                'sks' => $c['sks'],
                'semester' => $c['sem'],
                'is_mandatory' => $c['is_mandatory'] ?? false,
                'level' => $prodi->level,
                'keywords' => $c['keywords'] ?? [] // JSON Keywords
            ]);
        }
    }
}
