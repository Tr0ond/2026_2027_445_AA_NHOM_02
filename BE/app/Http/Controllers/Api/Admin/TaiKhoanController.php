<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiangVien;
use App\Models\SinhVien;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** US19 - Quản lý tài khoản. */
class TaiKhoanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->when($request->filled('vai_tro'), fn ($q) => $q->where('vai_tro', $request->vai_tro))
            ->when($request->filled('tu_khoa'), fn ($q) => $q->where(function ($w) use ($request) {
                $w->where('ho_ten', 'like', '%'.$request->tu_khoa.'%')
                    ->orWhere('email', 'like', '%'.$request->tu_khoa.'%');
            }))
            ->with(['sinhVien', 'giangVien'])
            ->orderByDesc('id');

        $danhSach = $query->paginate(20)->through(fn (User $u) => [
            'id' => $u->id,
            'ho_ten' => $u->ho_ten,
            'email' => $u->email,
            'vai_tro' => $u->vai_tro,
            'trang_thai' => $u->trang_thai,
            'ma_dinh_danh' => $u->sinhVien?->ma_sinh_vien ?? $u->giangVien?->ma_giang_vien,
            'so_dien_thoai' => $u->so_dien_thoai,
            'created_at' => $u->created_at?->format('d/m/Y'),
        ]);

        return response()->json($danhSach);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200', 'unique:tai_khoan,email'],
            'mat_khau' => ['required', 'string', 'min:6'],
            'vai_tro' => ['required', 'in:admin,giang_vien,sinh_vien'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'ma_dinh_danh' => ['nullable', 'string', 'max:20'],
            'bo_mon' => ['nullable', 'string', 'max:100'],
            'lop_danh_nghia' => ['nullable', 'string', 'max:50'],
            'khoa' => ['nullable', 'string', 'max:100'],
        ], [
            'email.unique' => 'Email đã tồn tại.',
            'mat_khau.min' => 'Mật khẩu tối thiểu 6 ký tự.',
        ]);

        $user = User::create([
            'ho_ten' => $data['ho_ten'],
            'email' => $data['email'],
            'mat_khau' => $data['mat_khau'],
            'vai_tro' => $data['vai_tro'],
            'so_dien_thoai' => $data['so_dien_thoai'] ?? null,
        ]);

        if ($data['vai_tro'] === 'sinh_vien') {
            SinhVien::create([
                'ma_sinh_vien' => $data['ma_dinh_danh'] ?: 'SV'.str_pad((string) (SinhVien::max('id') + 1), 4, '0', STR_PAD_LEFT),
                'ma_tai_khoan' => $user->id,
                'lop_danh_nghia' => $data['lop_danh_nghia'] ?? null,
                'khoa' => $data['khoa'] ?? null,
            ]);
        } elseif ($data['vai_tro'] === 'giang_vien') {
            GiangVien::create([
                'ma_giang_vien' => $data['ma_dinh_danh'] ?: 'GV'.str_pad((string) (GiangVien::max('id') + 1), 3, '0', STR_PAD_LEFT),
                'ma_tai_khoan' => $user->id,
                'bo_mon' => $data['bo_mon'] ?? null,
            ]);
        }

        return response()->json(['message' => 'Đã tạo tài khoản.'], 201);
    }

    public function update(Request $request, User $taiKhoan): JsonResponse
    {
        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', Rule::unique('tai_khoan', 'email')->ignore($taiKhoan->id)],
            'vai_tro' => ['required', 'in:admin,giang_vien,sinh_vien'],
            'trang_thai' => ['required', 'in:hoat_dong,khoa'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'mat_khau' => ['nullable', 'string', 'min:6'],
        ]);

        $taiKhoan->update([
            'ho_ten' => $data['ho_ten'],
            'email' => $data['email'],
            'vai_tro' => $data['vai_tro'],
            'trang_thai' => $data['trang_thai'],
            'so_dien_thoai' => $data['so_dien_thoai'] ?? null,
            ...($data['mat_khau'] ? ['mat_khau' => $data['mat_khau']] : []),
        ]);

        return response()->json(['message' => 'Đã cập nhật tài khoản.']);
    }

    public function destroy(User $taiKhoan): JsonResponse
    {
        if ($taiKhoan->id === request()->user()->id) {
            return response()->json(['message' => 'Không thể tự xóa tài khoản của chính mình.'], 422);
        }

        $taiKhoan->delete();

        return response()->json(['message' => 'Đã xóa tài khoản.']);
    }

    /** Khoa/mở khóa nhanh. */
    public function doiTrangThai(Request $request, User $taiKhoan): JsonResponse
    {
        $taiKhoan->update([
            'trang_thai' => $taiKhoan->trang_thai === 'hoat_dong' ? 'khoa' : 'hoat_dong',
        ]);

        return response()->json([
            'message' => $taiKhoan->trang_thai === 'hoat_dong' ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.',
            'trang_thai' => $taiKhoan->trang_thai,
        ]);
    }
}
