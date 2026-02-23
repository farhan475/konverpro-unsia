<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class CampusCurriculumController extends Controller
{
    public function getProdi()
    {
        $prodis = StudyProgram::where('university_id', Auth::user()->university_id)
            ->withCount('courses') // Hitung jumlah mata kuliah
            ->get();

        return response()->json(['data' => $prodis]);
    }

    // Mendapatkan detail mata kuliah di satu prodi
    public function getCourses($prodiId)
    {
        // Pastikan prodi ini milik kampus yang sedang login
        $prodi = StudyProgram::where('id', $prodiId)
            ->where('university_id', Auth::user()->university_id)
            ->firstOrFail();

        $courses = Course::where('study_program_id', $prodiId)->orderBy('semester')->get();

        return response()->json(['data' => $courses]);
    }

    // API Untuk Upload Excel Kurikulum (Nanti kita buat Jobnya mirip transkrip)
    // public function uploadCurriculum(Request $request, $prodiId) { ... }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'study_program_id' => 'required|exists:study_programs,id',
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Pastikan Prodi milik kampus user yang login (Security)
        $prodi = StudyProgram::where('id', $request->study_program_id)
            ->where('university_id', Auth::user()->university_id)
            ->firstOrFail();

        try {
            $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array) { return $array; }
            }, $request->file('file'));

            $sheet = $data[0];
            $count = 0;

            DB::beginTransaction();

            // Asumsi Baris 1 adalah Header, Data mulai Baris 2
            // Format Excel: [NO, KODE, NAMA_MK, SKS, SEMESTER, WAJIB(Y/N), KEYWORDS]
            foreach ($sheet as $index => $row) {
                if ($index === 0) continue; // Skip Header

                $code = $row[1] ?? null;
                $name = $row[2] ?? null;
                $sks  = (int) ($row[3] ?? 0);
                $sem  = (int) ($row[4] ?? 1);
                $isMandatory = strtoupper($row[5] ?? 'N') === 'Y';
                $keywords = isset($row[6]) ? explode(',', $row[6]) : []; // Keywords dipisah koma

                if (!$name || !$code) continue;

                // Update or Create (Biar kalau upload ulang, data lama terupdate)
                Course::updateOrCreate(
                    [
                        'study_program_id' => $prodi->id,
                        'code' => $code
                    ],
                    [
                        'name' => $name,
                        'sks' => $sks,
                        'semester' => $sem,
                        'is_mandatory' => $isMandatory,
                        'keywords' => $keywords // Akan otomatis jadi JSON karena casting di Model
                    ]
                );
                $count++;
            }

            DB::commit();

            return response()->json([
                'message' => "Berhasil mengimpor $count mata kuliah ke prodi {$prodi->name}."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal import: ' . $e->getMessage()], 500);
        }
    }
}