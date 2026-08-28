<?php

namespace Tests\Feature;

use App\Models\DangKyLopHoc;
use App\Models\DonXinPhep;
use App\Models\GiangVien;
use App\Models\LichHoc;
use App\Models\LopHoc;
use App\Models\MonHoc;
use App\Models\PhanCongGiangDay;
use App\Models\SinhVien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DonXinPhepTest extends TestCase
{
    use RefreshDatabase;

    private LopHoc $lopHoc;

    private LichHoc $lichHoc;

    private User $taiKhoanSinhVien;

    private SinhVien $sinhVien;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->lichHoc = LichHoc::create([
            'ma_lop_hoc' => $this->lopHoc->id,
            'ngay_hoc' => now()->addDay()->toDateString(),
            'gio_bat_dau' => '08:00:00',
            'gio_ket_thuc' => '10:00:00',
            'phong_hoc' => 'A101',
            'trang_thai' => 'ke_hoach',
        ]);

        $this->taiKhoanSinhVien = $this->taoTaiKhoan('sinh_vien', 'sv');
        $this->sinhVien = SinhVien::create([
            'ma_sinh_vien' => 'SV001',
            'ma_tai_khoan' => $this->taiKhoanSinhVien->id,
        ]);

        DangKyLopHoc::create([
            'ma_sinh_vien' => $this->sinhVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'ngay_dang_ky' => now()->toDateString(),
            'trang_thai' => 'da_duyet',
        ]);
    }

    public function test_sinh_vien_thuoc_lop_gui_duoc_don_xin_phep(): void
    {
        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->postJson('/api/xin-phep', $this->duLieuDon())
            ->assertCreated()
            ->assertJsonPath('message', 'Đã gửi yêu cầu xin phép vắng.');

        $this->assertDatabaseHas('don_xin_phep', [
            'ma_sinh_vien' => $this->sinhVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'ma_lich_hoc' => $this->lichHoc->id,
            'trang_thai' => 'cho_duyet',
        ]);
    }

    public function test_khong_the_gui_trung_don_cho_cung_buoi_hoc(): void
    {
        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->postJson('/api/xin-phep', $this->duLieuDon())->assertCreated();
        $this->postJson('/api/xin-phep', $this->duLieuDon())
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Bạn đã gửi đơn xin phép cho buổi học này.');
    }

    public function test_giang_vien_phu_trach_duoc_duyet_don(): void
    {
        [$taiKhoanGiangVien, $giangVien] = $this->taoGiangVien('gv-phu-trach');
        PhanCongGiangDay::create([
            'ma_giang_vien' => $giangVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'vai_tro_phu_trach' => 'giang_vien_chinh',
        ]);

        $don = $this->taoDon();
        Sanctum::actingAs($taiKhoanGiangVien);

        $this->postJson("/api/xin-phep/{$don->id}/duyet", [
            'trang_thai' => 'duoc_duyet',
        ])->assertOk();

        $this->assertDatabaseHas('don_xin_phep', [
            'id' => $don->id,
            'trang_thai' => 'duoc_duyet',
            'nguoi_duyet' => $taiKhoanGiangVien->id,
        ]);
    }

    public function test_giang_vien_khong_phu_trach_khong_duoc_duyet_don(): void
    {
        [$taiKhoanGiangVien] = $this->taoGiangVien('gv-khac');
        $don = $this->taoDon();
        Sanctum::actingAs($taiKhoanGiangVien);

        $this->postJson("/api/xin-phep/{$don->id}/duyet", [
            'trang_thai' => 'duoc_duyet',
        ])->assertForbidden();

        $this->assertDatabaseHas('don_xin_phep', [
            'id' => $don->id,
            'trang_thai' => 'cho_duyet',
        ]);
    }

    private function duLieuDon(): array
    {
        return [
            'ma_lop_hoc' => $this->lopHoc->id,
            'ma_lich_hoc' => $this->lichHoc->id,
            'ngay_nghi' => $this->lichHoc->ngay_hoc->toDateString(),
            'ly_do' => 'Có việc gia đình.',
        ];
    }

    private function taoDon(): DonXinPhep
    {
        return DonXinPhep::create([
            'ma_sinh_vien' => $this->sinhVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'ma_lich_hoc' => $this->lichHoc->id,
            'ngay_nghi' => $this->lichHoc->ngay_hoc->toDateString(),
            'ly_do' => 'Có việc gia đình.',
            'trang_thai' => 'cho_duyet',
        ]);
    }

    private function taoTaiKhoan(string $vaiTro, string $ma): User
    {
        return User::create([
            'ho_ten' => "Tài khoản {$ma}",
            'email' => "{$ma}@portal.test",
            'mat_khau' => 'password',
            'vai_tro' => $vaiTro,
            'trang_thai' => 'hoat_dong',
        ]);
    }

    private function taoGiangVien(string $ma): array
    {
        $taiKhoan = $this->taoTaiKhoan('giang_vien', $ma);
        $giangVien = GiangVien::create([
            'ma_giang_vien' => strtoupper($ma),
            'ma_tai_khoan' => $taiKhoan->id,
        ]);

        return [$taiKhoan, $giangVien];
    }
}
