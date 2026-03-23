<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class ImportBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'backup_file' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'backup_file.required' => 'File backup wajib dipilih terlebih dahulu.',
            'backup_file.file' => 'Upload backup tidak valid.',
            'backup_file.mimetypes' => 'File backup harus berupa JSON hasil ekspor sistem.',
            'backup_file.max' => 'Ukuran file backup maksimal 10 MB.',
        ];
    }
}
