<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpsertRequest extends FormRequest
{
    private const ROLE_MAP = [
        'Super Admin' => 'super_admin',
        'Admin Kampus' => 'campus_admin',
        'Admin Prodi' => 'prodi_admin',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $role = $this->input('role');

        if (is_string($role) && array_key_exists($role, self::ROLE_MAP)) {
            $this->merge([
                'role' => self::ROLE_MAP[$role],
            ]);
        }
    }

    public function rules(): array
    {
        $userId = (string) $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role' => ['required', Rule::in(['super_admin', 'campus_admin', 'prodi_admin'])],
            'university_id' => [
                Rule::requiredIf(fn (): bool => in_array($this->input('role'), ['campus_admin', 'prodi_admin'], true)),
                'nullable',
                'exists:universities,id',
            ],
        ];
    }
}
