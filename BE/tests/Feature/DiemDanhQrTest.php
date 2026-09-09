<?php

namespace Tests\Feature;

use App\Events\DiemDanhThanhCong;
use App\Events\MaQrDiemDanhCapNhat;
use App\Events\PhienDiemDanhDong;
use App\Events\PhienDiemDanhMo;
use App\Events\ThongBaoMoi;
use App\Models\ChiTietDiemDanh;
use App\Models\DangKyLopHoc;
use App\Models\GiangVien;
use App\Models\LichHoc;
use App\Models\LopHoc;
use App\Models\MaQrToken;
use App\Models\MonHoc;
use App\Models\PhanCongGiangDay;
use App\Models\PhienDiemDanh;
use App\Models\PhongHocTrucTuyen;
use App\Models\SinhVien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiemDanhQrTest extends TestCase
{
    use RefreshDatabase;

    private LopHoc $lopHoc;

    private LichHoc $lichHoc;

    private PhongHocTrucTuyen $phongHoc;

    private User $taiKhoanGiangVien;

    private GiangVien $giangVien;

    private User $taiKhoanSinhVien;

    private SinhVien $sinhVien;

    protected function setUp(): void
    {
        parent::setUp();

        // Đồng hồ cố định: test ranh giới 10 giây mà không phải sleep.
        $this->travelTo(Carbon::parse('2026-08-27 08:00:00', config('app.timezone')));
        config(['app.fe_url' => 'http://localhost:5173']);

        $monHoc = MonHoc::create([
            'ma_mon_hoc' => 'CS445-QR',
            'ten_mon' => 'Kiểm thử điểm danh QR',
            'so_tin_chi' => 3,
        ]);

        $this->lopHoc = LopHoc::create([
            'ma_lop_hoc' => 'CS445-QR-01',
            'ten_lop' => 'Lớp kiểm thử QR',
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

        $this->phongHoc = PhongHocTrucTuyen::create([
            'ma_phong' => 'PHONG-QR-TEST',
            'ma_lich_hoc' => $this->lichHoc->id,
            'trang_thai' => 'dang_dien_ra',
        ]);

        [$this->taiKhoanGiangVien, $this->giangVien] = $this->taoGiangVien('gv-qr');
        PhanCongGiangDay::create([
            'ma_giang_vien' => $this->giangVien->id,
            'ma_lop_hoc' => $this->lopHoc->id,
            'vai_tro_phu_trach' => 'giang_vien_chinh',
        ]);

        [$this->taiKhoanSinhVien, $this->sinhVien] = $this->taoSinhVien('sv-qr');
        $this->dangKyLop($this->sinhVien);

        // Vẫn chạy middleware, controller và database thật; chỉ chặn phát ra Reverb.
        Event::fake([
            PhienDiemDanhMo::class,
            PhienDiemDanhDong::class,
            MaQrDiemDanhCapNhat::class,
            DiemDanhThanhCong::class,
            ThongBaoMoi::class,
        ]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_mo_phien_tao_qr_hieu_luc_10_giay_va_phat_su_kien(): void
    {
        $phien = $this->moPhien();
        $qr = MaQrToken::where('token', $phien['qr_token'])->firstOrFail();
        $banGhiPhien = PhienDiemDanh::findOrFail($phien['id']);

        $this->assertSame('dang_mo', $phien['trang_thai']);
        $this->assertSame(300, $phien['so_giay']);
        $this->assertSame(64, strlen($qr->token));
        $this->assertTrue($qr->het_han_luc->equalTo(now()->addSeconds(10)));
        $this->assertTrue($banGhiPhien->thoi_gian_bat_dau->equalTo(now()));
        $this->assertTrue($banGhiPhien->thoi_gian_ket_thuc->equalTo(now()->addMinutes(5)));
        $this->assertSame($qr->het_han_luc->toIso8601String(), $phien['qr_het_han_luc']);
        $this->assertSame('http://localhost:5173/diem-danh/'.$qr->token, $phien['duong_dan_qr']);
        $this->assertDatabaseHas('phien_diem_danh', [
            'id' => $phien['id'],
            'ma_lich_hoc' => $this->lichHoc->id,
            'ma_giang_vien' => $this->giangVien->id,
            'trang_thai' => 'dang_mo',
            'hinh_thuc_diem_danh' => 'qr_code',
        ]);

        Event::assertDispatched(PhienDiemDanhMo::class, fn ($event) => $event->maPhong === $this->phongHoc->ma_phong
            && $event->maPhien === $phien['ma_phien']
            && $event->duongDanQr === $phien['duong_dan_qr']
            && $event->soGiay === 300
        );
    }

    public function test_qr_con_han_ghi_nhan_co_mat_va_phat_su_kien(): void
    {
        $phien = $this->moPhien();
        $this->travel(9)->seconds();
        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->postJson($this->urlQuet($phien['qr_token']))
            ->assertOk()
            ->assertJsonPath('thanh_cong', true)
            ->assertJsonPath('mon_hoc', 'Kiểm thử điểm danh QR');

        $this->assertDatabaseCount('chi_tiet_diem_danh', 1);
        $this->assertDatabaseHas('chi_tiet_diem_danh', [
            'ma_phien_diem_danh' => $phien['id'],
            'ma_sinh_vien' => $this->sinhVien->id,
            'trang_thai_diem_danh' => 'co_mat',
            'hinh_thuc_diem_danh' => 'qr_code',
            'thoi_gian_diem_danh' => now()->toDateTimeString(),
        ]);

        Event::assertDispatched(DiemDanhThanhCong::class, fn ($event) => $event->maPhong === $this->phongHoc->ma_phong
            && $event->maSinhVien === $this->sinhVien->id
            && $event->maSinhVienText === $this->sinhVien->ma_sinh_vien
        );
        Event::assertDispatchedTimes(DiemDanhThanhCong::class, 1);
    }

    public function test_qr_khong_ton_tai_khong_ghi_nhan_diem_danh(): void
    {
        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->postJson($this->urlQuet('ma-qr-khong-ton-tai'))
            ->assertNotFound()
            ->assertJsonPath('thanh_cong', false);

        $this->assertDatabaseCount('chi_tiet_diem_danh', 0);
        Event::assertNotDispatched(DiemDanhThanhCong::class);
    }

    public function test_qr_het_han_sau_10_giay_bi_tu_choi_du_phien_van_mo(): void
    {
        $phien = $this->moPhien();
        $this->travel(11)->seconds();
        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->assertTrue(PhienDiemDanh::findOrFail($phien['id'])->conMo());
        $this->postJson($this->urlQuet($phien['qr_token']))
            ->assertNotFound()
            ->assertJsonPath('thanh_cong', false);

        $this->assertDatabaseCount('chi_tiet_diem_danh', 0);
        Event::assertNotDispatched(DiemDanhThanhCong::class);
    }

    public function test_qr_tai_dung_moc_10_giay_cung_bi_tu_choi(): void
    {
        $phien = $this->moPhien();
        $this->travel(10)->seconds();
        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->postJson($this->urlQuet($phien['qr_token']))
            ->assertNotFound()
            ->assertJsonPath('thanh_cong', false);

        $this->assertDatabaseCount('chi_tiet_diem_danh', 0);
        Event::assertNotDispatched(DiemDanhThanhCong::class);
    }

    public function test_quet_lai_ke_ca_qr_moi_khong_tao_ban_ghi_trung(): void
    {
        $phien = $this->moPhien();
        Sanctum::actingAs($this->taiKhoanSinhVien);
        $this->postJson($this->urlQuet($phien['qr_token']))->assertOk();
        $banGhiBanDau = ChiTietDiemDanh::sole();

        $this->travel(1)->seconds();
        Sanctum::actingAs($this->taiKhoanGiangVien);
        $qrMoi = $this->getJson("/api/phien-diem-danh/{$phien['id']}/qr-token")
            ->assertOk()->json('qr_token');
        $this->assertNotSame($phien['qr_token'], $qrMoi);

        Sanctum::actingAs($this->taiKhoanSinhVien);
        $this->postJson($this->urlQuet($qrMoi))
            ->assertOk()
            ->assertJsonPath('thanh_cong', true)
            ->assertJsonPath('da_diem_danh_truoc_do', true)
            ->assertJsonPath('trang_thai_diem_danh', 'co_mat');

        $this->assertDatabaseCount('chi_tiet_diem_danh', 1);
        $this->assertSame($banGhiBanDau->id, ChiTietDiemDanh::sole()->id);
        $this->assertTrue(ChiTietDiemDanh::sole()->thoi_gian_diem_danh
            ->equalTo($banGhiBanDau->thoi_gian_diem_danh));
        Event::assertDispatchedTimes(DiemDanhThanhCong::class, 1);
    }

    public function test_lam_moi_qr_xoa_token_het_han_va_token_moi_su_dung_duoc(): void
    {
        $phien = $this->moPhien();
        $this->travel(11)->seconds();

        $response = $this->getJson("/api/phien-diem-danh/{$phien['id']}/qr-token")
            ->assertOk();
        $qrMoi = $response->json('qr_token');
        $this->assertNotSame($phien['qr_token'], $qrMoi);
        $this->assertDatabaseMissing('ma_qr_token', ['token' => $phien['qr_token']]);
        $this->assertDatabaseCount('ma_qr_token', 1);
        $this->assertTrue(MaQrToken::where('token', $qrMoi)->firstOrFail()
            ->het_han_luc->equalTo(now()->addSeconds(10)));
        Event::assertDispatched(MaQrDiemDanhCapNhat::class, fn ($event) => $event->maPhong === $this->phongHoc->ma_phong
            && $event->maPhien === $phien['ma_phien']
            && $event->duongDanQr === $response->json('duong_dan_qr')
        );

        Sanctum::actingAs($this->taiKhoanSinhVien);
        $this->postJson($this->urlQuet($phien['qr_token']))->assertNotFound();
        $this->postJson($this->urlQuet($qrMoi))
            ->assertOk()->assertJsonPath('thanh_cong', true);
        $this->assertDatabaseCount('chi_tiet_diem_danh', 1);
    }

    public function test_dong_phien_giu_nguoi_co_mat_va_danh_vang_nguoi_chua_quet(): void
    {
        [, $sinhVienChuaQuet] = $this->taoSinhVien('sv-chua-quet');
        $this->dangKyLop($sinhVienChuaQuet);
        $phien = $this->moPhien();
        Sanctum::actingAs($this->taiKhoanSinhVien);
        $this->postJson($this->urlQuet($phien['qr_token']))->assertOk();

        Sanctum::actingAs($this->taiKhoanGiangVien);
        $this->postJson("/api/phien-diem-danh/{$phien['id']}/dong")
            ->assertOk()->assertJsonPath('so_vang', 1);

        $this->assertDatabaseHas('phien_diem_danh', ['id' => $phien['id'], 'trang_thai' => 'da_dong']);
        $this->assertDatabaseCount('chi_tiet_diem_danh', 2);
        $this->assertDatabaseHas('chi_tiet_diem_danh', [
            'ma_phien_diem_danh' => $phien['id'],
            'ma_sinh_vien' => $this->sinhVien->id,
            'trang_thai_diem_danh' => 'co_mat',
            'hinh_thuc_diem_danh' => 'qr_code',
        ]);
        $this->assertDatabaseHas('chi_tiet_diem_danh', [
            'ma_phien_diem_danh' => $phien['id'],
            'ma_sinh_vien' => $sinhVienChuaQuet->id,
            'trang_thai_diem_danh' => 'vang',
            'thoi_gian_diem_danh' => null,
        ]);
        Event::assertDispatched(PhienDiemDanhDong::class, fn ($event) => $event->maPhong === $this->phongHoc->ma_phong && $event->maPhien === $phien['ma_phien']
        );
    }

    public function test_phien_dong_khong_nhan_diem_danh_hoac_cap_qr_moi(): void
    {
        $phien = $this->moPhien();
        $this->postJson("/api/phien-diem-danh/{$phien['id']}/dong")->assertOk();
        $this->assertTrue(MaQrToken::where('token', $phien['qr_token'])->firstOrFail()->conHan());
        $this->getJson("/api/phien-diem-danh/{$phien['id']}/qr-token")->assertUnprocessable();
        $this->assertDatabaseCount('ma_qr_token', 1);

        Sanctum::actingAs($this->taiKhoanSinhVien);
        $this->postJson($this->urlQuet($phien['qr_token']))
            ->assertUnprocessable()
            ->assertJsonPath('thanh_cong', false)
            ->assertJsonPath('message', 'Phiên điểm danh đã đóng.');

        $this->assertDatabaseCount('chi_tiet_diem_danh', 1);
        $this->assertDatabaseHas('chi_tiet_diem_danh', [
            'ma_phien_diem_danh' => $phien['id'],
            'ma_sinh_vien' => $this->sinhVien->id,
            'trang_thai_diem_danh' => 'vang',
            'thoi_gian_diem_danh' => null,
        ]);
        Event::assertNotDispatched(DiemDanhThanhCong::class);
    }

    public function test_phien_het_thoi_gian_tu_choi_ca_qr_van_con_han(): void
    {
        $phien = $this->moPhien(1);
        $this->travel(55)->seconds();
        $qrMoi = $this->getJson("/api/phien-diem-danh/{$phien['id']}/qr-token")
            ->assertOk()->json('qr_token');
        $this->travel(6)->seconds();

        $this->assertTrue(MaQrToken::where('token', $qrMoi)->firstOrFail()->conHan());
        $this->assertFalse(PhienDiemDanh::findOrFail($phien['id'])->conMo());
        $this->getJson("/api/phien-diem-danh/{$phien['id']}/qr-token")->assertUnprocessable();

        Sanctum::actingAs($this->taiKhoanSinhVien);
        $this->postJson($this->urlQuet($qrMoi))
            ->assertUnprocessable()
            ->assertJsonPath('thanh_cong', false)
            ->assertJsonPath('message', 'Đã quá thời gian điểm danh của phiên này.');

        $this->assertDatabaseCount('chi_tiet_diem_danh', 0);
        Event::assertNotDispatched(DiemDanhThanhCong::class);
    }

    public function test_sinh_vien_ngoai_lop_khong_duoc_diem_danh(): void
    {
        $phien = $this->moPhien();
        [$taiKhoanNgoaiLop] = $this->taoSinhVien('sv-ngoai-lop');
        Sanctum::actingAs($taiKhoanNgoaiLop);

        $this->postJson($this->urlQuet($phien['qr_token']))
            ->assertForbidden()->assertJsonPath('thanh_cong', false);

        $this->assertDatabaseCount('chi_tiet_diem_danh', 0);
        Event::assertNotDispatched(DiemDanhThanhCong::class);
    }

    public function test_sinh_vien_dang_ky_cho_duyet_khong_duoc_diem_danh(): void
    {
        DangKyLopHoc::where('ma_sinh_vien', $this->sinhVien->id)
            ->where('ma_lop_hoc', $this->lopHoc->id)
            ->update(['trang_thai' => 'cho_duyet']);
        $phien = $this->moPhien();
        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->postJson($this->urlQuet($phien['qr_token']))
            ->assertForbidden()->assertJsonPath('thanh_cong', false);

        $this->assertDatabaseCount('chi_tiet_diem_danh', 0);
        Event::assertNotDispatched(DiemDanhThanhCong::class);
    }

    public function test_sinh_vien_khong_co_quyen_mo_hoac_dong_phien(): void
    {
        $phien = $this->moPhien();
        Sanctum::actingAs($this->taiKhoanSinhVien);

        $this->postJson('/api/phien-diem-danh', ['ma_lich_hoc' => $this->lichHoc->id])
            ->assertForbidden();
        $this->postJson("/api/phien-diem-danh/{$phien['id']}/dong")->assertForbidden();

        $this->assertDatabaseCount('phien_diem_danh', 1);
        $this->assertDatabaseHas('phien_diem_danh', ['id' => $phien['id'], 'trang_thai' => 'dang_mo']);
        Event::assertNotDispatched(PhienDiemDanhDong::class);
    }

    public function test_giang_vien_khong_phu_trach_khong_duoc_mo_phien_hoac_doi_qr(): void
    {
        $phien = $this->moPhien();
        [$taiKhoanGiangVienKhac] = $this->taoGiangVien('gv-khac');
        Sanctum::actingAs($taiKhoanGiangVienKhac);

        $this->postJson('/api/phien-diem-danh', ['ma_lich_hoc' => $this->lichHoc->id])
            ->assertForbidden();
        $this->getJson("/api/phien-diem-danh/{$phien['id']}/qr-token")->assertForbidden();

        $this->assertDatabaseCount('phien_diem_danh', 1);
        $this->assertDatabaseCount('ma_qr_token', 1);
        $this->assertDatabaseHas('phien_diem_danh', ['id' => $phien['id'], 'trang_thai' => 'dang_mo']);
        Event::assertNotDispatched(MaQrDiemDanhCapNhat::class);
    }

    public function test_chua_dang_nhap_khong_duoc_mo_phien_hoac_quet_qr(): void
    {
        $this->postJson('/api/phien-diem-danh', ['ma_lich_hoc' => $this->lichHoc->id])
            ->assertUnauthorized();
        $this->postJson($this->urlQuet('ma-qr-bat-ky'))->assertUnauthorized();

        $this->assertDatabaseCount('phien_diem_danh', 0);
        $this->assertDatabaseCount('chi_tiet_diem_danh', 0);
        Event::assertNotDispatched(PhienDiemDanhMo::class);
        Event::assertNotDispatched(DiemDanhThanhCong::class);
    }

    public function test_mo_phien_moi_dong_phien_cu_cua_cung_buoi_hoc(): void
    {
        $phienCu = $this->moPhien();
        $phienMoi = $this->moPhien();

        $this->assertNotSame($phienCu['id'], $phienMoi['id']);
        $this->assertDatabaseCount('phien_diem_danh', 2);
        $this->assertSame(1, PhienDiemDanh::where('ma_lich_hoc', $this->lichHoc->id)
            ->where('trang_thai', 'dang_mo')->count());
        $this->assertDatabaseHas('phien_diem_danh', ['id' => $phienCu['id'], 'trang_thai' => 'da_dong']);
        $this->assertDatabaseHas('phien_diem_danh', ['id' => $phienMoi['id'], 'trang_thai' => 'dang_mo']);

        Sanctum::actingAs($this->taiKhoanSinhVien);
        $this->postJson($this->urlQuet($phienCu['qr_token']))->assertUnprocessable();
        $this->postJson($this->urlQuet($phienMoi['qr_token']))
            ->assertOk()->assertJsonPath('thanh_cong', true);
        $this->assertDatabaseCount('chi_tiet_diem_danh', 1);
    }

    public function test_dong_lai_phien_khong_tao_ban_ghi_vang_trung(): void
    {
        $phien = $this->moPhien();
        $this->postJson("/api/phien-diem-danh/{$phien['id']}/dong")->assertOk();
        $this->postJson("/api/phien-diem-danh/{$phien['id']}/dong")
            ->assertOk()->assertJsonPath('message', 'Phiên đã đóng trước đó.');

        $this->assertDatabaseCount('chi_tiet_diem_danh', 1);
        Event::assertDispatchedTimes(PhienDiemDanhDong::class, 1);
    }

    private function moPhien(int $soPhut = 5): array
    {
        Sanctum::actingAs($this->taiKhoanGiangVien);

        return $this->postJson('/api/phien-diem-danh', [
            'ma_lich_hoc' => $this->lichHoc->id,
            'so_phut' => $soPhut,
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
