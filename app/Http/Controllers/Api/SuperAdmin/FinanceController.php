<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\ProcessTopupRequest;
use App\Services\SuperAdmin\TopupManagementService;
use App\Support\ApiResponse;

class FinanceController extends Controller
{
    public function indexTopups(TopupManagementService $service)
    {
        return ApiResponse::success($service->list());
    }

    public function processTopup(
        ProcessTopupRequest $request,
        string $id,
        TopupManagementService $service,
    ) {
        $service->process($id, (string) $request->validated('status'), $request);

        return ApiResponse::success(null, 'Topup berhasil diproses');
    }
}
