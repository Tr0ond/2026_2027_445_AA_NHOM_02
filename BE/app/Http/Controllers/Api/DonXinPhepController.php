<?php

namespace App\Http\Controllers\Api;

use App\Events\TrangThaiDiemDanhCapNhat;
use App\Http\Controllers\Controller;
use App\Models\ChiTietDiemDanh;
use App\Models\DonXinPhep;
use App\Models\LichHoc;
use App\Models\PhienDiemDanh;
use App\Models\PhongHocTrucTuyen;
use App\Services\ThongBaoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonXinPhepController extends Controller
{
    /** US10 - Danh sách đơn xin phép (sinh viên: của mình; giảng viên/admin: các lớp phụ trách). */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = DonXinPhep::with(['sinhVien.taiKhoan', 'lopHoc.monHoc', 'lichHoc', 'nguoiDuyet']);

        if ($user->laSinhVien() && $user->sinhVien) {
            $query->where('ma_sinh_vien', $user->sinhVien->id);
        } elseif ($user->laGiangVien() && $user->giangVien) {
            $maLops = $user->giangVien->phanCong()->pluck('ma_lop_hoc');
            $query->whereIn('ma_lop_hoc', $maLops);
        }

        $danhSach = $query->orderByDesc('created_at')->get()
            ->map(fn ($don) => [
                'id' => $don->id,
                'ma_sinh_vien_text' => $don->sinhVien?->ma_sinh_vien,
                'sinh_vien' => $don->sinhVien?->taiKhoan?->ho_ten,
                'lop_hoc' => $don->lopHoc?->ten_lop,
                'mon_hoc' => $don->lopHoc?->monHoc?->ten_mon,
                'ma_lich_hoc' => $don->ma_lich_hoc,
                'buoi_hoc' => $don->lichHoc
                    ? $don->lichHoc->ngay_hoc?->format('d/m/Y').' '.$don->lichHoc->gio_bat_dau?->format('H:i')
                    : null,
                'ngay_nghi' => $don->ngay_nghi?->format('d/m/Y'),
                'ly_do' => $don->ly_do,
                'trang_thai' => $don->trang_thai,
                'nguoi_duyet' => $don->nguoiDuyet?->ho_ten,
                'thoi_gian_duyet' => $don->thoi_gian_duyet?->format('H:i d/m/Y'),
            ]);

        return response()->json(['danh_sach' => $danhSach]);
    }

    /** US10 - Sinh viên gửi yêu cầu xin phép vắng. */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $sinhVien = $user->sinhVien;

        if (! $sinhVien) {
            return response()->json(['message' => 'Tài khoản không phải sinh viên.'], 403);
        }

        $data = $request->validate([
            'ma_lop_hoc' => ['required', 'integer', 'exists:lop_hoc,id'],
            'ma_lich_hoc' => ['nullable', 'integer', 'exists:lich_hoc,id'],
            'ngay_nghi' => ['required', 'date', 'after_or_equal:today'],
            'ly_do' => ['required', 'string', 'max:500'],
        ], [
            'ngay_nghi.after_or_equal' => 'Ngày nghỉ phải từ hôm nay trở đi.',
            'ly_do.required' => 'Vui lòng nhập lý do.',
        ]);

        $daDangKy = $sinhVien->dangKyLopHoc()
            ->where('ma_lop_hoc', $data['ma_lop_hoc'])
            ->where('trang_thai', 'da_duyet')
            ->exists();

        if (! $daDangKy) {
            return response()->json(['message' => 'Bạn không thuộc lớp học này.'], 422);
        }

        $lichHoc = null;
        if (! empty($data['ma_lich_hoc'])) {
            $lichHoc = LichHoc::where('id', $data['ma_lich_hoc'])
                ->where('ma_lop_hoc', $data['ma_lop_hoc'])
                ->whereDate('ngay_hoc', $data['ngay_nghi'])
                ->first();

            if (! $lichHoc) {
                return response()->json(['message' => 'Buổi học không thuộc lớp hoặc không khớp ngày nghỉ.'], 422);
            }
        } else {
            $lichHocs = LichHoc::where('ma_lop_hoc', $data['ma_lop_hoc'])
                ->whereDate('ngay_hoc', $data['ngay_nghi'])
                ->orderBy('gio_bat_dau')
                ->get();

            if ($lichHocs->count() !== 1) {
                return response()->json([
                    'message' => $lichHocs->isEmpty()
                        ? 'Ngày nghỉ không có buổi học tương ứng.'
                        : 'Ngày nghỉ có nhiều buổi học, vui lòng chọn đúng buổi học.',
                ], 422);
            }

            $lichHoc = $lichHocs->first();
        }

        $daTonTai = DonXinPhep::where('ma_sinh_vien', $sinhVien->id)
            ->where('ma_lich_hoc', $lichHoc->id)
            ->whereIn('trang_thai', ['cho_duyet', 'duoc_duyet'])
            ->exists();

        if ($daTonTai) {
            return response()->json(['message' => 'Bạn đã gửi đơn xin phép cho buổi học này.'], 422);
        }

        DonXinPhep::create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $data['ma_lop_hoc'],
            'ma_lich_hoc' => $lichHoc->id,
            'ngay_nghi' => $data['ngay_nghi'],
            'ly_do' => $data['ly_do'],
            'trang_thai' => 'cho_duyet',
        ]);

        return response()->json(['message' => 'Đã gửi yêu cầu xin phép vắng.'], 201);
    }

    /** Giảng viên/admin duyệt hoặc từ chối đơn. */
    public function duyet(Request $request, DonXinPhep $don): JsonResponse
    {
        $data = $request->validate([
            'trang_thai' => ['required', 'in:duoc_duyet,tu_choi'],
        ]);

        if ($don->trang_thai !== 'cho_duyet') {
            return response()->json(['message' => 'Đơn đã được xử lý.'], 422);
        }

        $user = $request->user();
        if ($user->laGiangVien()) {
            $duocPhanCong = $user->giangVien
                && $don->lopHoc()
                    ->whereHas('phanCong', fn ($query) => $query->where('ma_giang_vien', $user->giangVien->id))
                    ->exists();

            if (! $duocPhanCong) {
                return response()->json(['message' => 'Bạn không phụ trách lớp học của đơn này.'], 403);
            }
        }

        DB::transaction(function () use ($don, $data, $request): void {
            $don->update([
                'trang_thai' => $data['trang_thai'],
                'nguoi_duyet' => $request->user()->id,
                'thoi_gian_duyet' => now(),
            ]);

            if ($data['trang_thai'] !== 'duoc_duyet') {
                return;
            }

            // Nếu phiên đã được mở, cập nhật ngay trạng thái vắng có phép.
            // Nếu phiên chưa mở, PhienDiemDanhController::dong() sẽ áp dụng
            // cùng quy tắc khi chốt danh sách.
            $phienIds = PhienDiemDanh::where('ma_lich_hoc', $don->ma_lich_hoc)->pluck('id');
            foreach ($phienIds as $phienId) {
                ChiTietDiemDanh::updateOrCreate(
                    [
                        'ma_phien_diem_danh' => $phienId,
                        'ma_sinh_vien' => $don->ma_sinh_vien,
                    ],
                    [
                        'trang_thai_diem_danh' => 'vang_co_phep',
                        'thoi_gian_diem_danh' => null,
                        'hinh_thuc_diem_danh' => 'sua_thu_cong',
                    ]
                );
            }
        });

        $don->loadMissing(['sinhVien.taiKhoan', 'lopHoc.monHoc']);

        if ($data['trang_thai'] === 'duoc_duyet') {
            $phong = PhongHocTrucTuyen::where('ma_lich_hoc', $don->ma_lich_hoc)
                ->where('trang_thai', 'dang_dien_ra')
                ->first();

            if ($phong) {
                broadcast(new TrangThaiDiemDanhCapNhat(
                    $phong->ma_phong,
                    $don->ma_sinh_vien,
                    'vang_co_phep',
                ));
            }
        }

        $taiKhoanSinhVien = $don->sinhVien?->ma_tai_khoan;
        if ($taiKhoanSinhVien) {
            app(ThongBaoService::class)->tao(
                $taiKhoanSinhVien,
                'don_xin_phep',
                $data['trang_thai'] === 'duoc_duyet' ? 'Đơn xin phép đã được duyệt' : 'Đơn xin phép bị từ chối',
                $data['trang_thai'] === 'duoc_duyet'
                    ? "Đơn xin phép nghỉ {$don->ngay_nghi?->format('d/m/Y')} môn {$don->lopHoc?->monHoc?->ten_mon} đã được duyệt."
                    : "Đơn xin phép nghỉ {$don->ngay_nghi?->format('d/m/Y')} đã bị từ chối.",
                [
                    'don_id' => $don->id,
                    'ma_lich_hoc' => $don->ma_lich_hoc,
                    'trang_thai' => $don->trang_thai,
                ],
            );
        }

        return response()->json(['message' => 'Đã xử lý đơn xin phép.']);
    }
}
