<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\SaveGlobalSettingsRequest;
use App\Services\SuperAdmin\GlobalSettingService;
use App\Support\ApiResponse;

class GlobalSettingController extends Controller
{
    public function index(GlobalSettingService $service)
    {
        return ApiResponse::success($service->list());
    }

    public function store(SaveGlobalSettingsRequest $request, GlobalSettingService $service)
    {
        $service->save($request->validated('settings'));

        return ApiResponse::success(null, 'Setelan global berhasil disimpan');
    }
}
