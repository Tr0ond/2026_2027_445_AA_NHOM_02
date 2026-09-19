<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_tao_dung_so_luong_va_quan_he_du_lieu(): void
    {
        $this->seed();

        $this->assertDatabaseCount('tai_khoan', 41);
        $this->assertDatabaseCount('giang_vien', 20);
        $this->assertDatabaseCount('sinh_vien', 20);
        $this->assertDatabaseCount('mon_hoc', 15);
        $this->assertDatabaseCount('lop_hoc', 75);
        $this->assertDatabaseCount('phan_cong_giang_day', 75);
        $this->assertDatabaseCount('dang_ky_lop_hoc', 300);
        $this->assertDatabaseCount('lich_hoc', 450);
        $this->assertDatabaseCount('diem_thanh_phan', 225);

        $soLopTheoMon = DB::table('lop_hoc')
            ->selectRaw('ma_mon_hoc, COUNT(*) AS so_lop')
            ->groupBy('ma_mon_hoc')
            ->pluck('so_lop');

        $this->assertCount(15, $soLopTheoMon);
        $this->assertTrue($soLopTheoMon->every(fn ($soLop) => (int) $soLop === 5));

        $this->assertSame(
            75,
            DB::table('phan_cong_giang_day')->distinct()->count('ma_lop_hoc')
        );
        $this->assertSame(
            20,
            DB::table('phan_cong_giang_day')->distinct()->count('ma_giang_vien')
        );

        $taiGiangDay = DB::table('phan_cong_giang_day')
            ->selectRaw('ma_giang_vien, COUNT(*) AS so_lop')
            ->groupBy('ma_giang_vien')
            ->pluck('so_lop');
        $this->assertCount(20, $taiGiangDay);
        $this->assertTrue($taiGiangDay->every(fn ($soLop) => in_array((int) $soLop, [3, 4], true)));

        $siSoTheoLop = DB::table('dang_ky_lop_hoc')
            ->selectRaw('ma_lop_hoc, COUNT(*) AS so_sinh_vien')
            ->groupBy('ma_lop_hoc')
            ->pluck('so_sinh_vien');
        $this->assertCount(75, $siSoTheoLop);
        $this->assertTrue($siSoTheoLop->every(fn ($siSo) => (int) $siSo === 4));

        $lichTrung = DB::table('lich_hoc as lh')
            ->join('phan_cong_giang_day as pc', 'pc.ma_lop_hoc', '=', 'lh.ma_lop_hoc')
            ->selectRaw('pc.ma_giang_vien, lh.ngay_hoc, lh.gio_bat_dau, lh.gio_ket_thuc, COUNT(*) AS so_luong')
            ->groupBy('pc.ma_giang_vien', 'lh.ngay_hoc', 'lh.gio_bat_dau', 'lh.gio_ket_thuc')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        $this->assertCount(0, $lichTrung);

        $this->assertDatabaseHas('tai_khoan', [
            'email' => 'gv20@portal.test',
            'vai_tro' => 'giang_vien',
        ]);
        $this->assertDatabaseHas('tai_khoan', [
            'email' => 'sv20@portal.test',
            'vai_tro' => 'sinh_vien',
        ]);
    }
}
