<?php

namespace App\Http\Controllers\Api;

use App\Events\CapQuyenPhong;
use App\Events\NguoiChiaSeManHinh;
use App\Events\PhienDiemDanhDong;
use App\Events\PhongHocKetThuc;
use App\Events\SinhVienGioTay;
use App\Events\ThanhVienPhongCapNhat;
use App\Http\Controllers\Controller;
use App\Models\DonXinPhep;
use App\Models\LichHoc;
use App\Models\PhienDiemDanh;
use App\Models\PhongHocTrucTuyen;
use App\Models\ThanhVienPhongTrucTuyen;
use App\Services\AgoraService;
use App\Services\QuyenPhongService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PhongHocController extends Controller
{
    public function __construct(private QuyenPhongService $quyenPhong) {}

    /** US12 - Giảng viên bấm "bắt đầu buổi dạy": tạo kênh Agora + ghi phong_hoc_truc_tuyen. */
    public function batDau(Request $request, AgoraService $agora): JsonResponse
    {
        $data = $request->validate([
            'ma_lich_hoc' => ['required', 'integer', 'exists:lich_hoc,id'],
        ]);

        // Khóa buổi học để hai lần bấm mở đồng thời không tạo hai phòng.
        [$phong, $moiTao, $thongTinAgora] = DB::transaction(function () use ($data, $request, $agora) {
            $lichHoc = LichHoc::with('lopHoc')->lockForUpdate()->findOrFail($data['ma_lich_hoc']);
            abort_unless($this->quyenPhong->phuTrach($request->user(), $lichHoc), 403, 'Bạn không phụ trách lớp học này.');
            abort_unless($lichHoc->co_hoc_truc_tuyen, 422, 'Buổi học này không được tổ chức trực tuyến.');
            abort_if(in_array($lichHoc->trang_thai, ['da_hoc', 'da_huy'], true), 422, 'Buổi học đã hoàn tất hoặc bị hủy.');

            $phong = $lichHoc->phongTrucTuyen()->first();
            $moiTao = ! $phong;
            if ($phong) {
                abort_unless($phong->trang_thai === 'dang_dien_ra', 422, 'Phòng học đã kết thúc.');
                $phong->update(['nen_tang' => 'Agora', 'duong_dan_tham_gia' => 'agora://'.$phong->ma_phong]);
            } else {
                $maPhong = 'PH'.strtoupper(Str::random(8));
                $phong = PhongHocTrucTuyen::create([
                    'ma_phong' => $maPhong,
                    'ma_lich_hoc' => $lichHoc->id,
                    'duong_dan_tham_gia' => 'agora://'.$maPhong,
                    'nen_tang' => 'Agora',
                    'trang_thai' => 'dang_dien_ra',
                ]);
            }

            $lichHoc->update(['trang_thai' => 'dang_dien_ra']);
            $this->ghiNhanThamGia($phong, $request);

            return [$phong, $moiTao, $agora->thongTinThamGia($phong->ma_phong, $request->user()->id)];
        });

        broadcast(new ThanhVienPhongCapNhat($phong->ma_phong, $request->user()->id, 'tham_gia'));

        return response()->json([
            'message' => $moiTao ? 'Đã mở phòng học trực tuyến.' : 'Buổi học này đã có phòng đang diễn ra.',
            'phong' => $this->duLieuPhong($phong),
            'thong_tin_agora' => $thongTinAgora,
        ], $moiTao ? 201 : 200);
    }

    /** US06 - Thành viên tham gia phòng: ghi thanh_vien_phong_truc_tuyen + trả thông tin Agora. */
    public function thamGia(Request $request, AgoraService $agora, string $maPhong): JsonResponse
    {
        $user = $request->user();

        $phong = PhongHocTrucTuyen::with('lichHoc.lopHoc')
            ->where('ma_phong', $maPhong)
            ->firstOrFail();

        abort_unless($this->quyenPhong->duocThamGia($user, $phong), 403, 'Bạn không thuộc lớp học này.');

        if ($phong->trang_thai !== 'dang_dien_ra') {
            return response()->json(['message' => 'Phòng học đã kết thúc.'], 422);
        }

        $vaiTro = $user->laGiangVien() ? 'giang_vien' : 'sinh_vien';

        // Sinh token trước khi ghi thành viên để cấu hình sai không tạo lần vào phòng giả.
        $thongTinAgora = $agora->thongTinThamGia($phong->ma_phong, $user->id);
        $thanhVien = $this->ghiNhanThamGia($phong, $request);
        broadcast(new ThanhVienPhongCapNhat($phong->ma_phong, $user->id, 'tham_gia'));

        $phienDiemDanh = PhienDiemDanh::where('ma_lich_hoc', $phong->ma_lich_hoc)
            ->where('trang_thai', 'dang_mo')
            ->where('thoi_gian_ket_thuc', '>', now())
            ->latest('id')
            ->first();

        $duLieuPhien = null;
        if ($phienDiemDanh) {
            $qrToken = $phienDiemDanh->qrTokens()
                ->where('het_han_luc', '>', now())
                ->latest('id')
                ->first();
            $sinhVienVangCoPhep = $phienDiemDanh->chiTiet()
                ->where('trang_thai_diem_danh', 'vang_co_phep')
                ->pluck('ma_sinh_vien')
                ->values()
                ->all();

            $duLieuPhien = [
                'id' => $phienDiemDanh->id,
                'ma_phien' => $phienDiemDanh->ma_phien,
                'duong_dan_qr' => $qrToken
                    ? rtrim(config('app.fe_url', env('FE_URL', 'http://localhost:5173')), '/').'/diem-danh/'.$qrToken->token
                    : null,
                'so_giay' => max(0, (int) now()->diffInSeconds($phienDiemDanh->thoi_gian_ket_thuc)),
                'qr_het_han_luc' => $qrToken?->het_han_luc?->toIso8601String(),
                'thoi_gian_ket_thuc' => $phienDiemDanh->thoi_gian_ket_thuc->toIso8601String(),
                'sinh_vien_vang_co_phep' => $sinhVienVangCoPhep,
            ];
        }

        $trangThaiDiemDanhCuaToi = null;
        if ($user->laSinhVien() && $user->sinhVien) {
            $daDuocPhepVang = DonXinPhep::where('ma_lich_hoc', $phong->ma_lich_hoc)
                ->where('ma_sinh_vien', $user->sinhVien->id)
                ->where('trang_thai', 'duoc_duyet')
                ->exists();
            $trangThaiDiemDanhCuaToi = $daDuocPhepVang ? 'vang_co_phep' : null;
        }

        return response()->json([
            'phong' => $this->duLieuPhong($phong),
            'vai_tro' => $vaiTro,
            'kenh_websocket' => 'phong.'.$phong->ma_phong,
            'thong_tin_agora' => $thongTinAgora,
            'phien_diem_danh' => $duLieuPhien,
            'trang_thai_diem_danh_cua_toi' => $trangThaiDiemDanhCuaToi,
            // Giảng viên luôn có đủ quyền; sinh viên theo quyền GV đã cấp
            'quyen' => [
                'la_giang_vien' => $user->laGiangVien(),
                'mac' => $user->laGiangVien() || $thanhVien->duoc_phep_mac,
                'chia_se' => $user->laGiangVien() || $thanhVien->duoc_phep_chia_se,
                'gio_tay' => $thanhVien->gio_tay,
            ],
        ]);
    }

    /** Sinh viên giơ tay / hạ tay trong phòng. */
    public function gioTay(Request $request, string $maPhong): JsonResponse
    {
        $user = $request->user();
        if (! $user->laSinhVien()) {
            return response()->json(['message' => 'Chỉ sinh viên mới giơ tay.'], 403);
        }

        $phong = PhongHocTrucTuyen::where('ma_phong', $maPhong)->firstOrFail();

        $thanhVien = $this->quyenPhong->thanhVienDangThamGia($user, $phong);

        $thanhVien->update(['gio_tay' => ! $thanhVien->gio_tay]);

        broadcast(new SinhVienGioTay(
            $phong->ma_phong,
            $user->id,
            $user->ho_ten,
            $thanhVien->gio_tay,
        ));

        return response()->json([
            'message' => $thanhVien->gio_tay ? 'Đã giơ tay.' : 'Đã hạ tay.',
            'dang_gio' => $thanhVien->gio_tay,
        ]);
    }

    /** Giảng viên cấp/thu hồi quyền dùng micro và chia sẻ màn hình cho sinh viên. */
    public function capQuyen(Request $request, string $maPhong): JsonResponse
    {
        $data = $request->validate([
            'ma_tai_khoan' => ['required', 'integer'],
            'duoc_phep_mac' => ['required', 'boolean'],
            'duoc_phep_chia_se' => ['required', 'boolean'],
        ]);

        $phong = PhongHocTrucTuyen::where('ma_phong', $maPhong)->firstOrFail();
        $this->quyenPhong->kiemTraQuanLy($request->user(), $phong);
        $this->quyenPhong->thanhVienDangThamGia($request->user(), $phong);

        $thanhVien = ThanhVienPhongTrucTuyen::with('taiKhoan')
            ->where('ma_phong_hoc_truc_tuyen', $phong->id)
            ->where('ma_tai_khoan', $data['ma_tai_khoan'])
            ->where('vai_tro', 'sinh_vien')
            ->whereNull('thoi_gian_roi')
            ->first();

        if (! $thanhVien || ! $thanhVien->taiKhoan || ! $this->quyenPhong->duocThamGia($thanhVien->taiKhoan, $phong)) {
            return response()->json(['message' => 'Sinh viên không có trong phòng.'], 404);
        }

        $thanhVien->update([
            'duoc_phep_mac' => $data['duoc_phep_mac'],
            'duoc_phep_chia_se' => $data['duoc_phep_chia_se'],
        ]);

        // Cấp quyền thì bỏ trạng thái giơ tay
        if ($data['duoc_phep_mac'] || $data['duoc_phep_chia_se']) {
            $thanhVien->update(['gio_tay' => false]);
        }

        // Thu hồi quyền chia sẻ trong khi đang chia sẻ → dừng chia sẻ luôn
        if (! $data['duoc_phep_chia_se'] && $thanhVien->dang_chia_se) {
            $thanhVien->update(['dang_chia_se' => false]);
            broadcast(new NguoiChiaSeManHinh(
                $phong->ma_phong,
                $thanhVien->ma_tai_khoan,
                $thanhVien->taiKhoan?->ho_ten ?? '',
                false,
            ));
        }

        broadcast(new CapQuyenPhong(
            $phong->ma_phong,
            $thanhVien->ma_tai_khoan,
            $thanhVien->taiKhoan?->ho_ten ?? '',
            $thanhVien->duoc_phep_mac,
            $thanhVien->duoc_phep_chia_se,
        ));

        return response()->json(['message' => 'Đã cập nhật quyền.']);
    }

    /** Báo trạng thái chia sẻ màn hình để cả phòng biết ai đang chia sẻ (như Zoom). */
    public function chiaSeTrangThai(Request $request, string $maPhong): JsonResponse
    {
        $data = $request->validate([
            'dang_chia_se' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $phong = PhongHocTrucTuyen::where('ma_phong', $maPhong)->firstOrFail();

        $thanhVien = $this->quyenPhong->thanhVienDangThamGia($user, $phong);

        // Sinh viên chỉ được báo "đang chia sẻ" nếu còn quyền
        if ($data['dang_chia_se'] && ! $user->laGiangVien() && ! $thanhVien->duoc_phep_chia_se) {
            return response()->json(['message' => 'Bạn chưa được cấp quyền chia sẻ màn hình.'], 403);
        }

        $thanhVien->update(['dang_chia_se' => $data['dang_chia_se']]);

        broadcast(new NguoiChiaSeManHinh($phong->ma_phong, $user->id, $user->ho_ten, $data['dang_chia_se']));

        return response()->json(['message' => 'Đã cập nhật trạng thái chia sẻ.']);
    }

    /** Thành viên rời phòng. */
    public function roiPhong(Request $request, string $maPhong): JsonResponse
    {
        $phong = PhongHocTrucTuyen::where('ma_phong', $maPhong)->firstOrFail();

        // Cho phép gửi lại yêu cầu rời phòng, kể cả sau khi GV đã kết thúc.
        abort_unless($this->quyenPhong->duocThamGia($request->user(), $phong), 403, 'Bạn không thuộc lớp học này.');
        $thanhVien = ThanhVienPhongTrucTuyen::where('ma_phong_hoc_truc_tuyen', $phong->id)
            ->where('ma_tai_khoan', $request->user()->id)
            ->whereNull('thoi_gian_roi')->first();
        if ($thanhVien) {
            $thanhVien->update(['thoi_gian_roi' => now(), 'gio_tay' => false, 'dang_chia_se' => false, 'duoc_phep_mac' => false, 'duoc_phep_chia_se' => false]);
            broadcast(new NguoiChiaSeManHinh($phong->ma_phong, $request->user()->id, $request->user()->ho_ten, false));
            broadcast(new ThanhVienPhongCapNhat($phong->ma_phong, $request->user()->id, 'roi'));
        }

        return response()->json(['message' => 'Đã rời phòng.']);
    }

    /** US13 - Danh sách thành viên đang trong phòng. */
    public function thanhVien(Request $request, string $maPhong): JsonResponse
    {
        $phong = PhongHocTrucTuyen::with(['thanhVien.taiKhoan', 'thanhVien.taiKhoan.sinhVien'])
            ->where('ma_phong', $maPhong)
            ->firstOrFail();

        $this->quyenPhong->thanhVienDangThamGia($request->user(), $phong);

        $danhSach = $phong->thanhVien
            ->whereNull('thoi_gian_roi')
            ->values()
            ->map(fn ($tv) => [
                'ma_tai_khoan' => $tv->ma_tai_khoan,
                'ho_ten' => $tv->taiKhoan?->ho_ten,
                'ma_sinh_vien_text' => $tv->taiKhoan?->sinhVien?->ma_sinh_vien,
                'vai_tro' => $tv->vai_tro,
                'thoi_gian_tham_gia' => $tv->thoi_gian_tham_gia?->format('H:i d/m/Y'),
                'gio_tay' => (bool) $tv->gio_tay,
                'duoc_phep_mac' => (bool) $tv->duoc_phep_mac,
                'duoc_phep_chia_se' => (bool) $tv->duoc_phep_chia_se,
                'dang_chia_se' => (bool) $tv->dang_chia_se,
            ]);

        return response()->json(['danh_sach' => $danhSach]);
    }

    /** US12/US13 - Kết thúc phòng học: đóng phòng, đóng phiên điểm danh đang mở, cập nhật lịch. */
    public function ketThuc(Request $request, string $maPhong): JsonResponse
    {
        $phong = PhongHocTrucTuyen::with('lichHoc')
            ->where('ma_phong', $maPhong)
            ->firstOrFail();

        $lichHoc = $phong->lichHoc;
        $this->quyenPhong->kiemTraQuanLy($request->user(), $phong, choAdmin: true);
        if ($phong->trang_thai === 'da_ket_thuc') {
            return response()->json(['message' => 'Phòng đã kết thúc trước đó.']);
        }

        // Đóng phiên điểm danh còn mở (tự đánh dấu vắng)
        $phienMo = PhienDiemDanh::where('ma_lich_hoc', $phong->ma_lich_hoc)
            ->where('trang_thai', 'dang_mo')
            ->get();

        foreach ($phienMo as $phien) {
            app(PhienDiemDanhController::class)->dong($request, $phien);
        }

        $phong->update(['trang_thai' => 'da_ket_thuc']);
        $lichHoc?->update(['trang_thai' => 'da_hoc']);

        broadcast(new PhienDiemDanhDong($phong->ma_phong, 'tat_ca'));

        ThanhVienPhongTrucTuyen::where('ma_phong_hoc_truc_tuyen', $phong->id)
            ->whereNull('thoi_gian_roi')
            ->update(['thoi_gian_roi' => now(), 'gio_tay' => false, 'dang_chia_se' => false, 'duoc_phep_mac' => false, 'duoc_phep_chia_se' => false]);

        broadcast(new PhongHocKetThuc($phong->ma_phong, $phong->ma_lich_hoc));

        return response()->json(['message' => 'Đã kết thúc phòng học.']);
    }

    private function ghiNhanThamGia(PhongHocTrucTuyen $phong, Request $request): ThanhVienPhongTrucTuyen
    {
        $thanhVien = ThanhVienPhongTrucTuyen::firstOrNew([
            'ma_phong_hoc_truc_tuyen' => $phong->id,
            'ma_tai_khoan' => $request->user()->id,
        ]);
        if (! $thanhVien->exists || $thanhVien->thoi_gian_roi !== null) {
            $thanhVien->fill([
                'vai_tro' => $request->user()->laGiangVien() ? 'giang_vien' : 'sinh_vien',
                'thoi_gian_tham_gia' => now(), 'thoi_gian_roi' => null,
                'gio_tay' => false, 'dang_chia_se' => false,
                'duoc_phep_mac' => false, 'duoc_phep_chia_se' => false,
            ]);
            $thanhVien->save();
        }

        return $thanhVien;
    }

    private function duLieuPhong(PhongHocTrucTuyen $phong): array
    {
        $phong->loadMissing('lichHoc.lopHoc.monHoc');

        return [
            'id' => $phong->id,
            'ma_phong' => $phong->ma_phong,
            'ma_lich_hoc' => $phong->ma_lich_hoc,
            'duong_dan_tham_gia' => $phong->duong_dan_tham_gia,
            'nen_tang' => $phong->nen_tang,
            'trang_thai' => $phong->trang_thai,
            'ten_lop' => $phong->lichHoc?->lopHoc?->ten_lop,
            'mon_hoc' => $phong->lichHoc?->lopHoc?->monHoc?->ten_mon,
            'ngay_hoc' => $phong->lichHoc?->ngay_hoc?->format('d/m/Y'),
            'gio_bat_dau' => $phong->lichHoc?->gio_bat_dau?->format('H:i'),
        ];
    }
}
