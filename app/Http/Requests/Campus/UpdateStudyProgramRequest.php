<?php

namespace App\Http\Requests\Campus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:100'],
            'level' => ['sometimes', 'required', Rule::in(['D3', 'D4', 'S1', 'S2'])],
            'settings' => ['nullable', 'array'],
        ];
    }
}
