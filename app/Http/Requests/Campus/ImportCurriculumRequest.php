<?php

namespace App\Http\Requests\Campus;

use Illuminate\Foundation\Http\FormRequest;

class ImportCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'study_program_id' => ['required', 'exists:study_programs,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ];
    }
}
