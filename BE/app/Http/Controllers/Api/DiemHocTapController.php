<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDiemDanh;
use App\Models\DangKyLopHoc;
use App\Models\DiemSinhVien;
use App\Models\DiemThanhPhan;
use App\Models\DonXinPhep;
use App\Models\LopHoc;
use App\Models\PhienDiemDanh;
use App\Models\SinhVien;
use App\Services\KetQuaHocPhanService;
use App\Services\ThongBaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiemHocTapController extends Controller
{
    /** US11 - Sinh viên xem điểm các lớp đã học (kèm điểm tổng kết tính theo trọng số). */
    public function diemCuaToi(Request $request): JsonResponse
    {
        $sinhVien = $request->user()->sinhVien;

        $lops = LopHoc::with(['monHoc'])
            ->whereHas('dangKy', fn ($q) => $q->where('ma_sinh_vien', $sinhVien->id)->where('trang_thai', 'da_duyet'))
            ->get();

        $ketQuaHocPhanService = app(KetQuaHocPhanService::class);

        $ketQua = $lops->map(function (LopHoc $lop) use ($sinhVien, $ketQuaHocPhanService) {
            $maThanhPhan = DiemThanhPhan::cuaLop($lop)->pluck('id');

            $diemCacThanhPhan = DiemSinhVien::with('thanhPhan')
                ->where('ma_sinh_vien', $sinhVien->id)
                ->whereIn('ma_thanh_phan', $maThanhPhan)
                ->get()
                ->map(fn ($d) => [
                    'ten_thanh_phan' => $d->thanhPhan?->ten_thanh_phan,
                    'trong_so' => $d->thanhPhan?->trong_so,
                    'diem' => $d->diem,
                ]);

            $ketQuaLuu = $ketQuaHocPhanService->dongBo($lop, $sinhVien->id);

            return [
                'id' => $lop->id,
                'ten_lop' => $lop->ten_lop,
                'mon_hoc' => $lop->monHoc?->ten_mon,
                'so_tin_chi' => $lop->monHoc?->so_tin_chi,
                'trang_thai' => $lop->trang_thai,
                'diem_thanh_phan' => $diemCacThanhPhan,
                'diem_tong_ket' => $ketQuaLuu->diem_tong_ket,
                'xep_loai' => $ketQuaLuu->xep_loai,
                'trang_thai_ket_qua' => $ketQuaLuu->trang_thai,
            ];
        });

        return response()->json(['danh_sach' => $ketQua]);
    }

    /** US23 - Xem điểm thành phần áp dụng trực tiếp cho lớp. */
    public function thanhPhanCuaLop(Request $request, LopHoc $lopHoc): JsonResponse
    {
        if (! $this->coQuyenQuanLyLop($request, $lopHoc)) {
            return response()->json(['message' => 'Bạn không phụ trách lớp học này.'], 403);
        }

        return response()->json([
            'danh_sach' => DiemThanhPhan::cuaLop($lopHoc)->orderBy('id')->get(),
        ]);
    }

    /** US18 - Bảng điểm cả lớp: sinh viên × điểm thành phần. */
    public function bangDiemLop(Request $request, LopHoc $lopHoc): JsonResponse
    {
        if (! $this->coQuyenQuanLyLop($request, $lopHoc)) {
            return response()->json(['message' => 'Bạn không phụ trách lớp học này.'], 403);
        }

        $lopHoc->load('monHoc');
        $thanhPhan = DiemThanhPhan::cuaLop($lopHoc)->orderBy('id')->get();

        $sinhViens = $lopHoc->dangKy()
            ->with(['sinhVien.taiKhoan'])
            ->where('trang_thai', 'da_duyet')
            ->get();

        $ketQuaHocPhanService = app(KetQuaHocPhanService::class);

        $danhSach = $sinhViens->map(function ($dk) use ($lopHoc, $thanhPhan, $ketQuaHocPhanService) {
            $diem = DiemSinhVien::where('ma_sinh_vien', $dk->ma_sinh_vien)
                ->whereIn('ma_thanh_phan', $thanhPhan->pluck('id'))
                ->get()
                ->keyBy('ma_thanh_phan');

            $cotDiem = [];
            foreach ($thanhPhan as $tp) {
                $d = $diem->get($tp->id)?->diem;
                $cotDiem[$tp->id] = $d;
            }

            $ketQua = $ketQuaHocPhanService->dongBo($lopHoc, $dk->ma_sinh_vien);

            return [
                'ma_sinh_vien' => $dk->ma_sinh_vien,
                'ma_sv_text' => $dk->sinhVien?->ma_sinh_vien,
                'ho_ten' => $dk->sinhVien?->taiKhoan?->ho_ten,
                'diem' => $cotDiem,
                'diem_tong_ket' => $ketQua->diem_tong_ket,
                'xep_loai' => $ketQua->xep_loai,
                'trang_thai_ket_qua' => $ketQua->trang_thai,
            ];
        });

        return response()->json([
            'lop_hoc' => [
                'id' => $lopHoc->id,
                'ten_lop' => $lopHoc->ten_lop,
                'mon_hoc' => $lopHoc->monHoc?->ten_mon,
            ],
            'thanh_phan' => $thanhPhan,
            'danh_sach' => $danhSach,
        ]);
    }

    /** US18 - Nhập/cập nhật điểm một sinh viên cho một thành phần. */
    public function luuDiem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ma_sinh_vien' => ['required', 'integer', 'exists:sinh_vien,id'],
            'ma_thanh_phan' => ['required', 'integer', 'exists:diem_thanh_phan,id'],
            'diem' => ['nullable', 'numeric', 'min:0', 'max:10'],
        ]);

        $thanhPhan = DiemThanhPhan::with('lopHoc')->findOrFail($data['ma_thanh_phan']);
        $lopHoc = $thanhPhan->lopHoc;

        if (! $this->coQuyenQuanLyLop($request, $lopHoc)) {
            return response()->json(['message' => 'Bạn không phụ trách lớp học này.'], 403);
        }

        $daDangKy = DangKyLopHoc::where('ma_sinh_vien', $data['ma_sinh_vien'])
            ->where('ma_lop_hoc', $lopHoc->id)
            ->where('trang_thai', 'da_duyet')
            ->exists();

        if (! $daDangKy) {
            return response()->json(['message' => 'Sinh viên không thuộc lớp của thành phần điểm này.'], 422);
        }

        DiemSinhVien::updateOrCreate(
            ['ma_sinh_vien' => $data['ma_sinh_vien'], 'ma_thanh_phan' => $data['ma_thanh_phan']],
            ['diem' => $data['diem']]
        );

        $ketQua = app(KetQuaHocPhanService::class)->dongBo($lopHoc, $data['ma_sinh_vien']);
        $sinhVien = SinhVien::find($data['ma_sinh_vien']);
        if ($sinhVien?->ma_tai_khoan) {
            app(ThongBaoService::class)->tao(
                $sinhVien->ma_tai_khoan,
                'diem_moi',
                'Có điểm mới',
                "Môn {$lopHoc->monHoc?->ten_mon} vừa được cập nhật điểm.",
                [
                    'ma_lop_hoc' => $lopHoc->id,
                    'ma_thanh_phan' => $thanhPhan->id,
                    'ket_qua_id' => $ketQua->id,
                ],
            );
        }

        return response()->json(['message' => 'Đã lưu điểm.']);
    }

    /**
     * US18 - Đồng bộ điểm chuyên cần theo từng buổi học:
     * - đạt ít nhất 2/3 phiên của buổi: 1 điểm;
     * - vắng có phép: 0,5 điểm;
     * - vắng hoặc không đủ 2/3 phiên: 0 điểm.
     * Tổng điểm các buổi được quy đổi về thang 10.
     */
    public function dongBoChuyenCan(Request $request, LopHoc $lopHoc): JsonResponse
    {
        if (! $this->coQuyenQuanLyLop($request, $lopHoc)) {
            return response()->json(['message' => 'Bạn không phụ trách lớp học này.'], 403);
        }

        $thanhPhanCC = DiemThanhPhan::cuaLop($lopHoc)
            ->where('ten_thanh_phan', 'like', '%Chuyên cần%')
            ->first();

        if (! $thanhPhanCC) {
            return response()->json(['message' => 'Lớp chưa có điểm thành phần "Chuyên cần".'], 422);
        }

        $cacPhien = PhienDiemDanh::whereHas('lichHoc', fn ($q) => $q->where('ma_lop_hoc', $lopHoc->id))
            ->where('trang_thai', 'da_dong')
            ->get(['id', 'ma_lich_hoc']);

        if ($cacPhien->isEmpty()) {
            return response()->json(['message' => 'Lớp chưa có phiên điểm danh nào đã đóng.'], 422);
        }

        $sinhViens = $lopHoc->dangKy()->where('trang_thai', 'da_duyet')->pluck('ma_sinh_vien');
        $cacPhienTheoBuoi = $cacPhien->groupBy('ma_lich_hoc');
        $tongBuoi = $cacPhienTheoBuoi->count();

        $chiTietTheoSinhVien = ChiTietDiemDanh::whereIn('ma_phien_diem_danh', $cacPhien->pluck('id'))
            ->whereIn('ma_sinh_vien', $sinhViens)
            ->get(['ma_phien_diem_danh', 'ma_sinh_vien', 'trang_thai_diem_danh'])
            ->groupBy('ma_sinh_vien');

        $donPhepTheoSinhVien = DonXinPhep::whereIn('ma_lich_hoc', $cacPhienTheoBuoi->keys())
            ->whereIn('ma_sinh_vien', $sinhViens)
            ->where('trang_thai', 'duoc_duyet')
            ->get(['ma_sinh_vien', 'ma_lich_hoc'])
            ->groupBy('ma_sinh_vien');

        $daCapNhat = 0;
        foreach ($sinhViens as $maSinhVien) {
            $chiTietCuaSinhVien = $chiTietTheoSinhVien->get($maSinhVien, collect());
            $cacBuoiDuocPhep = $donPhepTheoSinhVien->get($maSinhVien, collect())
                ->pluck('ma_lich_hoc')
                ->flip();
            $tongDiemBuoi = 0.0;

            foreach ($cacPhienTheoBuoi as $maLichHoc => $cacPhienCuaBuoi) {
                $maPhienCuaBuoi = $cacPhienCuaBuoi->pluck('id');
                $chiTietCuaBuoi = $chiTietCuaSinhVien
                    ->whereIn('ma_phien_diem_danh', $maPhienCuaBuoi);

                $vangCoPhep = $cacBuoiDuocPhep->has($maLichHoc)
                    || $chiTietCuaBuoi->contains('trang_thai_diem_danh', 'vang_co_phep');

                if ($vangCoPhep) {
                    $tongDiemBuoi += 0.5;
                    continue;
                }

                $soPhienDat = $chiTietCuaBuoi
                    ->whereIn('trang_thai_diem_danh', ['co_mat', 'di_muon'])
                    ->count();
                $soPhienToiThieu = (int) ceil($cacPhienCuaBuoi->count() * 2 / 3);

                if ($soPhienDat >= $soPhienToiThieu) {
                    $tongDiemBuoi += 1.0;
                }
            }

            $diemChuyenCan = round(($tongDiemBuoi / $tongBuoi) * 10, 2);
            DiemSinhVien::updateOrCreate(
                ['ma_sinh_vien' => $maSinhVien, 'ma_thanh_phan' => $thanhPhanCC->id],
                ['diem' => $diemChuyenCan]
            );

            $ketQua = app(KetQuaHocPhanService::class)->dongBo($lopHoc, $maSinhVien);
            $sinhVien = SinhVien::find($maSinhVien);
            if ($sinhVien?->ma_tai_khoan) {
                app(ThongBaoService::class)->tao(
                    $sinhVien->ma_tai_khoan,
                    'diem_moi',
                    'Có điểm mới',
                    "Điểm chuyên cần môn {$lopHoc->monHoc?->ten_mon} vừa được đồng bộ.",
                    ['ma_lop_hoc' => $lopHoc->id, 'ket_qua_id' => $ketQua->id],
                );
            }
            $daCapNhat++;
        }

        return response()->json([
            'message' => "Đã đồng bộ điểm chuyên cần cho {$daCapNhat} sinh viên theo {$tongBuoi} buổi học.",
            'quy_tac' => [
                'dat_tu_hai_phan_ba_phien' => 1,
                'vang_co_phep' => 0.5,
                'vang_hoac_khong_du_phien' => 0,
                'thang_diem' => 10,
            ],
        ]);
    }

    private function coQuyenQuanLyLop(Request $request, LopHoc $lopHoc): bool
    {
        $user = $request->user();

        if ($user->laAdmin()) {
            return true;
        }

        return $user->laGiangVien()
            && $user->giangVien
            && $lopHoc->phanCong()
                ->where('ma_giang_vien', $user->giangVien->id)
                ->exists();
    }
}
