<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Conversion;
use App\Services\ConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ConversionController extends Controller
{
    protected $conversionService;

    // Inject Service ke Constructor
    public function __construct(ConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    public function store(Request $request)
    {
        // 1. VALIDASI DULUAN (Best Practice: Validasi sebelum logic berat)
        $validator = Validator::make($request->all(), [
            'university_id'    => 'required|exists:universities,id',
            'study_program_id' => 'required|exists:study_programs,id',
            'file'             => 'required|file|mimes:xlsx,xls,csv|max:5120',
            // Wajib ada nama & email untuk Lead Capture
            'name'             => 'required|string', 
            'email'            => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // 2. SMART LEAD CAPTURE (Cari atau Buat Baru)
            // Ini solusi agar tidak error "User tidak ditemukan" untuk pendaftar baru
            $student = User::firstOrCreate(
                ['email' => $request->email], // Cek berdasarkan email
                [
                    // Jika belum ada, isi data ini:
                    'name' => $request->name,
                    'password' => Hash::make('password123'), // Default password
                    'role' => 'student',
                    'is_active' => true,
                    'profile_data' => ['source' => 'api_upload']
                ]
            );

            // 3. Siapkan Data
            $data = [
                'student_id'       => $student->id,
                'university_id'    => $request->university_id,
                'study_program_id' => $request->study_program_id,
            ];

            // 4. Panggil Service
            $conversion = $this->conversionService->submitTranscript($data, $request->file('file'));

            return response()->json([
                'message' => 'Transkrip berhasil diunggah',
                'lead_status' => $student->wasRecentlyCreated ? 'New User Created' : 'User Found',
                'data' => $conversion
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            // 1. Cari Data Konversi beserta Detailnya
            // Kita gunakan 'with' (Eager Loading) biar query cepat
            $conversion = Conversion::with([
                'university:id,name,logo_path', // Cuma ambil kolom penting
                'studyProgram:id,name',
                'student:id,name,email',
                'details.targetCourse' // Load detail MK dan MK tujuannya
            ])->findOrFail($id);

            // 2. Cek Logic Pembayaran (Middleware Logic)
            // Apakah user boleh lihat hasil? (Ingat logic Hybrid Payment?)
            // if (!$conversion->isVisibleToStudent()) {
            //     return response()->json([
            //         'message' => 'Silakan selesaikan pembayaran untuk melihat hasil detail.',
            //         'payment_status' => 'unpaid',
            //         'snap_token' => $conversion->snap_token,
            //         // Tetap kasih summary, tapi sembunyikan detail
            //         'summary' => [
            //             'total_sks_accepted' => $conversion->total_sks_accepted,
            //             'status' => $conversion->status
            //         ]
            //     ], 402); // 402 Payment Required
            // }

            // 3. Return Data Lengkap
            return response()->json([
                'message' => 'Detail Konversi Ditemukan',
                'data' => $conversion
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Data konversi tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}