<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThongBao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThongBaoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ThongBao::where('ma_tai_khoan', $request->user()->id)
            ->orderByDesc('created_at');

        $danhSach = $query->limit(50)->get()->map(fn (ThongBao $thongBao) => [
            'id' => $thongBao->id,
            'loai' => $thongBao->loai,
            'tieu_de' => $thongBao->tieu_de,
            'noi_dung' => $thongBao->noi_dung,
            'da_doc' => (bool) $thongBao->da_doc,
            'du_lieu' => $thongBao->du_lieu,
            'created_at' => $thongBao->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'danh_sach' => $danhSach,
            'chua_doc' => ThongBao::where('ma_tai_khoan', $request->user()->id)
                ->where('da_doc', false)
                ->count(),
        ]);
    }

    public function danhDauDaDoc(Request $request, ThongBao $thongBao): JsonResponse
    {
        if ($thongBao->ma_tai_khoan !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền với thông báo này.'], 403);
        }

        $thongBao->update([
            'da_doc' => true,
            'thoi_gian_doc' => now(),
        ]);

        return response()->json(['message' => 'Đã đánh dấu thông báo đã đọc.']);
    }

    public function danhDauTatCaDaDoc(Request $request): JsonResponse
    {
        ThongBao::where('ma_tai_khoan', $request->user()->id)
            ->where('da_doc', false)
            ->update([
                'da_doc' => true,
                'thoi_gian_doc' => now(),
            ]);

        return response()->json(['message' => 'Đã đánh dấu tất cả thông báo đã đọc.']);
    }
}
