<?php

namespace App\Services\SuperAdmin;

use App\Models\User;
use App\Support\AuditLogSupport;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UserManagementService
{
    public function list(): Collection
    {
        return User::query()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'university_id',
                'created_at',
                'updated_at',
            ])
            ->with('university:id,name')
            ->whereIn('role', ['campus_admin', 'prodi_admin', 'super_admin'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $validated, Request $request): User
    {
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('password123'),
            'role' => $validated['role'],
            'university_id' => $validated['role'] === 'super_admin'
                ? null
                : ($validated['university_id'] ?? null),
            'is_active' => true,
        ]);

        AuditLogSupport::record(
            $request,
            'user.created',
            $user,
            [],
            $user->only(['id', 'name', 'email', 'role', 'university_id']),
        );

        return $user->load('university:id,name');
    }

    public function update(string $id, array $validated, Request $request): User
    {
        $user = User::findOrFail($id);
        $before = $user->only(['id', 'name', 'email', 'role', 'university_id']);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'university_id' => $validated['role'] === 'super_admin'
                ? null
                : ($validated['university_id'] ?? null),
        ]);

        AuditLogSupport::record(
            $request,
            'user.updated',
            $user,
            $before,
            $user->fresh()->only(['id', 'name', 'email', 'role', 'university_id']),
        );

        return $user->fresh()->load('university:id,name');
    }

    public function delete(string $id, Request $request): void
    {
        $user = User::findOrFail($id);

        if ((string) $user->id === (string) $request->user()?->id) {
            throw new AuthorizationException('Tidak bisa menghapus diri sendiri');
        }

        $snapshot = $user->only(['id', 'name', 'email', 'role', 'university_id']);
        $user->delete();

        AuditLogSupport::record($request, 'user.deleted', $user, $snapshot, []);
    }
}
