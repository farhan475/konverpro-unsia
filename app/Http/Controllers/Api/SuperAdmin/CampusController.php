<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\AdjustCampusBalanceRequest;
use App\Http\Requests\SuperAdmin\CampusUpsertRequest;
use App\Services\SuperAdmin\CampusManagementService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CampusController extends Controller
{
    public function index(CampusManagementService $service)
    {
        return ApiResponse::success($service->list());
    }

    public function store(CampusUpsertRequest $request, CampusManagementService $service)
    {
        return ApiResponse::created(
            $service->create($request->validated(), $request),
            'Kampus berhasil didaftarkan',
        );
    }

    public function update(CampusUpsertRequest $request, string $id, CampusManagementService $service)
    {
        return ApiResponse::success(
            $service->update($id, $request->validated(), $request),
            'Kampus berhasil diupdate',
        );
    }

    public function destroy(Request $request, string $id, CampusManagementService $service)
    {
        $service->delete($id, $request);

        return ApiResponse::success(null, 'Kampus berhasil dihapus');
    }

    public function adjustBalance(
        AdjustCampusBalanceRequest $request,
        string $id,
        CampusManagementService $service,
    ) {
        return ApiResponse::success(
            null,
            'Saldo berhasil diupdate',
            200,
            [
                'balance' => $service->adjustBalance(
                    $id,
                    (float) $request->validated('amount'),
                    $request,
                ),
            ],
        );
    }
}
