<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Hệ thống học tập trực tuyến & điểm danh QR
|--------------------------------------------------------------------------
*/

// ---- US01: Đăng nhập (không cần token) ----
Route::post('/dang-nhap', [AuthController::class, 'dangNhap']);

// ---- Các route cần đăng nhập ----
Route::middleware('auth:sanctum')->group(function () {
    // US02: Đăng xuất
    Route::post('/dang-xuat', [AuthController::class, 'dangXuat']);
    Route::get('/me', [AuthController::class, 'me']);
});
