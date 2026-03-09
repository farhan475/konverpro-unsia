<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function indexTopups()
    {
        $topups = Transaction::with('university:id,name')
            ->where('type', 'topup')
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Map data to match frontend expectations (status pending/approved)
        $mapped = $topups->map(function($t) {
            return [
                'id' => $t->id,
                'campusId' => $t->university_id,
                'campusName' => $t->university->name ?? 'Unknown',
                'amount' => $t->amount,
                'status' => $t->status === 'success' ? 'approved' : 'pending'
            ];
        });

        return response()->json(['data' => $mapped]);
    }

    public function processTopup(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        try {
            DB::beginTransaction();

            $transaction = Transaction::findOrFail($id);
            
            if ($transaction->status === 'success') {
                return response()->json(['message' => 'Transaksi sudah disetujui sebelumnya'], 400);
            }

            if ($request->status === 'approved') {
                $transaction->status = 'success';
                $transaction->save();
                
                // Tambah saldo kampus
                $university = University::findOrFail($transaction->university_id);
                $university->increment('balance', $transaction->amount);
            } else {
                $transaction->status = 'failed';
                $transaction->save();
            }

            DB::commit();

            return response()->json(['message' => 'Topup berhasil diproses']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses Top Up: ' . $e->getMessage()], 500);
        }
    }
}
