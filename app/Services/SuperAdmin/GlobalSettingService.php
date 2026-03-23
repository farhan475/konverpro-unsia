<?php

namespace App\Services\SuperAdmin;

use App\Models\GlobalSetting;
use Illuminate\Support\Collection;

class GlobalSettingService
{
    public function list(): Collection
    {
        return GlobalSetting::query()->pluck('value', 'key');
    }

    public function save(array $settings): void
    {
        foreach ($settings as $key => $value) {
            GlobalSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
