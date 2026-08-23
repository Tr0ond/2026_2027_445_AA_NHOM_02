<?php

namespace App\Services;

use App\Events\ThongBaoMoi;
use App\Models\ThongBao;

class ThongBaoService
{
    public function tao(
        int $maTaiKhoan,
        string $loai,
        string $tieuDe,
        string $noiDung,
        array $duLieu = [],
    ): ThongBao {
        $thongBao = ThongBao::create([
            'ma_tai_khoan' => $maTaiKhoan,
            'loai' => $loai,
            'tieu_de' => $tieuDe,
            'noi_dung' => $noiDung,
            'du_lieu' => $duLieu ?: null,
        ]);

        broadcast(new ThongBaoMoi($maTaiKhoan, $this->duLieu($thongBao)));

        return $thongBao;
    }

    public function duLieu(ThongBao $thongBao): array
    {
        return [
            'id' => $thongBao->id,
            'loai' => $thongBao->loai,
            'tieu_de' => $thongBao->tieu_de,
            'noi_dung' => $thongBao->noi_dung,
            'da_doc' => (bool) $thongBao->da_doc,
            'du_lieu' => $thongBao->du_lieu,
            'created_at' => $thongBao->created_at?->toIso8601String(),
        ];
    }
}
