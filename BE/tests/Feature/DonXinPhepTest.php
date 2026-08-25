<?php

namespace Tests\Feature;

use App\Models\DonXinPhep;
use App\Models\SinhVien;
use App\Models\GiangVien;
use App\Models\User;
use App\Models\LopHoc;
use App\Models\LichHoc;
use App\Models\PhanCongGiangDay;
use App\Models\DangKyLopHoc;
use App\Models\MonHoc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonXinPhepTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    /** @test */
    public function sinh_vien_co_the_nop_don_xin_phep()
    {
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        DangKyLopHoc::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'da_dang_ky',
        ]);
        
        $lichHoc = LichHoc::factory()->create([
            'ma_lop_hoc' => $lopHoc->id,
            'ngay_hoc' => now()->addDays(2)->toDateString(),
        ]);

        $this->actingAs($userSV);

        $response = $this->postJson('/api/sinh-vien/don-xin-phep', [
            'ma_lop_hoc' => $lopHoc->id,
            'ma_lich_hoc' => $lichHoc->id,
            'ngay_nghi' => now()->addDays(2)->toDateString(),
            'ly_do' => 'Bị ốm',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('don_xin_phep', [
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'ma_lich_hoc' => $lichHoc->id,
            'ngay_nghi' => now()->addDays(2)->toDateString(),
            'ly_do' => 'Bị ốm',
            'trang_thai' => 'cho_duyet',
        ]);
    }

    /** @test */
    public function sinh_vien_khong_the_nop_don_neu_chua_dang_ky_lop()
    {
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        // Không đăng ký lớp học
        
        $lichHoc = LichHoc::factory()->create([
            'ma_lop_hoc' => $lopHoc->id,
            'ngay_hoc' => now()->addDays(2)->toDateString(),
        ]);

        $this->actingAs($userSV);

        $response = $this->postJson('/api/sinh-vien/don-xin-phep', [
            'ma_lop_hoc' => $lopHoc->id,
            'ma_lich_hoc' => $lichHoc->id,
            'ngay_nghi' => now()->addDays(2)->toDateString(),
            'ly_do' => 'Bị ốm',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function sinh_vien_khong_the_nop_don_trung_lap()
    {
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        DangKyLopHoc::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'da_dang_ky',
        ]);
        
        $lichHoc = LichHoc::factory()->create([
            'ma_lop_hoc' => $lopHoc->id,
            'ngay_hoc' => now()->addDays(2)->toDateString(),
        ]);
        
        DonXinPhep::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'ma_lich_hoc' => $lichHoc->id,
            'ngay_nghi' => now()->addDays(2)->toDateString(),
            'trang_thai' => 'cho_duyet',
        ]);

        $this->actingAs($userSV);

        $response = $this->postJson('/api/sinh-vien/don-xin-phep', [
            'ma_lop_hoc' => $lopHoc->id,
            'ma_lich_hoc' => $lichHoc->id,
            'ngay_nghi' => now()->addDays(2)->toDateString(),
            'ly_do' => 'Bị ốm',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function sinh_vien_co_the_xem_danh_sach_don_cua_minh()
    {
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        DonXinPhep::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'cho_duyet',
        ]);

        $this->actingAs($userSV);

        $response = $this->getJson('/api/sinh-vien/don-xin-phep');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    /** @test */
    public function sinh_vien_co_the_huy_don_cho_duyet()
    {
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        $don = DonXinPhep::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'cho_duyet',
        ]);

        $this->actingAs($userSV);

        $response = $this->deleteJson("/api/sinh-vien/don-xin-phep/{$don->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('don_xin_phep', ['id' => $don->id]);
    }

    /** @test */
    public function sinh_vien_khong_the_huy_don_da_duyet()
    {
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        $don = DonXinPhep::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'da_duyet',
        ]);

        $this->actingAs($userSV);

        $response = $this->deleteJson("/api/sinh-vien/don-xin-phep/{$don->id}");

        $response->assertStatus(400);
    }

    /** @test */
    public function giang_vien_co_the_duyet_don_xin_phep()
    {
        $userGV = User::factory()->create(['vai_tro' => 'giang_vien']);
        $giangVien = GiangVien::factory()->create(['ma_tai_khoan' => $userGV->id]);
        
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        PhanCongGiangDay::factory()->create([
            'ma_giang_vien' => $giangVien->id,
            'ma_lop_hoc' => $lopHoc->id,
        ]);
        
        $don = DonXinPhep::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'cho_duyet',
        ]);

        $this->actingAs($userGV);

        $response = $this->postJson("/api/don-xin-phep/{$don->id}/duyet");

        $response->assertStatus(200);
        $this->assertDatabaseHas('don_xin_phep', [
            'id' => $don->id,
            'trang_thai' => 'da_duyet',
            'nguoi_duyet' => $userGV->id,
        ]);
    }

    /** @test */
    public function giang_vien_co_the_tu_choi_don_xin_phep()
    {
        $userGV = User::factory()->create(['vai_tro' => 'giang_vien']);
        $giangVien = GiangVien::factory()->create(['ma_tai_khoan' => $userGV->id]);
        
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        PhanCongGiangDay::factory()->create([
            'ma_giang_vien' => $giangVien->id,
            'ma_lop_hoc' => $lopHoc->id,
        ]);
        
        $don = DonXinPhep::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'cho_duyet',
        ]);

        $this->actingAs($userGV);

        $response = $this->postJson("/api/don-xin-phep/{$don->id}/tu-choi", [
            'ly_do_tu_choi' => 'Không có lý do hợp lệ',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('don_xin_phep', [
            'id' => $don->id,
            'trang_thai' => 'tu_choi',
            'nguoi_duyet' => $userGV->id,
        ]);
    }

    /** @test */
    public function giang_vien_khong_phu_trach_khong_the_duyet_don()
    {
        $userGV = User::factory()->create(['vai_tro' => 'giang_vien']);
        $giangVien = GiangVien::factory()->create(['ma_tai_khoan' => $userGV->id]);
        
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        // Không phân công giảng viên cho lớp học này
        
        $don = DonXinPhep::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'cho_duyet',
        ]);

        $this->actingAs($userGV);

        $response = $this->postJson("/api/don-xin-phep/{$don->id}/duyet");

        $response->assertStatus(403);
    }

    /** @test */
    public function giang_vien_co_the_xem_danh_sach_don_theo_lop()
    {
        $userGV = User::factory()->create(['vai_tro' => 'giang_vien']);
        $giangVien = GiangVien::factory()->create(['ma_tai_khoan' => $userGV->id]);
        
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        PhanCongGiangDay::factory()->create([
            'ma_giang_vien' => $giangVien->id,
            'ma_lop_hoc' => $lopHoc->id,
        ]);
        
        DonXinPhep::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'cho_duyet',
        ]);

        $this->actingAs($userGV);

        $response = $this->getJson('/api/don-xin-phep/lop-hoc?ma_lop_hoc=' . $lopHoc->id);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    /** @test */
    public function giang_vien_co_the_xu_ly_don_hang_loat()
    {
        $userGV = User::factory()->create(['vai_tro' => 'giang_vien']);
        $giangVien = GiangVien::factory()->create(['ma_tai_khoan' => $userGV->id]);
        
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        PhanCongGiangDay::factory()->create([
            'ma_giang_vien' => $giangVien->id,
            'ma_lop_hoc' => $lopHoc->id,
        ]);
        
        $don1 = DonXinPhep::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'cho_duyet',
        ]);
        
        $don2 = DonXinPhep::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'cho_duyet',
        ]);

        $this->actingAs($userGV);

        $response = $this->postJson('/api/don-xin-phep/xu-ly-hang-loat', [
            'ma_don' => [$don1->id, $don2->id],
            'hanh_dong' => 'duyet',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('don_xin_phep', [
            'id' => $don1->id,
            'trang_thai' => 'da_duyet',
        ]);
        $this->assertDatabaseHas('don_xin_phep', [
            'id' => $don2->id,
            'trang_thai' => 'da_duyet',
        ]);
    }
}
