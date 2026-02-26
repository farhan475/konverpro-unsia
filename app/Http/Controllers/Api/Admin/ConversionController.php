<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversion;
use App\Models\ConversionDetail;
use App\Models\University;
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

            if($user->role !== 'super_admin') {
                $query->where('university_id', $user->university_id);
            }
            
            $conversions = $query->orderBy('created_at', 'desc')->paginate(10);
            
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
        try {
            $conversion = Conversion::findOrFail($conversionId);
            
            $conversion->update([
                'status' => 'approved',
                'admin_notes' => $request->notes
            ]);

            return response()->json([
                'message' => 'Konversi disetujui sepenuhnya'
            ], 200);

        } catch (\Exception $e) {
            // JIKA ERROR, TANGKAP DAN KIRIM KE FRONTEND
            return response()->json([
                'message' => 'Backend Error: ' . $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
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

        return response()->json(['data' => $stats]);
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