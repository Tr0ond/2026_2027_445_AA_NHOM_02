<?php

use App\Models\PhongHocTrucTuyen;
use App\Models\User;
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

    $lopHoc = $phong->lichHoc?->lopHoc;
    if (! $lopHoc) {
        return false;
    }

    if ($user->laGiangVien()) {
        $giangVien = $user->giangVien;

        return $giangVien
            && $lopHoc->phanCong()->where('ma_giang_vien', $giangVien->id)->exists();
    }

    if ($user->laSinhVien()) {
        $sinhVien = $user->sinhVien;

        return $sinhVien
            && $lopHoc->dangKy()
                ->where('ma_sinh_vien', $sinhVien->id)
                ->where('trang_thai', 'da_duyet')
                ->exists();
    }

    return $user->laAdmin();
});
