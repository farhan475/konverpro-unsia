<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationTemplateUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $templateId = (string) $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'trigger' => [
                'required',
                'string',
                'max:255',
                Rule::unique('notification_templates', 'trigger')->ignore($templateId),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ];
    }
}
