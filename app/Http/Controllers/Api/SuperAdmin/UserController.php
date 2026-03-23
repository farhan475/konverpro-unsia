<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UserUpsertRequest;
use App\Services\SuperAdmin\UserManagementService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(UserManagementService $service)
    {
        return ApiResponse::success($service->list());
    }

    public function store(UserUpsertRequest $request, UserManagementService $service)
    {
        return ApiResponse::success(
            $service->create($request->validated(), $request),
            'User berhasil dibuat',
        );
    }

    public function update(UserUpsertRequest $request, string $id, UserManagementService $service)
    {
        return ApiResponse::success(
            $service->update($id, $request->validated(), $request),
            'User berhasil diupdate',
        );
    }

    public function destroy(Request $request, string $id, UserManagementService $service)
    {
        $service->delete($id, $request);

        return ApiResponse::success(null, 'User berhasil dihapus');
    }
}
