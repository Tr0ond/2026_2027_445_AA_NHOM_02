<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class HoSoController extends Controller
{
    /** US03 - Xem thông tin cá nhân. */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['sinhVien', 'giangVien']);

        $data = [
            'id' => $user->id,
            'ho_ten' => $user->ho_ten,
            'email' => $user->email,
            'vai_tro' => $user->vai_tro,
            'so_dien_thoai' => $user->so_dien_thoai,
            'dia_chi' => $user->dia_chi,
            'anh_dai_dien' => $user->anh_dai_dien,
        ];

        if ($user->sinhVien) {
            $data['sinh_vien'] = $user->sinhVien->only(['id', 'ma_sinh_vien', 'lop_danh_nghia', 'khoa', 'ngay_sinh', 'gioi_tinh']);
        }

        if ($user->giangVien) {
            $data['giang_vien'] = $user->giangVien->only(['id', 'ma_giang_vien', 'hoc_vi', 'bo_mon']);
        }

        return response()->json(['ho_so' => $data]);
    }

    /** US03 - Cập nhật thông tin cá nhân. */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'max:200'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'dia_chi' => ['nullable', 'string', 'max:300'],
        ]);

        $user->update($data);

        return response()->json(['message' => 'Cập nhật hồ sơ thành công.', 'ho_so' => $data]);
    }

    /** Đổi mật khẩu. */
    public function doiMatKhau(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mat_khau_cu' => ['required', 'string'],
            'mat_khau_moi' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'mat_khau_moi.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'mat_khau_moi.min' => 'Mật khẩu mới tối thiểu 6 ký tự.',
        ]);

        if (! Hash::check($data['mat_khau_cu'], $request->user()->mat_khau)) {
            throw ValidationException::withMessages([
                'mat_khau_cu' => 'Mật khẩu cũ không đúng.',
            ]);
        }

        $request->user()->update(['mat_khau' => $data['mat_khau_moi']]);

        return response()->json(['message' => 'Đổi mật khẩu thành công.']);
    }
}
