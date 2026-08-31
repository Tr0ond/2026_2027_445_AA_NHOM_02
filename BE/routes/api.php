<?php

use App\Http\Controllers\Api\Admin\ThanhPhanController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DiemDanhSinhVienController;
use App\Http\Controllers\Api\DiemHocTapController;
use App\Http\Controllers\Api\DonXinPhepController;
use App\Http\Controllers\Api\LichHocController;
use App\Http\Controllers\Api\LopDayController;
use App\Http\Controllers\Api\LopHocController;
use App\Http\Controllers\Api\PhienDiemDanhController;
use App\Http\Controllers\Api\PhongHocController;
use App\Http\Controllers\Api\QuanLyDiemDanhController;
use App\Http\Controllers\Api\TinNhanPhongController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - MVP + điểm học tập, đơn xin phép và phòng học trực tuyến
|--------------------------------------------------------------------------
| Giữ API đã tích hợp; phần Cường chỉ bổ sung nhóm /phong.
*/

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::post('/dang-nhap', [AuthController::class, 'dangNhap']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dang-xuat', [AuthController::class, 'dangXuat']);
    Route::get('/me', [AuthController::class, 'me']);

    // Lịch học của người dùng đang đăng nhập.
    Route::get('/lich-hoc', [LichHocController::class, 'index']);

    // Đơn xin phép: sinh viên gửi, giảng viên/admin xử lý.
    Route::get('/xin-phep', [DonXinPhepController::class, 'index']);
    Route::post('/xin-phep', [DonXinPhepController::class, 'store'])
        ->middleware('vai_tro:sinh_vien');
    Route::post('/xin-phep/{don}/duyet', [DonXinPhepController::class, 'duyet'])
        ->middleware('vai_tro:giang_vien,admin');

    // Sinh viên: lớp học, đăng ký lớp, điểm danh và xem điểm.
    Route::middleware('vai_tro:sinh_vien')->prefix('sinh-vien')->group(function () {
        Route::get('/lop-hoc-mo', [LopHocController::class, 'danhSachMo']);
        Route::get('/lop-cua-toi', [LopHocController::class, 'lopCuaToi']);
        Route::post('/dang-ky-lop/{lopHoc}', [LopHocController::class, 'dangKy']);
        Route::post('/huy-dang-ky/{lopHoc}', [LopHocController::class, 'huyDangKy']);

        Route::post('/diem-danh/qr/{maQr}', [DiemDanhSinhVienController::class, 'quetQr']);
        Route::get('/lich-su-diem-danh', [DiemDanhSinhVienController::class, 'lichSu']);
        Route::get('/diem', [DiemHocTapController::class, 'diemCuaToi']);
    });

    // Cường: phòng học trực tuyến, Agora và tương tác realtime.
    Route::prefix('phong')->middleware('vai_tro:giang_vien,sinh_vien,admin')->group(function () {
        Route::post('/bat-dau', [PhongHocController::class, 'batDau'])
            ->middleware('vai_tro:giang_vien');
        Route::post('/{maPhong}/tham-gia', [PhongHocController::class, 'thamGia']);
        Route::post('/{maPhong}/roi', [PhongHocController::class, 'roiPhong']);
        Route::get('/{maPhong}/thanh-vien', [PhongHocController::class, 'thanhVien']);
        Route::post('/{maPhong}/ket-thuc', [PhongHocController::class, 'ketThuc'])
            ->middleware('vai_tro:giang_vien,admin');
        Route::get('/{maPhong}/tin-nhan', [TinNhanPhongController::class, 'index']);
        Route::post('/{maPhong}/tin-nhan', [TinNhanPhongController::class, 'store']);
        Route::post('/{maPhong}/gio-tay', [PhongHocController::class, 'gioTay'])
            ->middleware('vai_tro:sinh_vien');
        Route::post('/{maPhong}/cap-quyen', [PhongHocController::class, 'capQuyen'])
            ->middleware('vai_tro:giang_vien');
        Route::post('/{maPhong}/chia-se-trang-thai', [PhongHocController::class, 'chiaSeTrangThai']);
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

    // Giảng viên hoặc admin: quản lý phiên QR và điểm học tập.
    Route::middleware('vai_tro:giang_vien,admin')->group(function () {
        Route::post('/phien-diem-danh', [PhienDiemDanhController::class, 'store']);
        Route::get('/phien-diem-danh/{phien}/qr-token', [PhienDiemDanhController::class, 'tokenQr']);
        Route::get('/phien-diem-danh/ma-phien/{maPhien}/danh-sach', [PhienDiemDanhController::class, 'danhSachTheoMa']);
        Route::get('/phien-diem-danh/{phien}/danh-sach', [PhienDiemDanhController::class, 'danhSach']);
        Route::put('/phien-diem-danh/{phien}/trang-thai', [PhienDiemDanhController::class, 'suaTrangThai']);
        Route::post('/phien-diem-danh/{phien}/diem-danh-thu-cong', [PhienDiemDanhController::class, 'diemDanhThuCong']);
        Route::post('/phien-diem-danh/{phien}/dong', [PhienDiemDanhController::class, 'dong']);

        Route::get('/lop-hoc/{lopHoc}/diem', [DiemHocTapController::class, 'bangDiemLop']);
        Route::post('/luu-diem', [DiemHocTapController::class, 'luuDiem']);
        Route::post('/lop-hoc/{lopHoc}/dong-bo-chuyen-can', [DiemHocTapController::class, 'dongBoChuyenCan']);
        Route::get('/lop-hoc/{lopHoc}/thanh-phan', [DiemHocTapController::class, 'thanhPhanCuaLop']);
    });

    // Admin: cấu hình điểm thành phần theo lớp.
    Route::middleware('vai_tro:admin')->prefix('admin')->group(function () {
        Route::get('/lop-hoc/{lopHoc}/thanh-phan', [ThanhPhanController::class, 'index']);
        Route::post('/lop-hoc/{lopHoc}/thanh-phan', [ThanhPhanController::class, 'store']);
        Route::put('/thanh-phan/{thanhPhan}', [ThanhPhanController::class, 'update']);
        Route::delete('/thanh-phan/{thanhPhan}', [ThanhPhanController::class, 'destroy']);
    });
});
