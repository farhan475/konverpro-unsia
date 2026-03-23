<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Email atau password salah.');
        }

        if (!in_array($user->role, ['super_admin', 'campus_admin', 'prodi_admin'], true)) {
            throw new AuthorizationException('Akun Anda tidak memiliki akses ke dashboard ini.');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'university_id' => $user->university_id,
            ],
        ];
    }
}
