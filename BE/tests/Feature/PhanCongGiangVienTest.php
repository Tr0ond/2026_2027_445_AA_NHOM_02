<?php

namespace Tests\Feature;

use App\Models\GiangVien;
use App\Models\LopHoc;
use App\Models\MonHoc;
use App\Models\PhanCongGiangDay;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhanCongGiangVienTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private GiangVien $giangVienMot;

    private GiangVien $giangVienHai;

    private LopHoc $lopHoc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->taoTaiKhoan('admin', 'admin', 'Quản trị viên');
        $this->giangVienMot = $this->taoGiangVien('GV001', 'gv1');
        $this->giangVienHai = $this->taoGiangVien('GV002', 'gv2');

        $monHoc = MonHoc::create([
            'ma_mon_hoc' => 'CS445',
            'ten_mon' => 'Phát triển phần mềm',
            'so_tin_chi' => 3,
        ]);

        $this->lopHoc = LopHoc::create([
            'ma_lop_hoc' => 'CS445-01',
            'ten_lop' => 'Nhóm 01',
            'ma_mon_hoc' => $monHoc->id,
            'hoc_ky' => '1',
            'nam_hoc' => '2026-2027',
            'so_luong_toi_da' => 40,
            'trang_thai' => 'dang_hoc',
        ]);

        Sanctum::actingAs($this->admin);
    }

    public function test_mot_lop_chi_duoc_phan_cong_cho_mot_giang_vien(): void
    {
        $this->postJson('/api/admin/phan-cong', [
            'ma_giang_vien' => $this->giangVienMot->id,
            'ma_lop_hoc' => $this->lopHoc->id,
        ])->assertCreated();

        $this->postJson('/api/admin/phan-cong', [
            'ma_giang_vien' => $this->giangVienHai->id,
            'ma_lop_hoc' => $this->lopHoc->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('ma_lop_hoc')
            ->assertJsonPath('errors.ma_lop_hoc.0', 'Lớp học này đã được phân công cho một giảng viên.');

        $this->assertDatabaseCount('phan_cong_giang_day', 1);
        $this->assertDatabaseHas('phan_cong_giang_day', [
            'ma_giang_vien' => $this->giangVienMot->id,
            'ma_lop_hoc' => $this->lopHoc->id,
        ]);
    }

    public function test_co_so_du_lieu_tu_choi_phan_cong_trung_lop(): void
    {
        PhanCongGiangDay::create([
            'ma_giang_vien' => $this->giangVienMot->id,
            'ma_lop_hoc' => $this->lopHoc->id,
        ]);

        $this->expectException(QueryException::class);

        PhanCongGiangDay::create([
            'ma_giang_vien' => $this->giangVienHai->id,
            'ma_lop_hoc' => $this->lopHoc->id,
        ]);
    }

    private function taoTaiKhoan(string $vaiTro, string $ma, string $hoTen): User
    {
        return User::create([
            'ho_ten' => $hoTen,
            'email' => "{$ma}@portal.test",
            'mat_khau' => 'password',
            'vai_tro' => $vaiTro,
            'trang_thai' => 'hoat_dong',
        ]);
    }

    private function taoGiangVien(string $maGiangVien, string $maTaiKhoan): GiangVien
    {
        $taiKhoan = $this->taoTaiKhoan('giang_vien', $maTaiKhoan, "Giảng viên {$maGiangVien}");

        return GiangVien::create([
            'ma_giang_vien' => $maGiangVien,
            'ma_tai_khoan' => $taiKhoan->id,
        ]);
    }
}
