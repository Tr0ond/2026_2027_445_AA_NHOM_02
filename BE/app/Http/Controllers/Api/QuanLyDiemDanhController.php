<?php

namespace App\Http\Controllers\Api;

use App\Events\TrangThaiDiemDanhCapNhat;
use App\Http\Controllers\Controller;
use App\Models\ChiTietDiemDanh;
use App\Models\DangKyLopHoc;
use App\Models\DonXinPhep;
use App\Models\LichHoc;
use App\Models\LopHoc;
use App\Models\PhienDiemDanh;
use App\Models\PhongHocTrucTuyen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class QuanLyDiemDanhController extends Controller
{
    /** Danh sách tất cả buổi học của một lớp mà giảng viên phụ trách. */
    public function lichHocCuaLop(Request $request, LopHoc $lopHoc): JsonResponse
    {
        $this->damBaoPhuTrach($request, $lopHoc);

        $lopHoc->loadMissing('monHoc');
        $lichHocs = $lopHoc->lichHoc()
            ->with(['phienDiemDanh' => fn ($query) => $query->latest('id')])
            ->orderByDesc('ngay_hoc')
            ->orderByDesc('gio_bat_dau')
            ->get()
            ->map(function (LichHoc $lichHoc): array {
                $phien = $lichHoc->phienDiemDanh->first();

                return [
                    'id' => $lichHoc->id,
                    'ngay_hoc' => $lichHoc->ngay_hoc?->format('Y-m-d'),
                    'gio_bat_dau' => $lichHoc->gio_bat_dau?->format('H:i'),
                    'gio_ket_thuc' => $lichHoc->gio_ket_thuc?->format('H:i'),
                    'chu_de' => $lichHoc->chu_de,
                    'trang_thai' => $lichHoc->trang_thai,
                    'phien_diem_danh_id' => $phien?->id,
                    'trang_thai_phien' => $phien?->trang_thai,
                ];
            });

        return response()->json([
            'lop_hoc' => [
                'id' => $lopHoc->id,
                'ten_lop' => $lopHoc->ten_lop,
                'mon_hoc' => $lopHoc->monHoc?->ten_mon,
            ],
            'danh_sach' => $lichHocs,
        ]);
    }

    /** Danh sách điểm danh của một buổi học, kể cả khi chưa có phiên QR. */
    public function show(Request $request, LichHoc $lichHoc): JsonResponse
    {
        $lichHoc->loadMissing('lopHoc.monHoc');
        $this->damBaoPhuTrach($request, $lichHoc->lopHoc);

        return response()->json($this->duLieuDiemDanh($lichHoc));
    }

    /** Giảng viên chỉnh tay trạng thái một sinh viên trong buổi học. */
    public function update(Request $request, LichHoc $lichHoc): JsonResponse
    {
        $lichHoc->loadMissing('lopHoc.monHoc');
        $giangVien = $this->damBaoPhuTrach($request, $lichHoc->lopHoc);

        $data = $request->validate([
            'ma_sinh_vien' => ['required', 'integer', 'exists:sinh_vien,id'],
            'trang_thai_diem_danh' => ['required', 'in:co_mat,di_muon,vang,xin_phep,vang_co_phep,chua_diem_danh'],
        ]);

        $thuocLop = DangKyLopHoc::where('ma_lop_hoc', $lichHoc->ma_lop_hoc)
            ->where('ma_sinh_vien', $data['ma_sinh_vien'])
            ->where('trang_thai', 'da_duyet')
            ->exists();

        if (! $thuocLop) {
            return response()->json(['message' => 'Sinh viên không thuộc lớp học này.'], 422);
        }

        $phien = $lichHoc->phienDiemDanh()->latest('id')->first();

        if ($data['trang_thai_diem_danh'] === 'chua_diem_danh') {
            if ($phien) {
                ChiTietDiemDanh::where('ma_phien_diem_danh', $phien->id)
                    ->where('ma_sinh_vien', $data['ma_sinh_vien'])
                    ->delete();
            }

            $this->phatCapNhatRealtime($lichHoc, $data['ma_sinh_vien'], 'chua_diem_danh');

            return response()->json(array_merge(
                ['message' => 'Đã đưa sinh viên về trạng thái chưa điểm danh.'],
                $this->duLieuDiemDanh($lichHoc),
            ));
        }

        if (! $phien) {
            $ngayHoc = $lichHoc->ngay_hoc->format('Y-m-d');
            $batDau = Carbon::parse($ngayHoc.' '.$lichHoc->gio_bat_dau->format('H:i:s'));
            $ketThuc = Carbon::parse($ngayHoc.' '.$lichHoc->gio_ket_thuc->format('H:i:s'));
            $phien = PhienDiemDanh::create([
                'ma_phien' => 'TC'.strtoupper(Str::random(10)),
                'ma_lich_hoc' => $lichHoc->id,
                'ma_giang_vien' => $giangVien->id,
                'thoi_gian_bat_dau' => $batDau,
                'thoi_gian_ket_thuc' => $ketThuc,
                'hinh_thuc_diem_danh' => 'thu_cong',
                'trang_thai' => 'da_dong',
            ]);
        }

        $coThoiGian = in_array($data['trang_thai_diem_danh'], ['co_mat', 'di_muon'], true);
        ChiTietDiemDanh::updateOrCreate(
            [
                'ma_phien_diem_danh' => $phien->id,
                'ma_sinh_vien' => $data['ma_sinh_vien'],
            ],
            [
                'trang_thai_diem_danh' => $data['trang_thai_diem_danh'],
                'thoi_gian_diem_danh' => $coThoiGian ? now() : null,
                'hinh_thuc_diem_danh' => 'sua_thu_cong',
            ],
        );

        $this->phatCapNhatRealtime($lichHoc, $data['ma_sinh_vien'], $data['trang_thai_diem_danh']);

        return response()->json(array_merge(
            ['message' => 'Đã cập nhật điểm danh.'],
            $this->duLieuDiemDanh($lichHoc),
        ));
    }

    private function damBaoPhuTrach(Request $request, LopHoc $lopHoc)
    {
        $giangVien = $request->user()->giangVien;
        $duocPhanCong = $giangVien
            && $lopHoc->phanCong()->where('ma_giang_vien', $giangVien->id)->exists();

        abort_unless($duocPhanCong, 403, 'Bạn không phụ trách lớp học này.');

        return $giangVien;
    }

    private function duLieuDiemDanh(LichHoc $lichHoc): array
    {
        $phien = $lichHoc->phienDiemDanh()->latest('id')->first();
        $chiTiet = $phien
            ? ChiTietDiemDanh::where('ma_phien_diem_danh', $phien->id)->get()->keyBy('ma_sinh_vien')
            : collect();
        $sinhVienVangCoPhep = DonXinPhep::where('ma_lich_hoc', $lichHoc->id)
            ->where('trang_thai', 'duoc_duyet')
            ->pluck('ma_sinh_vien')
            ->flip();

        $danhSach = DangKyLopHoc::with('sinhVien.taiKhoan')
            ->where('ma_lop_hoc', $lichHoc->ma_lop_hoc)
            ->where('trang_thai', 'da_duyet')
            ->get()
            ->map(function (DangKyLopHoc $dangKy) use ($chiTiet, $sinhVienVangCoPhep): array {
                $banGhi = $chiTiet->get($dangKy->ma_sinh_vien);
                $daDuocPhepVang = $sinhVienVangCoPhep->has($dangKy->ma_sinh_vien);

                return [
                    'ma_sinh_vien' => $dangKy->ma_sinh_vien,
                    'ma_sv_text' => $dangKy->sinhVien?->ma_sinh_vien,
                    'ho_ten' => $dangKy->sinhVien?->taiKhoan?->ho_ten,
                    'trang_thai_diem_danh' => $banGhi?->trang_thai_diem_danh
                        ?? ($daDuocPhepVang ? 'vang_co_phep' : 'chua_diem_danh'),
                    'thoi_gian_diem_danh' => $banGhi?->thoi_gian_diem_danh?->format('H:i:s d/m/Y'),
                    'hinh_thuc_diem_danh' => $banGhi?->hinh_thuc_diem_danh
                        ?? ($daDuocPhepVang ? 'don_xin_phep' : null),
                ];
            })
            ->values();

        return [
            'lop_hoc' => [
                'id' => $lichHoc->lopHoc?->id,
                'ten_lop' => $lichHoc->lopHoc?->ten_lop,
                'mon_hoc' => $lichHoc->lopHoc?->monHoc?->ten_mon,
            ],
            'lich_hoc' => [
                'id' => $lichHoc->id,
                'ngay_hoc' => $lichHoc->ngay_hoc?->format('Y-m-d'),
                'gio_bat_dau' => $lichHoc->gio_bat_dau?->format('H:i'),
                'gio_ket_thuc' => $lichHoc->gio_ket_thuc?->format('H:i'),
                'chu_de' => $lichHoc->chu_de,
            ],
            'phien' => $phien ? [
                'id' => $phien->id,
                'ma_phien' => $phien->ma_phien,
                'trang_thai' => $phien->trang_thai,
            ] : null,
            'phong' => ($phong = PhongHocTrucTuyen::where('ma_lich_hoc', $lichHoc->id)
                ->where('trang_thai', 'dang_dien_ra')
                ->first()) ? ['ma_phong' => $phong->ma_phong] : null,
            'danh_sach' => $danhSach,
        ];
    }

    private function phatCapNhatRealtime(LichHoc $lichHoc, int $maSinhVien, string $trangThai): void
    {
        $phong = PhongHocTrucTuyen::where('ma_lich_hoc', $lichHoc->id)
            ->where('trang_thai', 'dang_dien_ra')
            ->first();

        if ($phong) {
            broadcast(new TrangThaiDiemDanhCapNhat($phong->ma_phong, $maSinhVien, $trangThai));
        }
    }
}
