<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\University;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Mengambil daftar kampus yang aktif beserta prodi-nya
     */
    public function getActiveCampuses()
    {
        $campuses = University::where('is_active', true)
            ->with(['studyPrograms' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get(['id', 'name', 'logo_path']);

        return response()->json([
            'message' => 'Daftar kampus berhasil diambil',
            'data' => $campuses
        ]);
    }

    public function getMarketplaceData(Request $request)
    {
        $query = University::where('is_active', true)
            ->with(['studyPrograms' => function ($q) use ($request) {
                $q->where('is_active', true);
                if ($request->has('prodi_name')) {
                    $q->where('name', 'like', '%' . $request->prodi_name . '%');
                }
                $q->with(['courses' => function ($q2) {
                    $q2->select('id', 'study_program_id', 'code', 'name', 'sks', 'keywords', 'is_mandatory');
                }]);
            }]);

        if ($request->has('campus_name')) {
            $query->where('name', 'like', '%' . $request->campus_name . '%');
        }

        $universities = $query->get();

        $formattedData = [];

        foreach ($universities as $univ) {
            // Apply JSON settings filters manually if needed (or via whereJsonContains if using MySQL 5.7+)
            $settings = $univ->settings ?? [];
            $province = $settings['province'] ?? 'Umum';
            $type = $settings['type'] ?? 'PTS';
            $lecture = $settings['lecture'] ?? 'Online/Offline';
            $city = $settings['city'] ?? '';

            if ($request->has('province') && strtolower($province) !== strtolower($request->province) && strtolower($request->province) !== 'semua') {
                continue;
            }
            if ($request->has('type') && strtolower($type) !== strtolower($request->type) && strtolower($request->type) !== 'semua') {
                continue;
            }
            if ($request->has('lecture') && strtolower($lecture) !== strtolower($request->lecture) && strtolower($request->lecture) !== 'semua') {
                continue;
            }

            foreach ($univ->studyPrograms as $prodi) {
                $formattedData[] = [
                    'id' => $univ->id,
                    'study_program_id' => $prodi->id,
                    'campus' => $univ->name,
                    'isOfficial' => $univ->is_partner,
                    'logoPath' => $univ->logo_path, // Uses accessor getLogoUrlAttribute if mapped
                    'province' => $province,
                    'city' => $city,
                    'type' => $type,
                    'lecture' => $lecture,
                    'prodiName' => $prodi->name,
                    'strata' => $prodi->level,
                    'tuition' => $univ->student_fee ?? 0, 
                    'registrationFee' => $univ->cost_per_check ?? $univ->student_registration_fee ?? 0,
                    'courses' => $prodi->courses
                ];
            }
        }

        // Handle simple sorting if requested
        if ($request->has('sort_by')) {
            $sortBy = $request->sort_by;
            usort($formattedData, function($a, $b) use ($sortBy) {
                if ($sortBy === 'fee_asc') {
                    return $a['tuition'] <=> $b['tuition'];
                }
                return 0;
            });
        }

        return response()->json([
            'message' => 'Data Marketplace Ready',
            'data' => $formattedData
        ]);
    }

    public function downloadTemplate()
    {
        // For simplicity when not using Maatwebsite Excel, we return a CSV
        $filename = "Template_Transkrip_KonverPro.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            
            // Kolom dummy sesuai yg dibaca frontend
            fputcsv($file, ['Nama', ':', 'Mahasiswa Contoh']);
            fputcsv($file, ['Universitas', ':', 'Universitas Asal']);
            fputcsv($file, ['Email', ':', 'contoh@email.com']);
            fputcsv($file, []);
            fputcsv($file, ['Kode MK', 'Nama Mata Kuliah', 'SKS', 'Nilai Huruf']);
            fputcsv($file, ['MK001', 'Algoritma Pemrograman', '3', 'A']);
            fputcsv($file, ['MK002', 'Basis Data', '3', 'B+']);
            fputcsv($file, ['MK003', 'Struktur Data', '3', 'A-']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}