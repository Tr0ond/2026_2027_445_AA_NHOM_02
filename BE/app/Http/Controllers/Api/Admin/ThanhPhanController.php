<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiemThanhPhan;
use App\Models\LopHoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * US23 - Quản lý điểm thành phần THEO LỚP HỌC (chỉ admin).
 * Giảng viên chỉ được xem và nhập điểm, không thêm/sửa/xóa thành phần.
 */
class ThanhPhanController extends Controller
{
    /** Danh sách thành phần của một lớp. */
    public function index(LopHoc $lopHoc): JsonResponse
    {
        $danhSach = DiemThanhPhan::where('ma_lop_hoc', $lopHoc->id)
            ->orderBy('id')
            ->get();

        return response()->json(['danh_sach' => $danhSach]);
    }

    /** Thêm thành phần cho một lớp. */
    public function store(Request $request, LopHoc $lopHoc): JsonResponse
    {
        $data = $request->validate([
            'ten_thanh_phan' => ['required', 'string', 'max:50'],
            'trong_so' => ['required', 'numeric', 'min:0', 'max:10'],
        ], [
            'ten_thanh_phan.required' => 'Vui lòng nhập tên thành phần.',
            'trong_so.required' => 'Vui lòng nhập trọng số.',
        ]);

        $tonTai = DiemThanhPhan::where('ma_lop_hoc', $lopHoc->id)
            ->where('ten_thanh_phan', $data['ten_thanh_phan'])
            ->exists();

        if ($tonTai) {
            return response()->json(['message' => 'Thành phần này đã tồn tại trong lớp.'], 422);
        }

        $tp = DiemThanhPhan::create([
            'ma_lop_hoc' => $lopHoc->id,
            'ten_thanh_phan' => $data['ten_thanh_phan'],
            'trong_so' => $data['trong_so'],
        ]);

        return response()->json(['message' => 'Đã thêm thành phần cho lớp.', 'thanh_phan' => $tp], 201);
    }

    /** Sửa tên / trọng số thành phần. */
    public function update(Request $request, DiemThanhPhan $thanhPhan): JsonResponse
    {
        $data = $request->validate([
            'ten_thanh_phan' => ['required', 'string', 'max:50'],
            'trong_so' => ['required', 'numeric', 'min:0', 'max:10'],
        ]);

        $trungTen = DiemThanhPhan::where('ma_lop_hoc', $thanhPhan->ma_lop_hoc)
            ->where('ten_thanh_phan', $data['ten_thanh_phan'])
            ->where('id', '!=', $thanhPhan->id)
            ->exists();

        if ($trungTen) {
            return response()->json(['message' => 'Lớp đã có thành phần trùng tên này.'], 422);
        }

        $thanhPhan->update($data);

        return response()->json(['message' => 'Đã cập nhật thành phần.']);
    }

    /** Xóa thành phần (kèm toàn bộ điểm đã nhập của thành phần này). */
    public function destroy(DiemThanhPhan $thanhPhan): JsonResponse
    {
        $thanhPhan->delete();

        return response()->json(['message' => 'Đã xóa thành phần cùng điểm liên quan.']);
    }
}

