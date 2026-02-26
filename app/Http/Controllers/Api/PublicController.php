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
        // Ambil kampus yang is_active = true
        // Dan load relasi studyPrograms yang is_active = true
        $campuses = University::where('is_active', true)
            ->with(['studyPrograms' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get(['id', 'name', 'logo_path']); // Ambil kolom yang diperlukan saja

        return response()->json([
            'message' => 'Daftar kampus berhasil diambil',
            'data' => $campuses
        ]);
    }

    public function getMarketplaceData()
    {
        $universities = University::where('is_active', true)
            ->with(['studyPrograms' => function ($query) {
                // Ambil prodi yang aktif beserta courses-nya
                $query->where('is_active', true)
                      ->with(['courses' => function ($q) {
                          // Ambil nama, sks, dan keywords untuk AI
                          $q->select('id', 'study_program_id', 'code', 'name', 'sks', 'keywords', 'is_mandatory');
                      }]);
            }])
            ->get();

        // Kita format/mapping datanya agar gampang dikonsumsi Frontend
        $formattedData = [];

        foreach ($universities as $univ) {
            foreach ($univ->studyPrograms as $prodi) {
                $formattedData[] = [
                    'id' => $univ->id, // University ID
                    'study_program_id' => $prodi->id,
                    'campus' => $univ->name,
                    'isOfficial' => $univ->is_partner,
                    'logoPath' => $univ->logo_path,
                    'province' => $univ->settings['province'] ?? 'Umum',
                    'type' => $univ->settings['type'] ?? 'PTS',
                    'lecture' => $univ->settings['lecture'] ?? 'Online/Offline',
                    'prodiName' => $prodi->name,
                    'strata' => $prodi->level,
                    'tuition' => $univ->student_fee, // Asumsi dari config atau DB
                    'registrationFee' => $univ->cost_per_check,
                    'courses' => $prodi->courses // Daftar MK untuk bahan matching
                ];
            }
        }

        return response()->json([
            'message' => 'Data Marketplace Ready',
            'data' => $formattedData
        ]);
    }
}