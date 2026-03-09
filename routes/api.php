<?php

use App\Http\Controllers\Api\Admin\ConversionController as AdminConversionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampusCurriculumController;
use App\Http\Controllers\Api\CampusSettingsController;
use App\Http\Controllers\Api\ConversionController;
use App\Http\Controllers\Api\PublicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- PUBLIC ROUTES ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('/conversions', [ConversionController::class, 'store']);
Route::get('/conversions/{id}', [ConversionController::class, 'show']);
Route::get('/public/campuses', [PublicController::class, 'getActiveCampuses']);
Route::get('/public/marketplace', [PublicController::class, 'getMarketplaceData']);
Route::post('/public/simulate', [\App\Http\Controllers\Api\SimulationController::class, 'simulate']);
Route::get('/public/template', [PublicController::class, 'downloadTemplate']);


// --- PROTECTED ROUTES (Butuh Login/Token) ---
Route::middleware('auth:sanctum')->group(function () {

    // Auth & User
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // 1. BLOK ADMIN KONVERSI (Pakai prefix /admin)
    Route::prefix('admin')->group(function () {
        Route::get('/conversions', [AdminConversionController::class, 'index']);
        Route::post('/review-detail/{detailId}', [AdminConversionController::class, 'reviewDetail']);
        Route::post('/finalize/{conversionId}', [AdminConversionController::class, 'finalize']);
        Route::get('/dashboard-stats', [AdminConversionController::class, 'getDashboardStats']);
    });

    // 2. BLOK MANAJEMEN KURIKULUM (Tanpa prefix /admin)
    Route::get('/curriculum/prodi', [CampusCurriculumController::class, 'getProdi']);
    Route::get('/curriculum/prodi/{prodiId}/courses', [CampusCurriculumController::class, 'getCourses']);
    Route::post('/curriculum/import', [CampusCurriculumController::class, 'import']);

    // 3. BLOK SUPER ADMIN
    Route::prefix('super-admin')->group(function () {
        // Campus Management
        Route::get('/campuses', [\App\Http\Controllers\Api\SuperAdmin\CampusController::class, 'index']);
        Route::post('/campuses', [\App\Http\Controllers\Api\SuperAdmin\CampusController::class, 'store']);
        Route::put('/campuses/{id}', [\App\Http\Controllers\Api\SuperAdmin\CampusController::class, 'update']);
        Route::delete('/campuses/{id}', [\App\Http\Controllers\Api\SuperAdmin\CampusController::class, 'destroy']);
        Route::post('/campuses/{id}/adjust-balance', [\App\Http\Controllers\Api\SuperAdmin\CampusController::class, 'adjustBalance']);

        // User Management
        Route::get('/users', [\App\Http\Controllers\Api\SuperAdmin\UserController::class, 'index']);
        Route::post('/users', [\App\Http\Controllers\Api\SuperAdmin\UserController::class, 'store']);
        Route::put('/users/{id}', [\App\Http\Controllers\Api\SuperAdmin\UserController::class, 'update']);
        Route::delete('/users/{id}', [\App\Http\Controllers\Api\SuperAdmin\UserController::class, 'destroy']);

        // Finance & Topup
        Route::get('/topups', [\App\Http\Controllers\Api\SuperAdmin\FinanceController::class, 'indexTopups']);
        Route::post('/topups/{id}/process', [\App\Http\Controllers\Api\SuperAdmin\FinanceController::class, 'processTopup']);

        // Global Settings
        Route::get('/settings', [\App\Http\Controllers\Api\SuperAdmin\GlobalSettingController::class, 'index']);
        Route::post('/settings', [\App\Http\Controllers\Api\SuperAdmin\GlobalSettingController::class, 'store']);

        // Notification Templates
        Route::get('/notification-templates', [\App\Http\Controllers\Api\SuperAdmin\NotificationTemplateController::class, 'index']);
        Route::post('/notification-templates', [\App\Http\Controllers\Api\SuperAdmin\NotificationTemplateController::class, 'store']);
        Route::put('/notification-templates/{id}', [\App\Http\Controllers\Api\SuperAdmin\NotificationTemplateController::class, 'update']);
        Route::delete('/notification-templates/{id}', [\App\Http\Controllers\Api\SuperAdmin\NotificationTemplateController::class, 'destroy']);

        // System Backup/Restore
        Route::get('/system/backup', [\App\Http\Controllers\Api\SuperAdmin\SystemController::class, 'exportData']);
        Route::post('/system/restore', [\App\Http\Controllers\Api\SuperAdmin\SystemController::class, 'importData']);

        // Audit Logs & Reporting
        Route::get('/reports/audit-logs', [\App\Http\Controllers\Api\SuperAdmin\ReportController::class, 'getAuditLogs']);
        Route::get('/reports/revenue', [\App\Http\Controllers\Api\SuperAdmin\ReportController::class, 'getRevenueChart']);
    });

    Route::prefix('campus/settings')->group(function () {
        Route::get('/profile', [CampusSettingsController::class, 'getProfile']);
        Route::post('/profile', [CampusSettingsController::class, 'updateProfile']);

        Route::get('/prodi', [CampusSettingsController::class, 'getProdis']);
        Route::post('/prodi', [CampusSettingsController::class, 'storeProdi']);
        Route::put('/prodi/{id}', [CampusSettingsController::class, 'updateProdi']);
        Route::delete('/prodi/{id}', [CampusSettingsController::class, 'destroyProdi']);

        Route::get('/dictionary', [CampusSettingsController::class, 'getDictionary']);
        Route::put('/dictionary/{courseId}', [CampusSettingsController::class, 'updateDictionary']);

        Route::get('/billing-history', [CampusSettingsController::class, 'getBillingHistory']);
    });
});