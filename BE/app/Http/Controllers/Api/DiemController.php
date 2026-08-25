<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DiemSinhVien;
use App\Models\DiemThanhPhan;
use App\Models\SinhVien;
use App\Models\GiangVien;
use App\Models\PhanCongGiangDay;
use App\Models\KetQuaHocPhan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DiemController extends Controller
{
    /**
     * Giảng viên nhập hoặc cập nhật điểm thành phần cho sinh viên
     */
    public function storeOrUpdate(Request $request)
    {
        $user = Auth::user();
        if (!$user->laGiangVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $validated = $request->validate([
            'ma_sinh_vien' => 'required|exists:sinh_vien,id',
            'ma_thanh_phan' => 'required|exists:diem_thanh_phan,id',
            'diem' => 'required|numeric|min:0|max:10',
        ]);

        $giangVien = GiangVien::where('ma_tai_khoan', $user->id)->first();
        if (!$giangVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin giảng viên'], 404);
        }

        $thanhPhan = DiemThanhPhan::with('lopHoc')->find($validated['ma_thanh_phan']);
        if (!$thanhPhan) {
            return response()->json(['message' => 'Không tìm thấy thành phần điểm'], 404);
        }

        // Kiểm tra giảng viên có phụ trách lớp học này không
        $phanCong = PhanCongGiangDay::where('ma_giang_vien', $giangVien->id)
            ->where('ma_lop_hoc', $thanhPhan->ma_lop_hoc)
            ->first();
        
        if (!$phanCong) {
            return response()->json(['message' => 'Bạn không phụ trách lớp học này'], 403);
        }

        // Kiểm tra sinh viên có đăng ký lớp học này không
        $sinhVien = SinhVien::find($validated['ma_sinh_vien']);
        $dangKy = DB::table('dang_ky_lop_hoc')
            ->where('ma_sinh_vien', $validated['ma_sinh_vien'])
            ->where('ma_lop_hoc', $thanhPhan->ma_lop_hoc)
            ->where('trang_thai', '!=', 'huy')
            ->first();
        
        if (!$dangKy) {
            return response()->json(['message' => 'Sinh viên không đăng ký lớp học này'], 400);
        }

        $diem = DiemSinhVien::updateOrCreate(
            [
                'ma_sinh_vien' => $validated['ma_sinh_vien'],
                'ma_thanh_phan' => $validated['ma_thanh_phan'],
            ],
            ['diem' => $validated['diem']]
        );

        // Cập nhật điểm tổng kết nếu tất cả thành phần đã có điểm
        $this->capNhatDiemTongKet($validated['ma_sinh_vien'], $thanhPhan->ma_lop_hoc);

        return response()->json(['message' => 'Điểm đã được cập nhật', 'data' => $diem->load('thanhPhan')], 200);
    }

    /**
     * Giảng viên nhập điểm hàng loạt cho nhiều sinh viên
     */
    public function storeBulk(Request $request)
    {
        $user = Auth::user();
        if (!$user->laGiangVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $validated = $request->validate([
            'ma_thanh_phan' => 'required|exists:diem_thanh_phan,id',
            'diems' => 'required|array',
            'diems.*.ma_sinh_vien' => 'required|exists:sinh_vien,id',
            'diems.*.diem' => 'required|numeric|min:0|max:10',
        ]);

        $giangVien = GiangVien::where('ma_tai_khoan', $user->id)->first();
        if (!$giangVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin giảng viên'], 404);
        }

        $thanhPhan = DiemThanhPhan::with('lopHoc')->find($validated['ma_thanh_phan']);
        if (!$thanhPhan) {
            return response()->json(['message' => 'Không tìm thấy thành phần điểm'], 404);
        }

        // Kiểm tra quyền giảng viên
        $phanCong = PhanCongGiangDay::where('ma_giang_vien', $giangVien->id)
            ->where('ma_lop_hoc', $thanhPhan->ma_lop_hoc)
            ->first();
        
        if (!$phanCong) {
            return response()->json(['message' => 'Bạn không phụ trách lớp học này'], 403);
        }

        DB::beginTransaction();
        try {
            $results = [];
            foreach ($validated['diems'] as $item) {
                // Kiểm tra sinh viên có đăng ký lớp học không
                $dangKy = DB::table('dang_ky_lop_hoc')
                    ->where('ma_sinh_vien', $item['ma_sinh_vien'])
                    ->where('ma_lop_hoc', $thanhPhan->ma_lop_hoc)
                    ->where('trang_thai', '!=', 'huy')
                    ->first();
                
                if (!$dangKy) {
                    $results[] = [
                        'ma_sinh_vien' => $item['ma_sinh_vien'],
                        'status' => 'error',
                        'message' => 'Sinh viên không đăng ký lớp học này'
                    ];
                    continue;
                }

                $diem = DiemSinhVien::updateOrCreate(
                    [
                        'ma_sinh_vien' => $item['ma_sinh_vien'],
                        'ma_thanh_phan' => $validated['ma_thanh_phan'],
                    ],
                    ['diem' => $item['diem']]
                );

                $results[] = [
                    'ma_sinh_vien' => $item['ma_sinh_vien'],
                    'status' => 'success',
                    'data' => $diem
                ];

                // Cập nhật điểm tổng kết
                $this->capNhatDiemTongKet($item['ma_sinh_vien'], $thanhPhan->ma_lop_hoc);
            }
            
            DB::commit();
            return response()->json(['message' => 'Đã cập nhật điểm hàng loạt', 'data' => $results], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sinh viên xem điểm của mình
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->laSinhVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $sinhVien = SinhVien::where('ma_tai_khoan', $user->id)->first();
        if (!$sinhVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin sinh viên'], 404);
        }

        $query = DiemSinhVien::where('ma_sinh_vien', $sinhVien->id)
            ->with(['thanhPhan.lopHoc.monHoc']);

        // Filter by class if specified
        if ($request->has('ma_lop_hoc')) {
            $query->whereHas('thanhPhan', function($q) use ($request) {
                $q->where('ma_lop_hoc', $request->ma_lop_hoc);
            });
        }

        $diem = $query->get();

        return response()->json(['data' => $diem], 200);
    }

    /**
     * Sinh viên xem điểm chi tiết theo lớp học
     */
    public function showByClass($ma_lop_hoc)
    {
        $user = Auth::user();
        if (!$user->laSinhVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $sinhVien = SinhVien::where('ma_tai_khoan', $user->id)->first();
        if (!$sinhVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin sinh viên'], 404);
        }

        // Kiểm tra sinh viên có đăng ký lớp học này không
        $dangKy = DB::table('dang_ky_lop_hoc')
            ->where('ma_sinh_vien', $sinhVien->id)
            ->where('ma_lop_hoc', $ma_lop_hoc)
            ->where('trang_thai', '!=', 'huy')
            ->first();
        
        if (!$dangKy) {
            return response()->json(['message' => 'Bạn không đăng ký lớp học này'], 403);
        }

        // Lấy tất cả thành phần điểm của lớp học
        $thanhPhans = DiemThanhPhan::where('ma_lop_hoc', $ma_lop_hoc)->get();
        
        // Lấy điểm của sinh viên cho từng thành phần
        $diemData = [];
        foreach ($thanhPhans as $thanhPhan) {
            $diemSV = DiemSinhVien::where('ma_sinh_vien', $sinhVien->id)
                ->where('ma_thanh_phan', $thanhPhan->id)
                ->first();
            
            $diemData[] = [
                'thanh_phan' => $thanhPhan,
                'diem' => $diemSV ? $diemSV->diem : null,
                'da_co_diem' => $diemSV !== null
            ];
        }

        // Lấy điểm tổng kết
        $ketQua = KetQuaHocPhan::where('ma_sinh_vien', $sinhVien->id)
            ->where('ma_lop_hoc', $ma_lop_hoc)
            ->first();

        return response()->json([
            'data' => [
                'diem_thanh_phan' => $diemData,
                'diem_tong_ket' => $ketQua ? $ketQua->diem_tong_ket : null,
                'xep_loai' => $ketQua ? $ketQua->xep_loai : null
            ]
        ], 200);
    }

    /**
     * Giảng viên xem danh sách điểm của lớp học
     */
    public function showClassGrades($ma_lop_hoc)
    {
        $user = Auth::user();
        if (!$user->laGiangVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $giangVien = GiangVien::where('ma_tai_khoan', $user->id)->first();
        if (!$giangVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin giảng viên'], 404);
        }

        // Kiểm tra giảng viên có phụ trách lớp học này không
        $phanCong = PhanCongGiangDay::where('ma_giang_vien', $giangVien->id)
            ->where('ma_lop_hoc', $ma_lop_hoc)
            ->first();
        
        if (!$phanCong) {
            return response()->json(['message' => 'Bạn không phụ trách lớp học này'], 403);
        }

        // Lấy tất cả sinh viên đăng ký lớp học
        $dangKyList = DB::table('dang_ky_lop_hoc')
            ->where('ma_lop_hoc', $ma_lop_hoc)
            ->where('trang_thai', '!=', 'huy')
            ->pluck('ma_sinh_vien');

        $sinhViens = SinhVien::whereIn('id', $dangKyList)->get();

        // Lấy tất cả thành phần điểm của lớp học
        $thanhPhans = DiemThanhPhan::where('ma_lop_hoc', $ma_lop_hoc)->get();

        // Lấy điểm của tất cả sinh viên
        $diemData = [];
        foreach ($sinhViens as $sinhVien) {
            $diemSV = [];
            foreach ($thanhPhans as $thanhPhan) {
                $diem = DiemSinhVien::where('ma_sinh_vien', $sinhVien->id)
                    ->where('ma_thanh_phan', $thanhPhan->id)
                    ->first();
                
                $diemSV[] = [
                    'thanh_phan_id' => $thanhPhan->id,
                    'ten_thanh_phan' => $thanhPhan->ten_thanh_phan,
                    'trong_so' => $thanhPhan->trong_so,
                    'diem' => $diem ? $diem->diem : null
                ];
            }

            // Lấy điểm tổng kết
            $ketQua = KetQuaHocPhan::where('ma_sinh_vien', $sinhVien->id)
                ->where('ma_lop_hoc', $ma_lop_hoc)
                ->first();

            $diemData[] = [
                'sinh_vien' => $sinhVien,
                'diem_thanh_phan' => $diemSV,
                'diem_tong_ket' => $ketQua ? $ketQua->diem_tong_ket : null,
                'xep_loai' => $ketQua ? $ketQua->xep_loai : null
            ];
        }

        return response()->json([
            'data' => [
                'thanh_phans' => $thanhPhans,
                'sinh_viens' => $diemData
            ]
        ], 200);
    }

    /**
     * Cập nhật điểm tổng kết cho sinh viên
     */
    private function capNhatDiemTongKet($ma_sinh_vien, $ma_lop_hoc)
    {
        // Lấy tất cả thành phần điểm của lớp học
        $thanhPhans = DiemThanhPhan::where('ma_lop_hoc', $ma_lop_hoc)->get();
        
        if ($thanhPhans->isEmpty()) {
            return;
        }

        // Kiểm tra tất cả thành phần đã có điểm chưa
        $tongTrongSo = 0;
        $diemTong = 0;
        $daHoanThanh = true;

        foreach ($thanhPhans as $thanhPhan) {
            $diemSV = DiemSinhVien::where('ma_sinh_vien', $ma_sinh_vien)
                ->where('ma_thanh_phan', $thanhPhan->id)
                ->first();
            
            if (!$diemSV) {
                $daHoanThanh = false;
                break;
            }

            $tongTrongSo += $thanhPhan->trong_so;
            $diemTong += $diemSV->diem * $thanhPhan->trong_so;
        }

        if ($daHoanThanh && $tongTrongSo > 0) {
            $diemTongKet = $diemTong / $tongTrongSo;
            $xepLoai = $this->tinhXepLoai($diemTongKet);

            KetQuaHocPhan::updateOrCreate(
                [
                    'ma_sinh_vien' => $ma_sinh_vien,
                    'ma_lop_hoc' => $ma_lop_hoc,
                ],
                [
                    'diem_tong_ket' => $diemTongKet,
                    'xep_loai' => $xepLoai,
                    'trang_thai' => 'da_co_diem',
                    'thoi_gian_cap_nhat' => now()
                ]
            );
        }
    }

    /**
     * Tính xếp loại dựa trên điểm tổng kết
     */
    private function tinhXepLoai($diem)
    {
        if ($diem >= 8.5) return 'A';
        if ($diem >= 7.0) return 'B';
        if ($diem >= 5.5) return 'C';
        if ($diem >= 4.0) return 'D';
        return 'F';
    }
}
