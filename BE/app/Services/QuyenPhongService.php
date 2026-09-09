<?php

namespace App\Services;

use App\Models\LichHoc;
use App\Models\PhongHocTrucTuyen;
use App\Models\ThanhVienPhongTrucTuyen;
use App\Models\User;

class QuyenPhongService
{
    public function phuTrach(User $user, LichHoc $lichHoc): bool
    {
        return $user->trang_thai === 'hoat_dong'
            && $user->laGiangVien()
            && $user->giangVien
            && $lichHoc->lopHoc?->phanCong()
                ->where('ma_giang_vien', $user->giangVien->id)->exists();
    }

    public function duocThamGia(User $user, PhongHocTrucTuyen $phong): bool
    {
        if ($user->trang_thai !== 'hoat_dong' || ! $phong->lichHoc) {
            return false;
        }

        if ($user->laGiangVien()) {
            return $this->phuTrach($user, $phong->lichHoc);
        }

        return $user->laSinhVien()
            && $user->sinhVien
            && $phong->lichHoc->lopHoc?->dangKy()
                ->where('ma_sinh_vien', $user->sinhVien->id)
                ->where('trang_thai', 'da_duyet')->exists();
    }

    public function kiemTraQuanLy(User $user, PhongHocTrucTuyen $phong, bool $choAdmin = false): void
    {
        abort_unless(
            ($choAdmin && $user->laAdmin() && $user->trang_thai === 'hoat_dong')
            || ($phong->lichHoc && $this->phuTrach($user, $phong->lichHoc)),
            403, 'Bạn không có quyền quản lý phòng học này.',
        );
    }

    public function thanhVienDangThamGia(User $user, PhongHocTrucTuyen $phong): ThanhVienPhongTrucTuyen
    {
        abort_unless($this->duocThamGia($user, $phong), 403, 'Bạn không thuộc lớp học này.');
        abort_unless($phong->trang_thai === 'dang_dien_ra', 422, 'Phòng học đã kết thúc.');

        $thanhVien = $phong->thanhVien()->where('ma_tai_khoan', $user->id)
            ->whereNull('thoi_gian_roi')->first();

        abort_unless($thanhVien, 403, 'Bạn chưa tham gia hoặc đã rời phòng học này.');

        return $thanhVien;
    }
}
