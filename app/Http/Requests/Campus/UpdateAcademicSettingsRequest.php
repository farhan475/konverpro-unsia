<?php

namespace App\Http\Requests\Campus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kaprodiName' => ['required', 'string', 'max:255'],
            'kaprodiTitle' => ['required', 'string', 'max:255'],
            'letterFormat' => ['required', 'string', 'max:100'],
            'minPassingGrade' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
            'maxAcceptedSks' => ['required', 'integer', 'min:1', 'max:200'],
            'maxStudyYears' => ['required', 'integer', 'min:1', 'max:14'],
            'semesterRules' => ['required', 'array', 'min:1'],
            'semesterRules.*.semester' => ['required', 'integer', 'min:1'],
            'semesterRules.*.maxSks' => ['required', 'integer', 'min:1', 'max:30'],
            'requiredCourses' => ['nullable', 'array'],
            'requiredCourses.*.id' => ['nullable', 'string'],
            'requiredCourses.*.code' => ['nullable', 'string'],
            'requiredCourses.*.name' => ['nullable', 'string'],
            'requiredCourses.*.semester' => ['nullable', 'integer', 'min:1'],
            'requiredCourses.*.sks' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'signatureDataUrl' => ['nullable', 'string'],
        ];
    }
}
