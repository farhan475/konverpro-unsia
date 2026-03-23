<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampusUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requiredRule = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$requiredRule, 'string', 'max:255'],
            'email' => [$requiredRule, 'email', 'max:255'],
            'plan' => [$requiredRule, 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['active', 'pending', 'suspended'])],
            'is_partner' => ['sometimes', 'boolean'],
            'website' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'province' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lecture' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billingMode' => ['sometimes', Rule::in(['subsidy', 'independent'])],
            'regFee' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tuitionFee' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'internalRate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'leadRate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'studyPrograms' => ['sometimes', 'array'],
            'studyPrograms.*.id' => ['nullable', 'string'],
            'studyPrograms.*.name' => ['required_with:studyPrograms', 'string', 'max:255'],
            'studyPrograms.*.level' => ['required_with:studyPrograms', Rule::in(['D3', 'D4', 'S1', 'S2', 'S3'])],
        ];
    }
}
