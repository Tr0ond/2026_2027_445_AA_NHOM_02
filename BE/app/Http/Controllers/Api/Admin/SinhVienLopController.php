<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DangKyLopHoc;
use App\Models\LopHoc;
use App\Models\SinhVien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** US22 - Quản lý danh sách sinh viên (xem theo lớp, thêm/xóa khỏi lớp). */
class SinhVienLopController extends Controller
{
    /** Danh sách sinh viên toàn hệ thống (kèm các lớp đã đăng ký). */
    public function index(Request $request): JsonResponse
    {
        $query = SinhVien::with(['taiKhoan:id,ho_ten,email,so_dien_thoai,trang_thai', 'dangKyLopHoc.lopHoc'])
            ->when($request->filled('tu_khoa'), fn ($q) => $q->where(function ($w) use ($request) {
                $tuKhoa = $request->tu_khoa;
                $w->where('ma_sinh_vien', 'like', "%{$tuKhoa}%")
                    ->orWhereHas('taiKhoan', fn ($tk) => $tk->where('ho_ten', 'like', "%{$tuKhoa}%")
                        ->orWhere('email', 'like', "%{$tuKhoa}%"));
            }))
            ->when($request->filled('lop_danh_nghia'), fn ($q) => $q->where('lop_danh_nghia', $request->lop_danh_nghia))
            ->orderBy('ma_sinh_vien');

        $danhSach = $query->paginate(20)->through(fn ($sv) => [
            'id' => $sv->id,
            'ma_sinh_vien' => $sv->ma_sinh_vien,
            'ho_ten' => $sv->taiKhoan?->ho_ten,
            'email' => $sv->taiKhoan?->email,
            'so_dien_thoai' => $sv->taiKhoan?->so_dien_thoai,
            'trang_thai' => $sv->taiKhoan?->trang_thai,
            'lop_danh_nghia' => $sv->lop_danh_nghia,
            'khoa' => $sv->khoa,
            'so_lop_dang_ky' => $sv->dangKyLopHoc->where('trang_thai', 'da_duyet')->count(),
        ]);

        return response()->json($danhSach);
    }

    /** Danh sách sinh viên của một lớp. */
    public function theoLop(LopHoc $lopHoc): JsonResponse
    {
        $danhSach = $lopHoc->dangKy()
            ->with(['sinhVien.taiKhoan'])
            ->where('trang_thai', '!=', 'huy')
            ->orderBy('ma_sinh_vien')
            ->get()
            ->map(fn ($dk) => [
                'id_dang_ky' => $dk->id,
                'ma_sinh_vien' => $dk->sinhVien?->id,
                'ma_sv_text' => $dk->sinhVien?->ma_sinh_vien,
                'ho_ten' => $dk->sinhVien?->taiKhoan?->ho_ten,
                'email' => $dk->sinhVien?->taiKhoan?->email,
                'lop_danh_nghia' => $dk->sinhVien?->lop_danh_nghia,
                'trang_thai_dang_ky' => $dk->trang_thai,
                'ngay_dang_ky' => $dk->ngay_dang_ky?->format('d/m/Y'),
            ]);

        return response()->json(['danh_sach' => $danhSach]);
    }

    /** Thêm sinh viên vào lớp. */
    public function themVaoLop(Request $request, LopHoc $lopHoc): JsonResponse
    {
        $data = $request->validate([
            'ma_sinh_vien' => ['required', 'integer', 'exists:sinh_vien,id'],
        ]);

        $tonTai = DangKyLopHoc::where('ma_sinh_vien', $data['ma_sinh_vien'])
            ->where('ma_lop_hoc', $lopHoc->id)
            ->exists();

        if ($tonTai) {
            return response()->json(['message' => 'Sinh viên đã ở trong lớp.'], 422);
        }

        if ($lopHoc->soLuongDaDangKy() >= $lopHoc->so_luong_toi_da) {
            return response()->json(['message' => 'Lớp đã đủ số lượng.'], 422);
        }

        DangKyLopHoc::create([
            'ma_sinh_vien' => $data['ma_sinh_vien'],
            'ma_lop_hoc' => $lopHoc->id,
            'ngay_dang_ky' => now()->toDateString(),
            'trang_thai' => 'da_duyet',
        ]);

        return response()->json(['message' => 'Đã thêm sinh viên vào lớp.'], 201);
    }

    /** Xóa sinh viên khỏi lớp. */
    public function xoaKhoiLop(Request $request, LopHoc $lopHoc, SinhVien $sinhVien): JsonResponse
    {
        DangKyLopHoc::where('ma_sinh_vien', $sinhVien->id)
            ->where('ma_lop_hoc', $lopHoc->id)
            ->delete();

        return response()->json(['message' => 'Đã xóa sinh viên khỏi lớp.']);
    }

    /** Danh sách sinh viên chưa thuộc lớp (để chọn thêm). */
    public function chuaThuocLop(LopHoc $lopHoc): JsonResponse
    {
        $daTrongLop = $lopHoc->dangKy()->pluck('ma_sinh_vien');

        $danhSach = SinhVien::with('taiKhoan:id,ho_ten')
            ->whereNotIn('id', $daTrongLop)
            ->orderBy('ma_sinh_vien')
            ->get()
            ->map(fn ($sv) => [
                'id' => $sv->id,
                'ma_sinh_vien' => $sv->ma_sinh_vien,
                'ho_ten' => $sv->taiKhoan?->ho_ten,
            ]);

        return response()->json(['danh_sach' => $danhSach]);
    }
}
