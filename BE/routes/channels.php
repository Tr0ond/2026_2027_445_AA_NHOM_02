<?php

use App\Models\PhongHocTrucTuyen;
use App\Models\User;
use App\Services\QuyenPhongService;
use Illuminate\Support\Facades\Broadcast;

// Kênh riêng để lưu thông báo trong database và đẩy realtime tới đúng người nhận.
Broadcast::channel('nguoi-dung.{maTaiKhoan}', function (User $user, int $maTaiKhoan) {
    return $user->id === $maTaiKhoan;
});

// Kênh riêng của từng phòng học: chỉ giảng viên phụ trách và sinh viên đã đăng ký lớp được tham gia.
Broadcast::channel('phong.{maPhong}', function (User $user, string $maPhong) {
    $phong = PhongHocTrucTuyen::where('ma_phong', $maPhong)->first();
    if (! $phong) {
        return false;
    }

    if ($phong->trang_thai !== 'dang_dien_ra' || $user->trang_thai !== 'hoat_dong') {
        return false;
    }

    // Không bắt buộc đã vào video: giữ tương thích với màn hình QR của lớp.
    return $user->laAdmin() || app(QuyenPhongService::class)->duocThamGia($user, $phong);
});
