<?php

namespace Tests\Feature;

use App\Exports\BaoCaoDiemDanhExport;
use App\Models\ChiTietDiemDanh;
use App\Models\DangKyLopHoc;
use App\Models\GiangVien;
use App\Models\LichHoc;
use App\Models\LopHoc;
use App\Models\MonHoc;
use App\Models\PhanCongGiangDay;
use App\Models\PhienDiemDanh;
use App\Models\SinhVien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BaoCaoDiemDanhTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private LopHoc $lopHoc;

    private SinhVien $sinhVien;

    private GiangVien $giangVien;

    private User $taiKhoanGiangVien;

    private LichHoc $lichHoc;

    protected function setUp(): void
    {
        parent::setUp();

        // Migration MySQL đã bổ sung `vang_co_phep`, còn enum mô phỏng của
        // SQLite vẫn giữ CHECK cũ. Chỉ nới CHECK trong cơ sở dữ liệu test.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA ignore_check_constraints = ON');
        }

        $this->admin = $this->taoTaiKhoan('admin', 'admin', 'Quản trị viên');

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

        $this->taiKhoanGiangVien = $this->taoTaiKhoan('giang_vien', 'gv', 'Giảng viên kiểm thử');
        $this->giangVien = GiangVien::create([
            'ma_giang_vien' => 'GV001',
            'ma_tai_khoan' => $this->taiKhoanGiangVien->id,
        ]);
        PhanCongGiangDay::create([
            'ma_giang_vien' => $this->giangVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'vai_tro_phu_trach' => 'giang_vien_chinh',
        ]);

        $taiKhoanSinhVien = $this->taoTaiKhoan('sinh_vien', 'sv', 'Sinh viên kiểm thử');
        $this->sinhVien = SinhVien::create([
            'ma_sinh_vien' => 'SV001',
            'ma_tai_khoan' => $taiKhoanSinhVien->id,
        ]);

        DangKyLopHoc::create([
            'ma_sinh_vien' => $this->sinhVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'ngay_dang_ky' => '2026-09-01',
            'trang_thai' => 'da_duyet',
        ]);

        $this->lichHoc = LichHoc::create([
            'ma_lop_hoc' => $this->lopHoc->id,
            'ngay_hoc' => '2026-09-14',
            'gio_bat_dau' => '08:00:00',
            'gio_ket_thuc' => '10:00:00',
            'phong_hoc' => 'A101',
            'trang_thai' => 'da_hoc',
        ]);
    }

    public function test_bao_cao_json_tinh_vang_co_phep_la_xin_phep(): void
    {
        $phienDaDong = $this->taoPhien('P-DD-01', 'da_dong', '2026-09-14 08:00:00');
        $phienDangMo = $this->taoPhien('P-MO-01', 'dang_mo', '2026-09-14 09:00:00');

        $this->taoChiTiet($phienDaDong, 'vang_co_phep');
        $this->taoChiTiet($phienDangMo, 'co_mat');

        Sanctum::actingAs($this->admin);

        $this->getJson("/api/admin/bao-cao/diem-danh/{$this->lopHoc->id}")
            ->assertOk()
            ->assertJsonPath('so_phien_diem_danh', 1)
            ->assertJsonPath('danh_sach.0.so_buoi', 1)
            ->assertJsonPath('danh_sach.0.so_co_mat', 0)
            ->assertJsonPath('danh_sach.0.so_vang', 0)
            ->assertJsonPath('danh_sach.0.so_xin_phep', 1)
            ->assertJsonPath('danh_sach.0.ty_le_chuyen_can', 0);
    }

    public function test_excel_chi_xuat_phien_da_dong_va_tach_vang_co_phep(): void
    {
        $phienDaDong = $this->taoPhien('P-DD-02', 'da_dong', '2026-09-14 08:00:00');
        $phienDangMo = $this->taoPhien('P-MO-02', 'dang_mo', '2026-09-14 09:00:00');

        $this->taoChiTiet($phienDaDong, 'vang_co_phep');
        $this->taoChiTiet($phienDangMo, 'co_mat');

        $export = new BaoCaoDiemDanhExport($this->lopHoc);

        $this->assertSame([
            'Mã SV',
            'Họ tên',
            '14/09 08:00',
            'Số buổi có mặt',
            'Số buổi vắng',
            'Số buổi xin phép',
            'Tỷ lệ chuyên cần',
        ], $export->headings());

        $this->assertSame([
            'SV001',
            'Sinh viên kiểm thử',
            'P',
            0,
            0,
            1,
            '0%',
        ], $export->collection()->first());
    }

    public function test_giang_vien_duoc_xuat_diem_danh_va_diem_cua_lop_duoc_phan_cong(): void
    {
        Sanctum::actingAs($this->taiKhoanGiangVien);

        $this->get("/api/giang-vien/bao-cao/diem-danh/{$this->lopHoc->id}/xuat")
            ->assertOk()
            ->assertHeader('content-disposition');
        $this->get("/api/giang-vien/bao-cao/diem/{$this->lopHoc->id}/xuat")
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_giang_vien_khong_duoc_xuat_lop_chua_duoc_phan_cong(): void
    {
        PhanCongGiangDay::query()->delete();
        Sanctum::actingAs($this->taiKhoanGiangVien);

        $this->getJson("/api/giang-vien/bao-cao/diem/{$this->lopHoc->id}/xuat")
            ->assertForbidden()
            ->assertJsonPath('message', 'Bạn không được phân công phụ trách lớp học này.');
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

    private function taoPhien(string $maPhien, string $trangThai, string $batDau): PhienDiemDanh
    {
        return PhienDiemDanh::create([
            'ma_phien' => $maPhien,
            'ma_lich_hoc' => $this->lichHoc->id,
            'ma_giang_vien' => $this->giangVien->id,
            'thoi_gian_bat_dau' => $batDau,
            'thoi_gian_ket_thuc' => '2026-09-14 10:00:00',
            'hinh_thuc_diem_danh' => 'qr_code',
            'trang_thai' => $trangThai,
        ]);
    }

    private function taoChiTiet(PhienDiemDanh $phien, string $trangThai): ChiTietDiemDanh
    {
        return ChiTietDiemDanh::create([
            'ma_phien_diem_danh' => $phien->id,
            'ma_sinh_vien' => $this->sinhVien->id,
            'trang_thai_diem_danh' => $trangThai,
            'thoi_gian_diem_danh' => now(),
            'hinh_thuc_diem_danh' => 'sua_thu_cong',
        ]);
    }
}
