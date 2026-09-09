<?php

namespace App\Http\Controllers\Api;

use App\Events\TinNhanMoi;
use App\Http\Controllers\Controller;
use App\Models\PhongHocTrucTuyen;
use App\Models\TinNhanPhong;
use App\Services\QuyenPhongService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TinNhanPhongController extends Controller
{
    public function __construct(private QuyenPhongService $quyenPhong) {}

    /** US07 - Lịch sử tin nhắn phòng. */
    public function index(Request $request, string $maPhong): JsonResponse
    {
        $phong = PhongHocTrucTuyen::where('ma_phong', $maPhong)->firstOrFail();
        $this->quyenPhong->thanhVienDangThamGia($request->user(), $phong);

        $tinNhans = TinNhanPhong::with('taiKhoan')
            ->where('ma_phong_hoc_truc_tuyen', $phong->id)
            ->orderBy('thoi_gian_gui')
            ->get()
            ->map(fn ($tn) => [
                'ma_tai_khoan' => $tn->ma_tai_khoan,
                'ho_ten' => $tn->taiKhoan?->ho_ten,
                'vai_tro' => $tn->taiKhoan?->vai_tro,
                'noi_dung' => $tn->noi_dung,
                'thoi_gian_gui' => $tn->thoi_gian_gui?->format('H:i'),
            ]);

        return response()->json(['danh_sach' => $tinNhans]);
    }

    /** US07 - Gửi tin nhắn trong phòng + broadcast realtime. */
    public function store(Request $request, string $maPhong): JsonResponse
    {
        $data = $request->validate([
            'noi_dung' => ['required', 'string', 'max:1000'],
        ], ['noi_dung.required' => 'Vui lòng nhập nội dung.']);

        $phong = PhongHocTrucTuyen::where('ma_phong', $maPhong)->firstOrFail();

        $this->quyenPhong->thanhVienDangThamGia($request->user(), $phong);

        $user = $request->user();
        $thoiGian = now();

        TinNhanPhong::create([
            'ma_phong_hoc_truc_tuyen' => $phong->id,
            'ma_tai_khoan' => $user->id,
            'noi_dung' => $data['noi_dung'],
            'thoi_gian_gui' => $thoiGian,
        ]);

        broadcast(new TinNhanMoi(
            $phong->ma_phong,
            $user->id,
            $user->ho_ten,
            $user->laGiangVien() ? 'giang_vien' : 'sinh_vien',
            $data['noi_dung'],
            $thoiGian->format('H:i'),
        ))->toOthers();

        return response()->json([
            'message' => 'Đã gửi.',
            'tin_nhan' => [
                'ma_tai_khoan' => $user->id,
                'ho_ten' => $user->ho_ten,
                'vai_tro' => $user->laGiangVien() ? 'giang_vien' : 'sinh_vien',
                'noi_dung' => $data['noi_dung'],
                'thoi_gian_gui' => $thoiGian->format('H:i'),
            ],
        ], 201);
    }
}
