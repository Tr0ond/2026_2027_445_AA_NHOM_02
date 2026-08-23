<?php

namespace App\Http\Controllers\Api;

use App\Events\MaQrDiemDanhCapNhat;
use App\Events\PhienDiemDanhDong;
use App\Events\PhienDiemDanhMo;
use App\Http\Controllers\Controller;
use App\Models\ChiTietDiemDanh;
use App\Models\DangKyLopHoc;
use App\Models\DonXinPhep;
use App\Models\GiangVien;
use App\Models\LichHoc;
use App\Models\MaQrToken;
use App\Models\PhienDiemDanh;
use App\Models\PhongHocTrucTuyen;
use App\Services\ThongBaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PhienDiemDanhController extends Controller
{
    /**
     * US14 - Giảng viên tạo phiên điểm danh và sinh QR token ngắn hạn.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ma_lich_hoc' => ['required', 'integer', 'exists:lich_hoc,id'],
            'so_phut' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        /** @var GiangVien $giangVien */
        $giangVien = $request->user()->giangVien;

        if (! $giangVien) {
            return response()->json(['message' => 'Chỉ giảng viên phụ trách mới được mở phiên.'], 403);
        }

        $lichHoc = LichHoc::with('lopHoc.monHoc')->findOrFail($data['ma_lich_hoc']);

        $duocPhanCong = $lichHoc->lopHoc?->phanCong()
            ->where('ma_giang_vien', $giangVien->id)
            ->exists();

        if (! $duocPhanCong) {
            return response()->json(['message' => 'Bạn không phụ trách lớp học này.'], 403);
        }

        // Đóng các phiên cũ còn mở của buổi học này
        PhienDiemDanh::where('ma_lich_hoc', $lichHoc->id)
            ->where('trang_thai', 'dang_mo')
            ->update(['trang_thai' => 'da_dong']);

        $soPhut = $data['so_phut'] ?? 5;
        $batDau = now();
        $ketThuc = $batDau->copy()->addMinutes($soPhut);

        $phien = PhienDiemDanh::create([
            'ma_phien' => 'PD'.strtoupper(Str::random(8)),
            'ma_lich_hoc' => $lichHoc->id,
            'ma_giang_vien' => $giangVien->id,
            'thoi_gian_bat_dau' => $batDau,
            'thoi_gian_ket_thuc' => $ketThuc,
            'hinh_thuc_diem_danh' => 'qr_code',
            'trang_thai' => 'dang_mo',
        ]);

        // Sinh viên đã được duyệt phép được ghi nhận ngay khi mở phiên,
        // không phải chờ đóng phiên và không cần quét QR.
        $sinhVienVangCoPhep = DonXinPhep::where('ma_lich_hoc', $lichHoc->id)
            ->where('trang_thai', 'duoc_duyet')
            ->pluck('ma_sinh_vien')
            ->unique()
            ->values();

        foreach ($sinhVienVangCoPhep as $maSinhVien) {
            ChiTietDiemDanh::updateOrCreate(
                [
                    'ma_phien_diem_danh' => $phien->id,
                    'ma_sinh_vien' => $maSinhVien,
                ],
                [
                    'trang_thai_diem_danh' => 'vang_co_phep',
                    'thoi_gian_diem_danh' => null,
                    'hinh_thuc_diem_danh' => 'sua_thu_cong',
                ],
            );
        }

        $qrToken = $this->taoQrToken($phien);
        $duongDanQr = $this->duongDanQr($qrToken->token);

        $phong = PhongHocTrucTuyen::where('ma_lich_hoc', $lichHoc->id)
            ->where('trang_thai', 'dang_dien_ra')
            ->first();

        if ($phong) {
            broadcast(new PhienDiemDanhMo(
                $phong->ma_phong,
                $phien->ma_phien,
                $duongDanQr,
                $qrToken->het_han_luc->toIso8601String(),
                $batDau->toIso8601String(),
                $ketThuc->toIso8601String(),
                $soPhut * 60,
                $sinhVienVangCoPhep->all(),
            ));
        }

        $sinhViens = DangKyLopHoc::with('sinhVien')
            ->where('ma_lop_hoc', $lichHoc->ma_lop_hoc)
            ->where('trang_thai', 'da_duyet')
            ->get();
        foreach ($sinhViens as $dangKy) {
            if ($dangKy->sinhVien?->ma_tai_khoan) {
                $daDuocPhepVang = $sinhVienVangCoPhep->contains($dangKy->ma_sinh_vien);
                app(ThongBaoService::class)->tao(
                    $dangKy->sinhVien->ma_tai_khoan,
                    'phien_diem_danh',
                    $daDuocPhepVang ? 'Đã ghi nhận vắng có phép' : 'Đã mở phiên điểm danh',
                    $daDuocPhepVang
                        ? "Bạn đã được duyệt phép vắng môn {$lichHoc->lopHoc?->monHoc?->ten_mon} và không cần quét QR."
                        : "Giảng viên đã mở điểm danh môn {$lichHoc->lopHoc?->monHoc?->ten_mon}.",
                    [
                        'ma_phien' => $phien->ma_phien,
                        'ma_lich_hoc' => $lichHoc->id,
                        'qr_het_han_luc' => $qrToken->het_han_luc->toIso8601String(),
                    ],
                );
            }
        }

        return response()->json([
            'message' => 'Đã mở phiên điểm danh.',
            'phien' => [
                'id' => $phien->id,
                'ma_phien' => $phien->ma_phien,
                'qr_token' => $qrToken->token,
                'duong_dan_qr' => $duongDanQr,
                'qr_het_han_luc' => $qrToken->het_han_luc->toIso8601String(),
                'thoi_gian_bat_dau' => $batDau->toIso8601String(),
                'thoi_gian_ket_thuc' => $ketThuc->toIso8601String(),
                'so_giay' => $soPhut * 60,
                'trang_thai' => $phien->trang_thai,
            ],
        ], 201);
    }

    /** Sinh QR token mới và đồng bộ cho mọi thành viên trong phòng; token chỉ sống 10 giây. */
    public function tokenQr(Request $request, PhienDiemDanh $phien): JsonResponse
    {
        $phien->loadMissing('lichHoc.lopHoc');
        $giangVien = $request->user()->giangVien;
        $duocPhanCong = $request->user()->laAdmin();
        if (! $duocPhanCong && $giangVien && $phien->lichHoc?->lopHoc) {
            $duocPhanCong = $phien->lichHoc->lopHoc->phanCong()
                ->where('ma_giang_vien', $giangVien->id)
                ->exists();
        }

        if (! $duocPhanCong) {
            return response()->json(['message' => 'Bạn không có quyền quản lý phiên này.'], 403);
        }

        if (! $phien->conMo()) {
            return response()->json(['message' => 'Phiên điểm danh đã đóng hoặc hết hạn.'], 422);
        }

        $qrToken = $this->taoQrToken($phien);
        $duongDanQr = $this->duongDanQr($qrToken->token);

        $phong = PhongHocTrucTuyen::where('ma_lich_hoc', $phien->ma_lich_hoc)
            ->where('trang_thai', 'dang_dien_ra')
            ->first();

        if ($phong) {
            broadcast(new MaQrDiemDanhCapNhat(
                $phong->ma_phong,
                $phien->ma_phien,
                $duongDanQr,
                $qrToken->het_han_luc->toIso8601String(),
            ));
        }

        return response()->json([
            'qr_token' => $qrToken->token,
            'duong_dan_qr' => $duongDanQr,
            'qr_het_han_luc' => $qrToken->het_han_luc->toIso8601String(),
        ]);
    }

    /** Tra cứu phiên theo mã phiên (FE dùng khi nhận event realtime). */
    public function danhSachTheoMa(string $maPhien): JsonResponse
    {
        $phien = PhienDiemDanh::where('ma_phien', $maPhien)->firstOrFail();

        return $this->danhSach($phien);
    }

    /** US16 - Danh sách điểm danh của phiên: toàn bộ sinh viên lớp + trạng thái. */
    public function danhSach(PhienDiemDanh $phien): JsonResponse
    {
        $phien->load('lichHoc.lopHoc');

        $lopHoc = $phien->lichHoc->lopHoc;

        $sinhViens = DangKyLopHoc::with(['sinhVien.taiKhoan'])
            ->where('ma_lop_hoc', $lopHoc->id)
            ->where('trang_thai', 'da_duyet')
            ->get()
            ->map(function ($dk) use ($phien) {
                $ct = $phien->chiTiet->firstWhere('ma_sinh_vien', $dk->ma_sinh_vien);

                return [
                    'ma_sinh_vien' => $dk->ma_sinh_vien,
                    'ma_sv_text' => $dk->sinhVien?->ma_sinh_vien,
                    'ho_ten' => $dk->sinhVien?->taiKhoan?->ho_ten,
                    'trang_thai_diem_danh' => $ct?->trang_thai_diem_danh ?? 'chua_diem_danh',
                    'thoi_gian_diem_danh' => $ct?->thoi_gian_diem_danh?->format('H:i:s d/m/Y'),
                    'hinh_thuc_diem_danh' => $ct->hinh_thuc_diem_danh ?? null,
                ];
            })
            ->values();

        return response()->json([
            'phien' => [
                'id' => $phien->id,
                'ma_phien' => $phien->ma_phien,
                'trang_thai' => $phien->trang_thai,
                'thoi_gian_bat_dau' => $phien->thoi_gian_bat_dau->toIso8601String(),
                'thoi_gian_ket_thuc' => $phien->thoi_gian_ket_thuc->toIso8601String(),
                'da_het_han' => $phien->trang_thai === 'da_dong' || now()->gt($phien->thoi_gian_ket_thuc),
            ],
            'danh_sach' => $sinhViens,
        ]);
    }

    /** US15 - Điểm danh thủ công cho một sinh viên. */
    public function diemDanhThuCong(Request $request, PhienDiemDanh $phien): JsonResponse
    {
        $data = $request->validate([
            'ma_sinh_vien' => ['required', 'integer', 'exists:sinh_vien,id'],
            'trang_thai' => ['nullable', 'in:co_mat,di_muon,vang,xin_phep,vang_co_phep'],
        ]);

        if ($phien->trang_thai !== 'dang_mo') {
            return response()->json(['message' => 'Phiên đã đóng.'], 422);
        }

        ChiTietDiemDanh::updateOrCreate(
            [
                'ma_phien_diem_danh' => $phien->id,
                'ma_sinh_vien' => $data['ma_sinh_vien'],
            ],
            [
                'trang_thai_diem_danh' => $data['trang_thai'] ?? 'co_mat',
                'thoi_gian_diem_danh' => now(),
                'hinh_thuc_diem_danh' => 'thu_cong',
            ]
        );

        return response()->json(['message' => 'Đã điểm danh thủ công.']);
    }

    /** US16 - Sửa trạng thái điểm danh của một sinh viên. */
    public function suaTrangThai(Request $request, PhienDiemDanh $phien): JsonResponse
    {
        $data = $request->validate([
            'ma_sinh_vien' => ['required', 'integer'],
            'trang_thai_diem_danh' => ['required', 'in:co_mat,di_muon,vang,xin_phep,vang_co_phep,chua_diem_danh'],
        ]);

        if ($data['trang_thai_diem_danh'] === 'chua_diem_danh') {
            ChiTietDiemDanh::where('ma_phien_diem_danh', $phien->id)
                ->where('ma_sinh_vien', $data['ma_sinh_vien'])
                ->delete();

            return response()->json(['message' => 'Đã xóa bản ghi điểm danh.']);
        }

        ChiTietDiemDanh::updateOrCreate(
            [
                'ma_phien_diem_danh' => $phien->id,
                'ma_sinh_vien' => $data['ma_sinh_vien'],
            ],
            [
                'trang_thai_diem_danh' => $data['trang_thai_diem_danh'],
                'thoi_gian_diem_danh' => now(),
                'hinh_thuc_diem_danh' => 'sua_thu_cong',
            ]
        );

        return response()->json(['message' => 'Đã cập nhật trạng thái điểm danh.']);
    }

    /** Bước 6 - Đóng phiên: đánh dấu vắng những ai chưa điểm danh và broadcast. */
    public function dong(Request $request, PhienDiemDanh $phien): JsonResponse
    {
        if ($phien->trang_thai === 'da_dong') {
            return response()->json(['message' => 'Phiên đã đóng trước đó.']);
        }

        $phien->load('lichHoc.lopHoc');
        $lopHoc = $phien->lichHoc->lopHoc;

        $danhSachLop = DangKyLopHoc::where('ma_lop_hoc', $lopHoc->id)
            ->where('trang_thai', 'da_duyet')
            ->pluck('ma_sinh_vien');

        $daDiemDanh = ChiTietDiemDanh::where('ma_phien_diem_danh', $phien->id)
            ->pluck('ma_sinh_vien');

        $vang = $danhSachLop->diff($daDiemDanh);
        $vangCoPhep = DonXinPhep::where('ma_lich_hoc', $phien->ma_lich_hoc)
            ->where('trang_thai', 'duoc_duyet')
            ->whereIn('ma_sinh_vien', $vang)
            ->pluck('ma_sinh_vien')
            ->flip();
        foreach ($vang as $maSinhVien) {
            ChiTietDiemDanh::create([
                'ma_phien_diem_danh' => $phien->id,
                'ma_sinh_vien' => $maSinhVien,
                'trang_thai_diem_danh' => 'vang',
                'thoi_gian_diem_danh' => null,
                'hinh_thuc_diem_danh' => 'sua_thu_cong',
            ]);

            if ($vangCoPhep->has($maSinhVien)) {
                ChiTietDiemDanh::where('ma_phien_diem_danh', $phien->id)
                    ->where('ma_sinh_vien', $maSinhVien)
                    ->update(['trang_thai_diem_danh' => 'vang_co_phep']);
            }
        }

        $phien->update(['trang_thai' => 'da_dong']);

        $phong = PhongHocTrucTuyen::where('ma_lich_hoc', $phien->ma_lich_hoc)
            ->where('trang_thai', 'dang_dien_ra')
            ->first();

        if ($phong) {
            broadcast(new PhienDiemDanhDong($phong->ma_phong, $phien->ma_phien));
        }

        return response()->json([
            'message' => 'Đã đóng phiên điểm danh.',
            'so_vang' => $vang->count(),
        ]);
    }

    private function taoQrToken(PhienDiemDanh $phien): MaQrToken
    {
        MaQrToken::where('ma_phien', $phien->id)
            ->where('het_han_luc', '<', now())
            ->delete();

        return MaQrToken::create([
            'ma_phien' => $phien->id,
            'token' => Str::random(64),
            'het_han_luc' => now()->addSeconds(10),
        ]);
    }

    private function duongDanQr(string $token): string
    {
        return rtrim(config('app.fe_url', env('FE_URL', 'http://localhost:5173')), '/').'/diem-danh/'.$token;
    }
}
