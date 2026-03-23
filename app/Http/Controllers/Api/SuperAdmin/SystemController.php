<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\ImportBackupRequest;
use App\Services\SuperAdmin\SystemBackupService;
use App\Support\ApiResponse;

class SystemController extends Controller
{
    public function exportData(SystemBackupService $service)
    {
        $payload = $service->export();
        $fileName = 'backup_konverpro_'.now()->format('Ymd_His').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function importData(ImportBackupRequest $request, SystemBackupService $service)
    {
        $summary = $service->import($request->file('backup_file'));

        return ApiResponse::success($summary, 'Restorasi data parsial berhasil dijalankan');
    }
}
