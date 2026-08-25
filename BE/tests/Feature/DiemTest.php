<?php

namespace Tests\Feature;

use App\Models\DiemSinhVien;
use App\Models\DiemThanhPhan;
use App\Models\SinhVien;
use App\Models\GiangVien;
use App\Models\User;
use App\Models\LopHoc;
use App\Models\PhanCongGiangDay;
use App\Models\DangKyLopHoc;
use App\Models\MonHoc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Tạo middleware bypass cho test
        $this->withoutMiddleware();
    }

    /** @test */
    public function giang_vien_co_the_nhap_diem_thanh_phan()
    {
        // Tạo dữ liệu test
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
        
        DangKyLopHoc::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'da_dang_ky',
        ]);
        
        $thanhPhan = DiemThanhPhan::factory()->create([
            'ma_lop_hoc' => $lopHoc->id,
            'ten_thanh_phan' => 'Giữa kỳ',
            'trong_so' => 0.3,
        ]);

        $this->actingAs($userGV);

        $response = $this->postJson('/api/diem', [
            'ma_sinh_vien' => $sinhVien->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 8.5,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('diem_sinh_vien', [
            'ma_sinh_vien' => $sinhVien->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 8.5,
        ]);
    }

    /** @test */
    public function giang_vien_khong_phu_trach_lop_hoc_khong_the_nhap_diem()
    {
        $userGV = User::factory()->create(['vai_tro' => 'giang_vien']);
        $giangVien = GiangVien::factory()->create(['ma_tai_khoan' => $userGV->id]);
        
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        // Không phân công giảng viên cho lớp học này
        
        DangKyLopHoc::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'da_dang_ky',
        ]);
        
        $thanhPhan = DiemThanhPhan::factory()->create([
            'ma_lop_hoc' => $lopHoc->id,
        ]);

        $this->actingAs($userGV);

        $response = $this->postJson('/api/diem', [
            'ma_sinh_vien' => $sinhVien->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 8.5,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function sinh_vien_co_the_xem_diem_cua_minh()
    {
        $userSV = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien = SinhVien::factory()->create(['ma_tai_khoan' => $userSV->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        $thanhPhan = DiemThanhPhan::factory()->create([
            'ma_lop_hoc' => $lopHoc->id,
        ]);

        DiemSinhVien::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 9.0,
        ]);

        $this->actingAs($userSV);

        $response = $this->getJson('/api/sinh-vien/diem');

        $response->assertStatus(200);
        $response->assertJsonFragment(['diem' => 9.0]);
    }

    /** @test */
    public function sinh_vien_co_the_xem_diem_theo_lop_hoc()
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
        
        $thanhPhan = DiemThanhPhan::factory()->create([
            'ma_lop_hoc' => $lopHoc->id,
        ]);

        DiemSinhVien::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 8.5,
        ]);

        $this->actingAs($userSV);

        $response = $this->getJson("/api/sinh-vien/diem/lop-hoc/{$lopHoc->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'diem_thanh_phan',
                'diem_tong_ket',
                'xep_loai'
            ]
        ]);
    }

    /** @test */
    public function giang_vien_co_the_nhap_diem_hang_loat()
    {
        $userGV = User::factory()->create(['vai_tro' => 'giang_vien']);
        $giangVien = GiangVien::factory()->create(['ma_tai_khoan' => $userGV->id]);
        
        $userSV1 = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien1 = SinhVien::factory()->create(['ma_tai_khoan' => $userSV1->id]);
        
        $userSV2 = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $sinhVien2 = SinhVien::factory()->create(['ma_tai_khoan' => $userSV2->id]);
        
        $monHoc = MonHoc::factory()->create();
        $lopHoc = LopHoc::factory()->create(['ma_mon_hoc' => $monHoc->id]);
        
        PhanCongGiangDay::factory()->create([
            'ma_giang_vien' => $giangVien->id,
            'ma_lop_hoc' => $lopHoc->id,
        ]);
        
        DangKyLopHoc::factory()->create([
            'ma_sinh_vien' => $sinhVien1->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'da_dang_ky',
        ]);
        
        DangKyLopHoc::factory()->create([
            'ma_sinh_vien' => $sinhVien2->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'da_dang_ky',
        ]);
        
        $thanhPhan = DiemThanhPhan::factory()->create([
            'ma_lop_hoc' => $lopHoc->id,
        ]);

        $this->actingAs($userGV);

        $response = $this->postJson('/api/diem/bulk', [
            'ma_thanh_phan' => $thanhPhan->id,
            'diems' => [
                ['ma_sinh_vien' => $sinhVien1->id, 'diem' => 8.0],
                ['ma_sinh_vien' => $sinhVien2->id, 'diem' => 9.0],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('diem_sinh_vien', [
            'ma_sinh_vien' => $sinhVien1->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 8.0,
        ]);
        $this->assertDatabaseHas('diem_sinh_vien', [
            'ma_sinh_vien' => $sinhVien2->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 9.0,
        ]);
    }

    /** @test */
    public function giang_vien_co_the_xem_diem_cua_lop_hoc()
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
        
        DangKyLopHoc::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'da_dang_ky',
        ]);
        
        $thanhPhan = DiemThanhPhan::factory()->create([
            'ma_lop_hoc' => $lopHoc->id,
        ]);

        DiemSinhVien::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 7.5,
        ]);

        $this->actingAs($userGV);

        $response = $this->getJson("/api/diem/lop-hoc/{$lopHoc->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'thanh_phans',
                'sinh_viens'
            ]
        ]);
    }

    /** @test */
    public function diem_phai_trong_khoang_0_den_10()
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
        
        DangKyLopHoc::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $lopHoc->id,
            'trang_thai' => 'da_dang_ky',
        ]);
        
        $thanhPhan = DiemThanhPhan::factory()->create([
            'ma_lop_hoc' => $lopHoc->id,
        ]);

        $this->actingAs($userGV);

        // Test điểm > 10
        $response = $this->postJson('/api/diem', [
            'ma_sinh_vien' => $sinhVien->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => 11.0,
        ]);
        $response->assertStatus(422);

        // Test điểm < 0
        $response = $this->postJson('/api/diem', [
            'ma_sinh_vien' => $sinhVien->id,
            'ma_thanh_phan' => $thanhPhan->id,
            'diem' => -1.0,
        ]);
        $response->assertStatus(422);
    }
}
