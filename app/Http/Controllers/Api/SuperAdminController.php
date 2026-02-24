<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\University;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    // Cek proteksi role
    public function __construct()
    {
        // Pastikan hanya super_admin yang bisa akses controller ini
        if (Auth::check() && Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }
    }

    // 1. Ambil Semua Data Kampus
    public function getCampuses()
    {
        // Ambil data kampus beserta jumlah mahasiswa yang sudah konversi
        $campuses = University::withCount('conversions')->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $campuses]);
    }

    // 2. Fitur Top Up Saldo Kampus
    public function topupBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000'
        ]);

        try {
            DB::beginTransaction();

            $university = University::findOrFail($id);
            
            // Tambah saldo
            $university->increment('balance', $request->amount);

            // Catat di tabel transaksi
            Transaction::create([
                'invoice_number' => 'TOPUP-' . time(),
                'university_id' => $university->id,
                'user_id' => Auth::id(),
                'type' => 'topup',
                'amount' => $request->amount,
                'status' => 'success'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Top Up sebesar Rp ' . number_format($request->amount, 0, ',', '.') . ' berhasil ditambahkan ke ' . $university->name
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal Top Up: ' . $e->getMessage()], 500);
        }
    }

    public function getUsers()
    {
        $users = User::with('university:id,name')
            ->whereIn('role', ['campus_admin', 'super_admin'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json(['data' => $users]);
    }

    // 4. Buat User Baru (Admin Kampus / Super Admin)
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:campus_admin,super_admin',
            // University ID wajib jika role = campus_admin
            'university_id' => 'required_if:role,campus_admin|nullable|exists:universities,id'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'university_id' => $request->role === 'super_admin' ? null : $request->university_id,
            'is_active' => true
        ]);

        return response()->json(['message' => 'User berhasil dibuat', 'data' => $user]);
    }

    // 5. Hapus User
    public function destroyUser($id)
    {
        $user = \App\Models\User::findOrFail($id);
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'Tidak bisa menghapus diri sendiri'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'User berhasil dihapus']);
    }
}