<?php

namespace Tests\Feature;

use App\Models\DangKyLopHoc;
use App\Models\DiemSinhVien;
use App\Models\DiemThanhPhan;
use App\Models\GiangVien;
use App\Models\LopHoc;
use App\Models\MonHoc;
use App\Models\PhanCongGiangDay;
use App\Models\SinhVien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiemHocTapTest extends TestCase
{
    use RefreshDatabase;

    private LopHoc $lopHoc;

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

    public function test_sinh_vien_xem_duoc_diem_va_ket_qua_tong_ket(): void
    {
        $quaTrinh = DiemThanhPhan::create([
            'ma_lop_hoc' => $this->lopHoc->id,
            'ten_thanh_phan' => 'Quá trình',
            'trong_so' => 4,
        ]);
        $cuoiKy = DiemThanhPhan::create([
            'ma_lop_hoc' => $this->lopHoc->id,
            'ten_thanh_phan' => 'Cuối kỳ',
            'trong_so' => 6,
        ]);

        DiemSinhVien::create([
            'ma_sinh_vien' => $this->sinhVien->id,
            'ma_thanh_phan' => $quaTrinh->id,
            'diem' => 8,
        ]);
        DiemSinhVien::create([
            'ma_sinh_vien' => $this->sinhVien->id,
            'ma_thanh_phan' => $cuoiKy->id,
            'diem' => 9,
        ]);

        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->getJson('/api/sinh-vien/diem')
            ->assertOk()
            ->assertJsonCount(1, 'danh_sach')
            ->assertJsonPath('danh_sach.0.xep_loai', 'A')
            ->assertJsonPath('danh_sach.0.trang_thai_ket_qua', 'dat');

        $this->assertDatabaseHas('ket_qua_hoc_phan', [
            'ma_sinh_vien' => $this->sinhVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'diem_tong_ket' => 8.6,
            'xep_loai' => 'A',
            'trang_thai' => 'dat',
        ]);
    }

    public function test_giang_vien_phu_trach_duoc_luu_diem(): void
    {
        [$taiKhoanGiangVien, $giangVien] = $this->taoGiangVien('gv-phu-trach');
        PhanCongGiangDay::create([
            'ma_giang_vien' => $giangVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'vai_tro_phu_trach' => 'giang_vien_chinh',
        ]);

        $thanhPhan = DiemThanhPhan::create([
            'ma_lop_hoc' => $this->lopHoc->id,
            'ten_thanh_phan' => 'Giữa kỳ',
            'trong_so' => 4,
        ]);

        Sanctum::actingAs($taiKhoanGiangVien);

        $this->postJson('/api/luu-diem', [
            'ma_sinh_vien' => $this->sinhVien->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 7.5,
        ])->assertOk()->assertJsonPath('message', 'Đã lưu điểm.');

        $this->assertDatabaseHas('diem_sinh_vien', [
            'ma_sinh_vien' => $this->sinhVien->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 7.5,
        ]);
    }

    public function test_giang_vien_khong_phu_trach_khong_duoc_xem_hoac_luu_diem(): void
    {
        [$taiKhoanGiangVien] = $this->taoGiangVien('gv-khac');
        $thanhPhan = DiemThanhPhan::create([
            'ma_lop_hoc' => $this->lopHoc->id,
            'ten_thanh_phan' => 'Giữa kỳ',
            'trong_so' => 4,
        ]);

        Sanctum::actingAs($taiKhoanGiangVien);

        $this->getJson("/api/lop-hoc/{$this->lopHoc->id}/diem")
            ->assertForbidden();

        $this->postJson('/api/luu-diem', [
            'ma_sinh_vien' => $this->sinhVien->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 7.5,
        ])->assertForbidden();
    }

    public function test_admin_them_duoc_thanh_phan_diem_cho_lop(): void
    {
        Sanctum::actingAs($this->taoTaiKhoan('admin', 'admin'));

        $this->postJson("/api/admin/lop-hoc/{$this->lopHoc->id}/thanh-phan", [
            'ten_thanh_phan' => 'Chuyên cần',
            'trong_so' => 1,
        ])->assertCreated();

        $this->assertDatabaseHas('diem_thanh_phan', [
            'ma_lop_hoc' => $this->lopHoc->id,
            'ten_thanh_phan' => 'Chuyên cần',
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
