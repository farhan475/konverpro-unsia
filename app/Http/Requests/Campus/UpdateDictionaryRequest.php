<?php

namespace App\Http\Requests\Campus;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDictionaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
