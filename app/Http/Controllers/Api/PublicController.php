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
}