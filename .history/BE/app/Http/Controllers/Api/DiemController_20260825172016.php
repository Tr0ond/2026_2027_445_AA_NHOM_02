<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DiemSinhVien;
use Illuminate\Support\Facades\Auth;

class DiemController extends Controller
{
    // Giảng viên nhập hoặc sửa điểm
    public function storeOrUpdate(Request $request)
    {
        $this->authorize('isGiangVien'); // Kiểm tra quyền giảng viên

        $validated = $request->validate([
            'ma_sinh_vien' => 'required|exists:sinh_vien,id',
            'ma_thanh_phan' => 'required|exists:diem_thanh_phan,id',
            'diem' => 'required|numeric|min:0|max:10',
        ]);

        $diem = DiemSinhVien::updateOrCreate(
            [
                'ma_sinh_vien' => $validated['ma_sinh_vien'],
                'ma_thanh_phan' => $validated['ma_thanh_phan'],
            ],
            ['diem' => $validated['diem']]
        );

        return response()->json(['message' => 'Điểm đã được cập nhật.', 'data' => $diem], 200);
    }

    // Sinh viên xem điểm của mình
    public function show(Request $request)
    {
        $this->authorize('isSinhVien'); // Kiểm tra quyền sinh viên

        $user = Auth::user();
        $diem = DiemSinhVien::where('ma_sinh_vien', $user->id)
            ->with('thanhPhan')
            ->get();

        return response()->json(['data' => $diem], 200);
    }
}