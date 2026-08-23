<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DiemDanhSinhVienController;
use App\Http\Controllers\Api\PhienDiemDanhController;
use App\Http\Controllers\Api\QuanLyDiemDanhController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Xác thực nền và điểm danh QR
|--------------------------------------------------------------------------
*/

// Xác thực private channel bằng Sanctum token.
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Phần xác thực nền của Vinh đã có trên main.
Route::post('/dang-nhap', [AuthController::class, 'dangNhap']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dang-xuat', [AuthController::class, 'dangXuat']);
    Route::get('/me', [AuthController::class, 'me']);

    // Phần sinh viên của Cường.
    Route::middleware('vai_tro:sinh_vien')->prefix('sinh-vien')->group(function () {
        Route::post('/diem-danh/qr/{maQr}', [DiemDanhSinhVienController::class, 'quetQr']);
        Route::get('/lich-su-diem-danh', [DiemDanhSinhVienController::class, 'lichSu']);
    });

    // Phần giảng viên quản lý điểm danh của Cường.
    Route::middleware('vai_tro:giang_vien,admin')->group(function () {
        Route::middleware('vai_tro:giang_vien')->prefix('giang-vien/diem-danh')->group(function () {
            Route::get('/lop/{lopHoc}/lich-hoc', [QuanLyDiemDanhController::class, 'lichHocCuaLop']);
            Route::get('/lich-hoc/{lichHoc}', [QuanLyDiemDanhController::class, 'show']);
            Route::put('/lich-hoc/{lichHoc}', [QuanLyDiemDanhController::class, 'update']);
        });

        Route::post('/phien-diem-danh', [PhienDiemDanhController::class, 'store']);
        Route::get('/phien-diem-danh/{phien}/qr-token', [PhienDiemDanhController::class, 'tokenQr']);
        Route::get('/phien-diem-danh/ma-phien/{maPhien}/danh-sach', [PhienDiemDanhController::class, 'danhSachTheoMa']);
        Route::get('/phien-diem-danh/{phien}/danh-sach', [PhienDiemDanhController::class, 'danhSach']);
        Route::put('/phien-diem-danh/{phien}/trang-thai', [PhienDiemDanhController::class, 'suaTrangThai']);
        Route::post('/phien-diem-danh/{phien}/diem-danh-thu-cong', [PhienDiemDanhController::class, 'diemDanhThuCong']);
        Route::post('/phien-diem-danh/{phien}/dong', [PhienDiemDanhController::class, 'dong']);
    });
});
