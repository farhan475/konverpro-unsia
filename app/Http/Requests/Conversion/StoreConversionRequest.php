<?php

namespace App\Http\Requests\Conversion;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'university_id' => ['required', 'exists:universities,id'],
            'study_program_id' => ['required', 'exists:study_programs,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source_campus' => ['nullable', 'string', 'max:255'],
        ];
    }
}
