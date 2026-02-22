<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversion;
use App\Models\ConversionDetail;
use Illuminate\Http\Request;

class ConversionController extends Controller
{
    public function index(Request $request)
    {
        // Nanti filter by University ID dari user yang login
        // $universityId = auth()->user()->university_id;
        
        // Untuk sekarang (Testing), kita ambil semua dulu atau hardcode ID UNSIA
        $conversions = Conversion::with(['student', 'studyProgram'])
            ->where('status', '!=', 'draft') // Yang draft belum disubmit
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'message' => 'Data fetched',
            'data' => $conversions
        ]);
    }

    /**
     * Admin melakukan Review Manual per Mata Kuliah
     */
    public function reviewDetail(Request $request, $detailId)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected', // Keputusan Admin
            'admin_notes' => 'nullable|string'
        ]);

        $detail = ConversionDetail::findOrFail($detailId);
        
        $detail->update([
            'status' => $request->status === 'approved' ? 'manual_accepted' : 'rejected',
            'admin_notes' => $request->admin_notes
        ]);

        // Hitung ulang total SKS di Header Conversion
        $this->recalculateTotalSks($detail->conversion_id);

        return response()->json(['message' => 'Item berhasil direview']);
    }

    /**
     * Finalisasi (Ketuk Palu) Konversi
     */
    public function finalize(Request $request, $conversionId)
    {
        $conversion = Conversion::findOrFail($conversionId);
        
        $conversion->update([
            'status' => 'approved',
            'admin_notes' => $request->notes // Catatan final (misal: "Selamat bergabung")
        ]);

        // TODO: Kirim Email ke Mahasiswa (Nanti)

        return response()->json(['message' => 'Konversi disetujui sepenuhnya']);
    }

    // Helper Private
    private function recalculateTotalSks($conversionId)
    {
        $total = ConversionDetail::where('conversion_id', $conversionId)
            ->whereIn('status', ['auto_accepted', 'manual_accepted'])
            ->with('targetCourse') // Join ke tabel course untuk ambil SKS target
            ->get()
            ->sum(function ($detail) {
                return $detail->targetCourse ? $detail->targetCourse->sks : 0;
            });

        Conversion::where('id', $conversionId)->update(['total_sks_accepted' => $total]);
    }
}