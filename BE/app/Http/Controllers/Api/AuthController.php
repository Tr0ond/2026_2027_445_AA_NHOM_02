<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** US01 - Đăng nhập: trả về Sanctum token kèm thông tin tài khoản. */
    public function dangNhap(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'mat_khau' => ['required', 'string'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'mat_khau.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['mat_khau'], $user->mat_khau)) {
            throw ValidationException::withMessages([
                'email' => 'Email hoặc mật khẩu không đúng.',
            ]);
        }

        if ($user->trang_thai !== 'hoat_dong') {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản đã bị khóa, vui lòng liên hệ quản trị viên.',
            ]);
        }

        $token = $user->createToken('spa-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'tai_khoan' => $this->duLieuTaiKhoan($user),
        ]);
    }

    /** US02 - Đăng xuất: thu hồi token hiện tại. */
    public function dangXuat(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đã đăng xuất.']);
    }

    /** Lấy thông tin tài khoản đang đăng nhập. */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['tai_khoan' => $this->duLieuTaiKhoan($request->user())]);
    }

    public function duLieuTaiKhoan(User $user): array
    {
        $data = [
            'id' => $user->id,
            'ho_ten' => $user->ho_ten,
            'email' => $user->email,
            'vai_tro' => $user->vai_tro,
            'trang_thai' => $user->trang_thai,
            'anh_dai_dien' => $user->anh_dai_dien,
            'so_dien_thoai' => $user->so_dien_thoai,
            'dia_chi' => $user->dia_chi,
        ];

        if ($user->laSinhVien() && $user->sinhVien) {
            $data['sinh_vien'] = [
                'id' => $user->sinhVien->id,
                'ma_sinh_vien' => $user->sinhVien->ma_sinh_vien,
                'lop_danh_nghia' => $user->sinhVien->lop_danh_nghia,
                'khoa' => $user->sinhVien->khoa,
            ];
        }

        if ($user->laGiangVien() && $user->giangVien) {
            $data['giang_vien'] = [
                'id' => $user->giangVien->id,
                'ma_giang_vien' => $user->giangVien->ma_giang_vien,
                'hoc_vi' => $user->giangVien->hoc_vi,
                'bo_mon' => $user->giangVien->bo_mon,
            ];
        }

        return $data;
    }
}
