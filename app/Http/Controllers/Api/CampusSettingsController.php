<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campus\StoreStudyProgramRequest;
use App\Http\Requests\Campus\StoreTopupRequestRequest;
use App\Http\Requests\Campus\UpdateAcademicSettingsRequest;
use App\Http\Requests\Campus\UpdateCampusProfileRequest;
use App\Http\Requests\Campus\UpdateDictionaryRequest;
use App\Http\Requests\Campus\UpdateStudyProgramRequest;
use App\Models\Course;
use App\Models\StudyProgram;
use App\Models\Transaction;
use App\Models\University;
use App\Services\Campus\CampusSettingsService;
use App\Support\AcademicSettingsSupport;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Auth;

class CampusSettingsController extends Controller
{
    // ==========================================
    // 1. PROFIL KAMPUS
    // ==========================================
    public function getProfile()
    {
        $university = University::findOrFail(Auth::user()->university_id);
        $settings = is_array($university->settings) ? $university->settings : [];

        return ApiResponse::success([
            ...$university->toArray(),
            'email' => $settings['email'] ?? null,
            'phone' => $settings['phone'] ?? null,
            'address' => $settings['address'] ?? null,
        ]);
    }

    public function updateProfile(
        UpdateCampusProfileRequest $request,
        CampusSettingsService $service,
    ) {
        $university = University::findOrFail(Auth::user()->university_id);

        return ApiResponse::success(
            $service->updateProfile(
                $university,
                $request->validated(),
                $request->file('logo'),
            ),
            'Profil berhasil diperbarui',
        );
    }

    // ==========================================
    // 2. PROGRAM STUDI (CRUD)
    // ==========================================
    public function getProdis()
    {
        $prodis = StudyProgram::where('university_id', Auth::user()->university_id)->get();

        return ApiResponse::success($prodis);
    }

    public function storeProdi(StoreStudyProgramRequest $request, CampusSettingsService $service)
    {
        return ApiResponse::success(
            $service->createStudyProgram(
                (string) Auth::user()->university_id,
                $request->validated(),
            ),
            'Prodi berhasil ditambahkan',
        );
    }

    public function updateProdi(
        UpdateStudyProgramRequest $request,
        string $id,
        CampusSettingsService $service,
    ) {
        return ApiResponse::success(
            $service->updateStudyProgram(
                (string) Auth::user()->university_id,
                $id,
                $request->validated(),
            ),
            'Prodi berhasil diperbarui',
        );
    }

    public function getAcademicSettings($id)
    {
        $prodi = StudyProgram::where('id', $id)
            ->where('university_id', Auth::user()->university_id)
            ->firstOrFail();

        $courses = Course::where('study_program_id', $prodi->id)
            ->get(['id', 'study_program_id', 'code', 'name', 'sks', 'semester', 'is_mandatory']);

        return ApiResponse::success(AcademicSettingsSupport::resolve($prodi, $courses));
    }

    public function updateAcademicSettings(
        UpdateAcademicSettingsRequest $request,
        string $id,
        CampusSettingsService $service,
    ) {
        return ApiResponse::success(
            $service->saveAcademicSettings(
                (string) Auth::user()->university_id,
                $id,
                $request->validated(),
            ),
            'Pengaturan akademik berhasil diperbarui',
        );
    }

    public function destroyProdi($id)
    {
        $prodi = StudyProgram::where('id', $id)
            ->where('university_id', Auth::user()->university_id)
            ->firstOrFail();

        $prodi->delete(); // Soft delete bekerja di sini

        return ApiResponse::success(null, 'Prodi berhasil dihapus');
    }

    // ==========================================
    // 2.5. BILLING HISTORY
    // ==========================================
    public function getBillingHistory()
    {
        $transactions = Transaction::where('university_id', Auth::user()->university_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $mapped = $transactions->map(function (Transaction $transaction) {
            return [
                'id' => $transaction->id,
                'trx_id' => $transaction->invoice_number,
                'type' => $transaction->type,
                'amount' => abs((float) $transaction->amount),
                'created_at' => $transaction->created_at,
                'status' => $transaction->status === 'success'
                    ? 'approved'
                    : ($transaction->status === 'failed' ? 'rejected' : 'pending'),
                'university_id' => $transaction->university_id,
            ];
        });

        return ApiResponse::success($mapped);
    }

    // ==========================================
    // 3. KAMUS SINONIM AI (Mengelola keywords di courses)
    // ==========================================
    public function getDictionary()
    {
        // Ambil semua mata kuliah milik kampus ini
        $courses = Course::whereHas('studyProgram', function ($q) {
            $q->where('university_id', Auth::user()->university_id);
        })->get(['id', 'name', 'keywords']);

        return ApiResponse::success($courses);
    }

    public function updateDictionary(
        UpdateDictionaryRequest $request,
        string $courseId,
        CampusSettingsService $service,
    ) {
        $service->updateDictionary(
            (string) Auth::user()->university_id,
            $courseId,
            $request->validated(),
        );

        return ApiResponse::success(null, 'Kamus berhasil diperbarui');
    }

    public function storeTopupRequest(
        StoreTopupRequestRequest $request,
        CampusSettingsService $service,
    ) {
        return ApiResponse::created(
            $service->createTopupRequest(
                (string) Auth::user()->university_id,
                (string) Auth::id(),
                (float) $request->validated('amount'),
            ),
            'Permintaan top up berhasil dibuat',
        );
    }
}
