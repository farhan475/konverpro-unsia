<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('university:id,name')
            ->whereIn('role', ['campus_admin', 'prodi_admin', 'super_admin'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json(['data' => $users]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string',
            'university_id' => 'nullable|exists:universities,id'
        ]);

        // Mapped roles if needed
        $roleMap = [
            'Super Admin' => 'super_admin',
            'Admin Kampus' => 'campus_admin',
            'Admin Prodi' => 'prodi_admin',
        ];
        
        $role = $roleMap[$request->role] ?? $request->role;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('password123'), // Default password
            'role' => $role,
            'university_id' => $role === 'super_admin' ? null : $request->university_id,
            'is_active' => true
        ]);

        return response()->json(['message' => 'User berhasil dibuat', 'data' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|string',
            'university_id' => 'nullable|exists:universities,id'
        ]);

        $roleMap = [
            'Super Admin' => 'super_admin',
            'Admin Kampus' => 'campus_admin',
            'Admin Prodi' => 'prodi_admin',
        ];
        $role = $roleMap[$request->role] ?? $request->role;

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $role,
            'university_id' => $role === 'super_admin' ? null : $request->university_id,
        ]);

        return response()->json(['message' => 'User berhasil diupdate', 'data' => $user]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Tidak bisa menghapus diri sendiri'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'User berhasil dihapus']);
    }
}
