<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveGlobalSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.internal_rate' => ['nullable', 'numeric', 'min:0'],
            'settings.lead_rate' => ['nullable', 'numeric', 'min:0'],
            'settings.partner_surcharge' => ['nullable', 'numeric', 'min:0'],
            'settings.tax' => ['nullable', 'numeric', 'min:0'],
            'settings.min_topup' => ['nullable', 'numeric', 'min:0'],
            'settings.maintenance_mode' => ['nullable', 'boolean'],
            'settings.smtp_host' => ['nullable', 'string', 'max:255'],
            'settings.smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'settings.smtp_username' => ['nullable', 'string', 'max:255'],
            'settings.smtp_password' => ['nullable', 'string', 'max:255'],
            'settings.smtp_encryption' => ['nullable', Rule::in(['ssl', 'tls', 'starttls', 'none'])],
            'settings.mail_from_name' => ['nullable', 'string', 'max:255'],
            'settings.mail_from_address' => ['nullable', 'email', 'max:255'],
            'settings.support_email' => ['nullable', 'email', 'max:255'],
            'settings.support_whatsapp' => ['nullable', 'string', 'max:50'],
        ];
    }
}
