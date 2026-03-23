<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversion\StoreConversionRequest;
use App\Models\Conversion;
use App\Models\Course;
use App\Services\ConversionSubmissionService;
use App\Support\ApiResponse;

class ConversionController extends Controller
{
    public function store(
        StoreConversionRequest $request,
        ConversionSubmissionService $service,
    ) {
        $result = $service->submit($request->validated(), $request->file('file'));

        return ApiResponse::created(
            $result['conversion'],
            'Transkrip berhasil diunggah',
            [
                'lead_status' => $result['lead_status'],
            ],
        );
    }

    public function show($id)
    {
        $conversion = Conversion::with([
            'university:id,name,logo_path,student_registration_fee,student_fee,is_partner,settings',
            'studyProgram:id,name,level',
            'details.targetCourse:id,code,name',
        ])->findOrFail($id);

        $studyProgram = $conversion->studyProgram;
        $university = $conversion->university;
        $settings = is_array($university?->settings) ? $university->settings : [];
        $requiredSks = $studyProgram
            ? Course::where('study_program_id', $studyProgram->id)->sum('sks')
            : 0;
        $acceptedSks = (int) ($conversion->total_sks_accepted ?? 0);
        $remainingSks = max($requiredSks - $acceptedSks, 0);

        return ApiResponse::success([
            'id' => $conversion->id,
            'trx_id' => $conversion->trx_id,
            'status' => $conversion->status,
            'payment_status' => $conversion->payment_status,
            'total_sks_accepted' => $acceptedSks,
            'total_sks_target' => $remainingSks,
            'total_sks_required' => $requiredSks,
            'estimated_semesters' => $remainingSks > 0 ? (int) ceil($remainingSks / 20) : 0,
            'estimated_years' => $remainingSks > 0 ? round(ceil($remainingSks / 20) / 2, 1) : 0,
            'tuition_per_semester' => (float) ($university?->student_fee ?? 0),
            'registration_fee' => (float) ($university?->student_registration_fee ?? 0),
            'university' => [
                'id' => $university?->id,
                'name' => $university?->name,
                'city' => $settings['city'] ?? null,
                'province' => $settings['province'] ?? null,
                'learning_method' => $settings['lecture'] ?? null,
                'is_official_partner' => (bool) ($university?->is_partner ?? false),
            ],
            'study_program' => [
                'id' => $studyProgram?->id,
                'name' => $studyProgram?->name,
                'total_sks' => $requiredSks,
            ],
            'details' => $conversion->details->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'status' => $detail->status,
                    'src_name' => $detail->src_name,
                    'src_sks' => $detail->src_sks,
                    'src_grade' => $detail->src_grade,
                    'target_course' => $detail->targetCourse
                        ? [
                            'code' => $detail->targetCourse->code,
                            'name' => $detail->targetCourse->name,
                        ]
                        : null,
                ];
            })->values(),
            'notes' => array_values(array_filter([
                $conversion->status === 'processing'
                    ? 'Transkrip sedang diproses oleh sistem. Silakan tunggu beberapa saat lagi.'
                    : null,
                $conversion->status === 'draft' && $conversion->payment_status === 'pending'
                    ? 'Simulasi menunggu pembayaran mahasiswa sebelum hasil lengkap dapat dibuka.'
                    : null,
                $conversion->status === 'review'
                    ? 'Sebagian hasil masih menunggu validasi admin kampus.'
                    : null,
            ])),
        ], 'Detail Konversi Ditemukan');
    }
}
