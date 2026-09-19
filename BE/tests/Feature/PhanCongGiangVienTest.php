<?php

namespace Tests\Feature;

use App\Models\GiangVien;
use App\Models\LichHoc;
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

    public function test_khong_phan_cong_hai_lop_trung_lich_cho_cung_giang_vien(): void
    {
        $ngayHoc = now()->addDay()->toDateString();
        $lopTrung = $this->taoLop('CS445-02');
        $this->taoLich($this->lopHoc, $ngayHoc, '09:00:00', '11:00:00');
        $this->taoLich($lopTrung, $ngayHoc, '10:30:00', '12:30:00');

        $this->postJson('/api/admin/phan-cong', [
            'ma_giang_vien' => $this->giangVienMot->id,
            'ma_lop_hoc' => $this->lopHoc->id,
        ])->assertCreated();

        $this->postJson('/api/admin/phan-cong', [
            'ma_giang_vien' => $this->giangVienMot->id,
            'ma_lop_hoc' => $lopTrung->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('ma_lop_hoc')
            ->assertJsonPath('errors.ma_lop_hoc.0', fn ($message) => str_contains($message, 'trùng với lớp CS445-01'));

        $this->postJson('/api/admin/phan-cong', [
            'ma_giang_vien' => $this->giangVienHai->id,
            'ma_lop_hoc' => $lopTrung->id,
        ])->assertCreated();

        $this->assertDatabaseCount('phan_cong_giang_day', 2);
    }

    public function test_cho_phep_hai_lop_lien_tiep_nhung_khong_giao_nhau(): void
    {
        $ngayHoc = now()->addDay()->toDateString();
        $lopKeTiep = $this->taoLop('CS445-03');
        $this->taoLich($this->lopHoc, $ngayHoc, '09:00:00', '11:00:00');
        $this->taoLich($lopKeTiep, $ngayHoc, '11:00:00', '13:00:00');

        foreach ([$this->lopHoc, $lopKeTiep] as $lop) {
            $this->postJson('/api/admin/phan-cong', [
                'ma_giang_vien' => $this->giangVienMot->id,
                'ma_lop_hoc' => $lop->id,
            ])->assertCreated();
        }

        $this->assertDatabaseCount('phan_cong_giang_day', 2);
    }

    public function test_admin_co_the_huy_phan_cong(): void
    {
        PhanCongGiangDay::create([
            'ma_giang_vien' => $this->giangVienMot->id,
            'ma_lop_hoc' => $this->lopHoc->id,
        ]);

        $this->deleteJson('/api/admin/phan-cong?'.http_build_query([
            'ma_giang_vien' => $this->giangVienMot->id,
            'ma_lop_hoc' => $this->lopHoc->id,
        ]))->assertOk()->assertJsonPath('message', 'Đã hủy phân công.');

        $this->assertDatabaseMissing('phan_cong_giang_day', [
            'ma_giang_vien' => $this->giangVienMot->id,
            'ma_lop_hoc' => $this->lopHoc->id,
        ]);
    }

    public function test_lich_da_xoa_khong_con_gay_xung_dot_khi_phan_cong(): void
    {
        $ngayHoc = now()->addDay()->toDateString();
        $lopMoi = $this->taoLop('CS445-04');
        $this->taoLich($this->lopHoc, $ngayHoc, '09:00:00', '11:00:00');
        $lichDaXoa = $this->taoLich($lopMoi, $ngayHoc, '09:00:00', '11:00:00');

        $this->postJson('/api/admin/phan-cong', [
            'ma_giang_vien' => $this->giangVienMot->id,
            'ma_lop_hoc' => $this->lopHoc->id,
        ])->assertCreated();

        $this->deleteJson("/api/admin/lop-hoc/{$lopMoi->id}/lich-hoc/{$lichDaXoa->id}")
            ->assertOk()
            ->assertJsonPath('lich_hoc_id', $lichDaXoa->id);

        $this->assertDatabaseMissing('lich_hoc', ['id' => $lichDaXoa->id]);

        $this->postJson('/api/admin/phan-cong', [
            'ma_giang_vien' => $this->giangVienMot->id,
            'ma_lop_hoc' => $lopMoi->id,
        ])->assertCreated();
    }

    public function test_khong_the_xoa_lich_bang_ma_lop_khac(): void
    {
        $lopKhac = $this->taoLop('CS445-05');
        $lichHoc = $this->taoLich($this->lopHoc, now()->addDay()->toDateString(), '09:00:00', '11:00:00');

        $this->deleteJson("/api/admin/lop-hoc/{$lopKhac->id}/lich-hoc/{$lichHoc->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('lich_hoc', ['id' => $lichHoc->id]);
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

    private function taoLop(string $maLop): LopHoc
    {
        $lop = $this->lopHoc->replicate();
        $lop->ma_lop_hoc = $maLop;
        $lop->ten_lop = 'Lớp '.$maLop;
        $lop->save();

        return $lop;
    }

    private function taoLich(LopHoc $lop, string $ngayHoc, string $batDau, string $ketThuc): LichHoc
    {
        return LichHoc::create([
            'ma_lop_hoc' => $lop->id,
            'ngay_hoc' => $ngayHoc,
            'gio_bat_dau' => $batDau,
            'gio_ket_thuc' => $ketThuc,
            'co_hoc_truc_tuyen' => true,
            'trang_thai' => 'ke_hoach',
        ]);
    }
}
