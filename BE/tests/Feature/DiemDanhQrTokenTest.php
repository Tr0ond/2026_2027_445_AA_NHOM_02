<?php

namespace Tests\Feature;

use App\Events\DiemDanhThanhCong;
use App\Models\DangKyLopHoc;
use App\Models\DonXinPhep;
use App\Models\GiangVien;
use App\Models\LichHoc;
use App\Models\LopHoc;
use App\Models\MaQrToken;
use App\Models\MonHoc;
use App\Models\PhanCongGiangDay;
use App\Models\SinhVien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiemDanhQrTokenTest extends TestCase
{
    use RefreshDatabase;

    private LopHoc $lopHoc;

    private LichHoc $lichHoc;

    private User $taiKhoanGiangVien;

    private GiangVien $giangVien;

    private User $taiKhoanSinhVien;

    private SinhVien $sinhVien;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-13 08:00:00', config('app.timezone')));
        config(['app.fe_url' => 'http://localhost:5173']);
        Event::fake();

        $monHoc = MonHoc::create([
            'ma_mon_hoc' => 'CS445-QR',
            'ten_mon' => 'Kiểm thử QR động',
            'so_tin_chi' => 3,
        ]);

        $this->lopHoc = LopHoc::create([
            'ma_lop_hoc' => 'CS445-QR-01',
            'ten_lop' => 'Lớp QR động',
            'ma_mon_hoc' => $monHoc->id,
            'hoc_ky' => '1',
            'nam_hoc' => '2026-2027',
            'so_luong_toi_da' => 40,
            'trang_thai' => 'dang_hoc',
        ]);

        $this->lichHoc = LichHoc::create([
            'ma_lop_hoc' => $this->lopHoc->id,
            'ngay_hoc' => now()->toDateString(),
            'gio_bat_dau' => '08:00:00',
            'gio_ket_thuc' => '10:00:00',
            'phong_hoc' => 'A101',
            'trang_thai' => 'ke_hoach',
        ]);

        [$this->taiKhoanGiangVien, $this->giangVien] = $this->taoGiangVien('gv-qr');
        PhanCongGiangDay::create([
            'ma_giang_vien' => $this->giangVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'vai_tro_phu_trach' => 'giang_vien_chinh',
        ]);

        [$this->taiKhoanSinhVien, $this->sinhVien] = $this->taoSinhVien('sv-qr');
        $this->dangKyLop($this->sinhVien);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_diem_danh_bang_token_hop_le_va_token_xoay_sau_10_giay(): void
    {
        $phien = $this->moPhien();
        $tokenCu = MaQrToken::where('token', $phien['qr_token'])->firstOrFail();

        $this->assertSame(10, $phien['qr_xoay_sau_giay']);
        $this->assertTrue($tokenCu->het_han_luc->equalTo(now()->addSeconds(15)));

        $this->travel(10)->seconds();
        $tokenMoi = $this->getJson("/api/phien-diem-danh/{$phien['id']}/qr-token")
            ->assertOk()
            ->assertJsonPath('qr_xoay_sau_giay', 10)
            ->json('qr_token');

        $this->assertNotSame($tokenCu->token, $tokenMoi);

        Sanctum::actingAs($this->taiKhoanSinhVien);
        $this->postJson($this->urlQuet($tokenMoi))
            ->assertOk()
            ->assertJsonPath('thanh_cong', true);

        $this->assertDatabaseHas('chi_tiet_diem_danh', [
            'ma_phien_diem_danh' => $phien['id'],
            'ma_sinh_vien' => $this->sinhVien->id,
            'trang_thai_diem_danh' => 'co_mat',
            'hinh_thuc_diem_danh' => 'qr_code',
        ]);
    }

    public function test_token_het_han_bi_tu_choi(): void
    {
        $phien = $this->moPhien();
        $this->travel(16)->seconds();
        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->postJson($this->urlQuet($phien['qr_token']))
            ->assertNotFound()
            ->assertJsonPath('thanh_cong', false);

        $this->assertDatabaseCount('chi_tiet_diem_danh', 0);
    }

    public function test_gui_ma_sinh_vien_khac_de_quet_ho_bi_chan(): void
    {
        [, $sinhVienKhac] = $this->taoSinhVien('sv-duoc-quet-ho');
        $this->dangKyLop($sinhVienKhac);
        $phien = $this->moPhien();
        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->postJson($this->urlQuet($phien['qr_token']), [
            'ma_sinh_vien' => $sinhVienKhac->id,
        ])->assertForbidden()
            ->assertJsonPath('thanh_cong', false)
            ->assertJsonPath('message', 'Không thể điểm danh thay cho sinh viên khác.');

        $this->assertDatabaseCount('chi_tiet_diem_danh', 0);
    }

    public function test_sinh_vien_vang_co_phep_khong_bi_chuyen_thanh_co_mat_khi_quet_qr(): void
    {
        DonXinPhep::create([
            'ma_sinh_vien' => $this->sinhVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'ma_lich_hoc' => $this->lichHoc->id,
            'ngay_nghi' => $this->lichHoc->ngay_hoc,
            'ly_do' => 'Ốm có xác nhận',
            'trang_thai' => 'duoc_duyet',
            'nguoi_duyet' => $this->taiKhoanGiangVien->id,
            'thoi_gian_duyet' => now(),
        ]);

        $phien = $this->moPhien();
        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->postJson($this->urlQuet($phien['qr_token']))
            ->assertOk()
            ->assertJsonPath('da_diem_danh_truoc_do', true)
            ->assertJsonPath('trang_thai_diem_danh', 'vang_co_phep');

        $this->assertDatabaseHas('chi_tiet_diem_danh', [
            'ma_phien_diem_danh' => $phien['id'],
            'ma_sinh_vien' => $this->sinhVien->id,
            'trang_thai_diem_danh' => 'vang_co_phep',
            'thoi_gian_diem_danh' => null,
        ]);
        Event::assertNotDispatched(DiemDanhThanhCong::class);
    }

    private function moPhien(): array
    {
        Sanctum::actingAs($this->taiKhoanGiangVien);

        return $this->postJson('/api/phien-diem-danh', [
            'ma_lich_hoc' => $this->lichHoc->id,
            'so_phut' => 5,
        ])->assertCreated()->json('phien');
    }

    private function urlQuet(string $token): string
    {
        return '/api/sinh-vien/diem-danh/qr/'.$token;
    }

    private function dangKyLop(SinhVien $sinhVien): void
    {
        DangKyLopHoc::create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'ngay_dang_ky' => now()->toDateString(),
            'trang_thai' => 'da_duyet',
        ]);
    }

    private function taoTaiKhoan(string $vaiTro, string $ma): User
    {
        return User::create([
            'ho_ten' => 'Tài khoản '.$ma,
            'email' => $ma.'@portal.test',
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

    private function taoSinhVien(string $ma): array
    {
        $taiKhoan = $this->taoTaiKhoan('sinh_vien', $ma);
        $sinhVien = SinhVien::create([
            'ma_sinh_vien' => strtoupper($ma),
            'ma_tai_khoan' => $taiKhoan->id,
        ]);

        return [$taiKhoan, $sinhVien];
    }
}
