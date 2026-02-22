<?php

use App\Http\Controllers\Api\Admin\ConversionController as AdminConversionController;
use App\Http\Controllers\Api\ConversionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/conversions', [ConversionController::class, 'store']);
Route::get('/conversions/{id}', [ConversionController::class, 'show']);

// API ADMIN KAMPUS
Route::prefix('admin')->group(function () {
    // List Dashboard
    Route::get('/conversions', [AdminConversionController::class, 'index']);
    
    // Review per Mata Kuliah (Ubah status dari pending/manual -> accepted/rejected)
    Route::post('/review-detail/{detailId}', [AdminConversionController::class, 'reviewDetail']);
    
    // Final Approve (Ketuk Palu satu dokumen)
    Route::post('/finalize/{conversionId}', [AdminConversionController::class, 'finalize']);
});