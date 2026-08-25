<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DiemDanhSinhVienController;
use App\Http\Controllers\Api\LichHocController;
use App\Http\Controllers\Api\LopDayController;
use App\Http\Controllers\Api\LopHocController;
use App\Http\Controllers\Api\PhienDiemDanhController;
use App\Http\Controllers\Api\QuanLyDiemDanhController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - MVP
|--------------------------------------------------------------------------
| Xác thực, lớp học, lịch học và điểm danh QR.
*/

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::post('/dang-nhap', [AuthController::class, 'dangNhap']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dang-xuat', [AuthController::class, 'dangXuat']);
    Route::get('/me', [AuthController::class, 'me']);

    // Lịch học của người dùng đang đăng nhập.
    Route::get('/lich-hoc', [LichHocController::class, 'index']);

    // Sinh viên: lớp học, đăng ký lớp và điểm danh.
    Route::middleware('vai_tro:sinh_vien')->prefix('sinh-vien')->group(function () {
        Route::get('/lop-hoc-mo', [LopHocController::class, 'danhSachMo']);
        Route::get('/lop-cua-toi', [LopHocController::class, 'lopCuaToi']);
        Route::post('/dang-ky-lop/{lopHoc}', [LopHocController::class, 'dangKy']);
        Route::post('/huy-dang-ky/{lopHoc}', [LopHocController::class, 'huyDangKy']);

        Route::post('/diem-danh/qr/{maQr}', [DiemDanhSinhVienController::class, 'quetQr']);
        Route::get('/lich-su-diem-danh', [DiemDanhSinhVienController::class, 'lichSu']);
    });

    // Giảng viên: lớp phụ trách, lịch dạy và điều chỉnh điểm danh.
    Route::middleware('vai_tro:giang_vien')->group(function () {
        Route::get('/lop-day', [LopDayController::class, 'index']);
        Route::get('/lop-day/buoi-hoc', [LopDayController::class, 'buoiHoc']);

        Route::prefix('giang-vien/diem-danh')->group(function () {
            Route::get('/lop/{lopHoc}/lich-hoc', [QuanLyDiemDanhController::class, 'lichHocCuaLop']);
            Route::get('/lich-hoc/{lichHoc}', [QuanLyDiemDanhController::class, 'show']);
            Route::put('/lich-hoc/{lichHoc}', [QuanLyDiemDanhController::class, 'update']);
        });
    });

    // Giảng viên hoặc admin: quản lý phiên QR.
    Route::middleware('vai_tro:giang_vien,admin')->group(function () {
        Route::post('/phien-diem-danh', [PhienDiemDanhController::class, 'store']);
        Route::get('/phien-diem-danh/{phien}/qr-token', [PhienDiemDanhController::class, 'tokenQr']);
        Route::get('/phien-diem-danh/ma-phien/{maPhien}/danh-sach', [PhienDiemDanhController::class, 'danhSachTheoMa']);
        Route::get('/phien-diem-danh/{phien}/danh-sach', [PhienDiemDanhController::class, 'danhSach']);
        Route::put('/phien-diem-danh/{phien}/trang-thai', [PhienDiemDanhController::class, 'suaTrangThai']);
        Route::post('/phien-diem-danh/{phien}/diem-danh-thu-cong', [PhienDiemDanhController::class, 'diemDanhThuCong']);
        Route::post('/phien-diem-danh/{phien}/dong', [PhienDiemDanhController::class, 'dong']);
    });
});

