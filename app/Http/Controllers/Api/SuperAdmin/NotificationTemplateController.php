<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\NotificationTemplateUpsertRequest;
use App\Services\SuperAdmin\NotificationTemplateService;
use App\Support\ApiResponse;

class NotificationTemplateController extends Controller
{
    public function index(NotificationTemplateService $service)
    {
        return ApiResponse::success($service->list());
    }

    public function store(NotificationTemplateUpsertRequest $request, NotificationTemplateService $service)
    {
        return ApiResponse::success(
            $service->create($request->validated()),
            'Template berhasil dibuat',
        );
    }

    public function update(
        NotificationTemplateUpsertRequest $request,
        string $id,
        NotificationTemplateService $service,
    ) {
        return ApiResponse::success(
            $service->update($id, $request->validated()),
            'Template berhasil diupdate',
        );
    }

    public function destroy(string $id, NotificationTemplateService $service)
    {
        $service->delete($id);

        return ApiResponse::success(null, 'Template berhasil dihapus');
    }
}
