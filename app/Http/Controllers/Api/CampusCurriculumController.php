<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campus\ImportCurriculumRequest;
use App\Models\Course;
use App\Models\StudyProgram;
use App\Services\Campus\CurriculumImportService;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Auth;

class CampusCurriculumController extends Controller
{
    public function getProdi()
    {
        $prodis = StudyProgram::where('university_id', Auth::user()->university_id)
            ->withCount('courses') // Hitung jumlah mata kuliah
            ->get();

        return ApiResponse::success($prodis);
    }

    // Mendapatkan detail mata kuliah di satu prodi
    public function getCourses($prodiId)
    {
        // Pastikan prodi ini milik kampus yang sedang login
        $prodi = StudyProgram::where('id', $prodiId)
            ->where('university_id', Auth::user()->university_id)
            ->firstOrFail();

        $courses = Course::where('study_program_id', $prodiId)->orderBy('semester')->get();

        return ApiResponse::success($courses);
    }

    // API Untuk Upload Excel Kurikulum (Nanti kita buat Jobnya mirip transkrip)
    // public function uploadCurriculum(Request $request, $prodiId) { ... }

    public function import(
        ImportCurriculumRequest $request,
        CurriculumImportService $service,
    ) {
        $prodi = StudyProgram::where('id', $request->validated('study_program_id'))
            ->where('university_id', Auth::user()->university_id)
            ->firstOrFail();

        $count = $service->import($prodi, $request->file('file'));

        return ApiResponse::success(
            null,
            "Berhasil mengimpor $count mata kuliah ke prodi {$prodi->name}.",
        );
    }
}
