<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinalizeConversionRequest;
use App\Http\Requests\Admin\ReviewConversionDetailRequest;
use App\Models\Conversion;
use App\Models\University;
use App\Services\Admin\ConversionReviewService;
use App\Support\AcademicSettingsSupport;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversionController extends Controller
{
    public function index(Request $request)
    {
        // Nanti filter by University ID dari user yang login
        // $universityId = auth()->user()->university_id;

        $user = Auth::user();
        // Untuk sekarang (Testing), kita ambil semua dulu atau hardcode ID UNSIA
        $query = Conversion::with(['student', 'studyProgram'])
            ->where('status', '!=', 'draft'); // Yang draft belum disubmit

        if ($user->role !== 'super_admin') {
            $query->where('university_id', $user->university_id);
        }

        $conversions = $query->orderBy('created_at', 'desc')->paginate(10);

        return ApiResponse::success($conversions, 'Data fetched');
    }

    public function show($id)
    {
        $conversion = $this->baseConversionQuery()
            ->with([
                'university:id,name,logo_path,settings',
                'studyProgram:id,code,name,level,settings',
                'student:id,name,email,profile_data',
                'details.targetCourse:id,code,name',
            ])
            ->findOrFail($id);

        return ApiResponse::success($conversion, 'Detail konversi berhasil diambil');
    }

    public function officialDocument($id)
    {
        $conversion = $this->baseConversionQuery()
            ->with([
                'student:id,name,email,profile_data',
                'university:id,name,logo_path,settings',
                'studyProgram:id,code,name,level,settings',
                'studyProgram.courses:id,study_program_id,code,name,sks,semester,is_mandatory',
                'details.targetCourse:id,code,name,sks',
            ])
            ->findOrFail($id);

        return ApiResponse::success(
            AcademicSettingsSupport::buildOfficialDocumentPayload($conversion),
            'Payload dokumen resmi berhasil diambil',
        );
    }

    /**
     * Admin melakukan Review Manual per Mata Kuliah
     */
    public function reviewDetail(
        ReviewConversionDetailRequest $request,
        string $detailId,
        ConversionReviewService $service,
    ) {
        $service->reviewDetail($detailId, $request->validated());

        return ApiResponse::success(null, 'Item berhasil direview');
    }

    /**
     * Finalisasi (Ketuk Palu) Konversi
     */
    public function finalize(
        FinalizeConversionRequest $request,
        string $conversionId,
        ConversionReviewService $service,
    ) {
        $service->finalize($conversionId, $request->validated());

        return ApiResponse::success(null, 'Konversi disetujui sepenuhnya');
    }

    public function getDashboardStats()
    {
        $univId = Auth::user()->university_id;
        $univ = University::findOrFail($univId);

        $stats = [
            'total_conversions' => Conversion::where('university_id', $univId)->count(),
            'pending_review' => Conversion::where('university_id', $univId)->whereIn('status', ['review', 'review_needed'])->count(),
            'approved' => Conversion::where('university_id', $univId)->where('status', 'approved')->count(),
            'balance' => $univ->balance,
        ];

        return ApiResponse::success($stats);
    }

    private function baseConversionQuery()
    {
        $user = Auth::user();
        $query = Conversion::query();

        if ($user->role !== 'super_admin') {
            $query->where('university_id', $user->university_id);
        }

        return $query;
    }
}
