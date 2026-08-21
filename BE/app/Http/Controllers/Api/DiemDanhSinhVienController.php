<?php

namespace App\Http\Controllers\Api;

use App\Events\DiemDanhThanhCong;
use App\Http\Controllers\Controller;
use App\Models\ChiTietDiemDanh as ChiTietDiemDanhModel;
use App\Models\DangKyLopHoc;
use App\Models\MaQrToken;
use App\Models\PhongHocTrucTuyen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiemDanhSinhVienController extends Controller
{
    /**
     * US08 - Sinh viên quét mã QR: điện thoại gọi API này kèm token và chuỗi mã QR.
     * Bước 5 trong luồng: kiểm tra phiên, thời gian, đăng ký lớp rồi ghi chi_tiet_diem_danh.
     */
    public function quetQr(Request $request, string $maQr): JsonResponse
    {
        $user = $request->user();
        $sinhVien = $user->sinhVien;

        if (! $sinhVien) {
            return response()->json(['message' => 'Tài khoản không phải sinh viên.'], 403);
        }

        $qrToken = MaQrToken::with(['phien.lichHoc.lopHoc.monHoc', 'phien.chiTiet'])
            ->where('token', $maQr)
            ->where('het_han_luc', '>', now())
            ->first();
        $phien = $qrToken?->phien;

        if (! $phien) {
            return response()->json([
                'thanh_cong' => false,
                'message' => 'Mã QR không hợp lệ hoặc phiên điểm danh không tồn tại.',
            ], 404);
        }

        if ($phien->trang_thai !== 'dang_mo') {
            return response()->json([
                'thanh_cong' => false,
                'message' => 'Phiên điểm danh đã đóng.',
            ], 422);
        }

        $now = now();
        if (! $now->between($phien->thoi_gian_bat_dau, $phien->thoi_gian_ket_thuc)) {
            return response()->json([
                'thanh_cong' => false,
                'message' => 'Đã quá thời gian điểm danh của phiên này.',
            ], 422);
        }

        $lopHoc = $phien->lichHoc?->lopHoc;
        $daDangKy = DangKyLopHoc::where('ma_sinh_vien', $sinhVien->id)
            ->where('ma_lop_hoc', $lopHoc?->id)
            ->where('trang_thai', 'da_duyet')
            ->exists();

        if (! $daDangKy) {
            return response()->json([
                'thanh_cong' => false,
                'message' => 'Bạn không nằm trong danh sách lớp học của phiên điểm danh này.',
            ], 403);
        }

        $daDiemDanh = ChiTietDiemDanhModel::where('ma_phien_diem_danh', $phien->id)
            ->where('ma_sinh_vien', $sinhVien->id)
            ->exists();

        if ($daDiemDanh) {
            return response()->json([
                'thanh_cong' => true,
                'da_diem_danh_truoc_do' => true,
                'message' => 'Bạn đã điểm danh phiên này rồi.',
                'thoi_gian_diem_danh' => $phien->chiTiet
                    ->firstWhere('ma_sinh_vien', $sinhVien->id)
                    ?->thoi_gian_diem_danh?->format('H:i d/m/Y'),
            ]);
        }

        // Bước 5: lưu kết quả điểm danh
        ChiTietDiemDanhModel::create([
            'ma_phien_diem_danh' => $phien->id,
            'ma_sinh_vien' => $sinhVien->id,
            'trang_thai_diem_danh' => 'co_mat',
            'thoi_gian_diem_danh' => $now,
            'hinh_thuc_diem_danh' => 'qr_code',
        ]);

        // Bước 6: đồng bộ realtime cho giảng viên và sinh viên trong phòng
        $phong = PhongHocTrucTuyen::where('ma_lich_hoc', $phien->ma_lich_hoc)
            ->where('trang_thai', 'dang_dien_ra')
            ->first();

        if ($phong) {
            broadcast(new DiemDanhThanhCong(
                $phong->ma_phong,
                $sinhVien->id,
                $sinhVien->ma_sinh_vien,
                $user->ho_ten,
                $now->format('H:i d/m/Y'),
            ))->toOthers();
        }

        return response()->json([
            'thanh_cong' => true,
            'message' => 'Điểm danh thành công!',
            'mon_hoc' => $lopHoc?->monHoc?->ten_mon ?? $lopHoc?->ten_lop,
            'thoi_gian_diem_danh' => $now->format('H:i d/m/Y'),
        ]);
    }

    /** US09 - Sinh viên xem lịch sử điểm danh của mình. */
    public function lichSu(Request $request): JsonResponse
    {
        $sinhVien = $request->user()->sinhVien;

        $danhSach = ChiTietDiemDanhModel::with(['phien.lichHoc.lopHoc.monHoc'])
            ->where('ma_sinh_vien', $sinhVien->id)
            ->orderByDesc('thoi_gian_diem_danh')
            ->get()
            ->map(fn ($ct) => [
                'id' => $ct->id,
                'mon_hoc' => $ct->phien?->lichHoc?->lopHoc?->monHoc?->ten_mon,
                'ten_lop' => $ct->phien?->lichHoc?->lopHoc?->ten_lop,
                'ngay_hoc' => $ct->phien?->lichHoc?->ngay_hoc?->format('d/m/Y'),
                'gio_bat_dau' => $ct->phien?->lichHoc?->gio_bat_dau?->format('H:i'),
                'trang_thai_diem_danh' => $ct->trang_thai_diem_danh,
                'thoi_gian_diem_danh' => $ct->thoi_gian_diem_danh?->format('H:i d/m/Y'),
                'hinh_thuc_diem_danh' => $ct->hinh_thuc_diem_danh,
            ]);

        return response()->json(['danh_sach' => $danhSach]);
    }
}
