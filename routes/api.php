<?php

use App\Http\Controllers\Api\Admin\ConversionController as AdminConversionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampusCurriculumController;
use App\Http\Controllers\Api\CampusSettingsController;
use App\Http\Controllers\Api\ConversionController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\SuperAdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- PUBLIC ROUTES ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('/conversions', [ConversionController::class, 'store']);
Route::get('/conversions/{id}', [ConversionController::class, 'show']);
Route::get('/public/campuses', [PublicController::class, 'getActiveCampuses']);


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
    });

    // 2. BLOK MANAJEMEN KURIKULUM (Tanpa prefix /admin)
    Route::get('/curriculum/prodi', [CampusCurriculumController::class, 'getProdi']);
    Route::get('/curriculum/prodi/{prodiId}/courses', [CampusCurriculumController::class, 'getCourses']);
    Route::post('/curriculum/import', [CampusCurriculumController::class, 'import']);

     // 3. BLOK SUPER ADMIN
    Route::prefix('super-admin')->group(    function () {
        Route::get('/campuses', [SuperAdminController::class, 'getCampuses']);
        Route::post('/campuses/{id}/topup', [SuperAdminController::class, 'topupBalance']);
         Route::get('/users', [SuperAdminController::class, 'getUsers']);
        Route::post('/users', [SuperAdminController::class, 'storeUser']);
        Route::delete('/users/{id}', [SuperAdminController::class, 'destroyUser']);
    });

    Route::prefix('campus/settings')->group(function () {
    Route::get('/profile', [CampusSettingsController::class, 'getProfile']);
    Route::post('/profile', [CampusSettingsController::class, 'updateProfile']);
    
    Route::get('/prodi', [CampusSettingsController::class, 'getProdis']);
    Route::post('/prodi', [CampusSettingsController::class, 'storeProdi']);
    Route::delete('/prodi/{id}', [CampusSettingsController::class, 'destroyProdi']);
    
    Route::get('/dictionary', [CampusSettingsController::class, 'getDictionary']);
    Route::put('/dictionary/{courseId}', [CampusSettingsController::class, 'updateDictionary']);
});
    
});