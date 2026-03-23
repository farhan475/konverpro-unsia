<?php

namespace App\Http\Requests\Campus;

use App\Models\GlobalSetting;
use Illuminate\Foundation\Http\FormRequest;

class StoreTopupRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:' . $this->minimumTopup()],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Nominal top up minimum Rp ' . number_format($this->minimumTopup(), 0, ',', '.'),
        ];
    }

    private function minimumTopup(): int
    {
        return (int) (GlobalSetting::where('key', 'min_topup')->value('value') ?? 500000);
    }
}
