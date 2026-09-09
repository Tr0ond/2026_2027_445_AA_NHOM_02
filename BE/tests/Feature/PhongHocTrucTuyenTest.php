<?php

namespace Tests\Feature;

use App\Events\CapQuyenPhong;
use App\Events\NguoiChiaSeManHinh;
use App\Events\PhienDiemDanhDong;
use App\Events\PhienDiemDanhMo;
use App\Events\PhongHocKetThuc;
use App\Events\SinhVienGioTay;
use App\Events\ThanhVienPhongCapNhat;
use App\Events\ThongBaoMoi;
use App\Events\TinNhanMoi;
use App\Models\DangKyLopHoc;
use App\Models\DonXinPhep;
use App\Models\GiangVien;
use App\Models\LichHoc;
use App\Models\LopHoc;
use App\Models\MonHoc;
use App\Models\PhanCongGiangDay;
use App\Models\PhongHocTrucTuyen;
use App\Models\SinhVien;
use App\Models\ThanhVienPhongTrucTuyen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhongHocTrucTuyenTest extends TestCase
{
    use RefreshDatabase;

    private LichHoc $lich;

    private LopHoc $lop;

    private User $gv;

    private User $sv;

    private SinhVien $sinhVien;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Bộ test phòng học chỉ được chạy với SQLite :memory:.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.agora.app_id' => null, 'services.agora.certificate' => null, 'app.fe_url' => 'http://localhost:5173']);
        $mon = MonHoc::create(['ma_mon_hoc' => 'ROOM', 'ten_mon' => 'Kiểm thử phòng học', 'so_tin_chi' => 3]);
        $this->lop = LopHoc::create([
            'ma_lop_hoc' => 'ROOM-01', 'ten_lop' => 'Lớp phòng học', 'ma_mon_hoc' => $mon->id,
            'hoc_ky' => '1', 'nam_hoc' => '2026-2027', 'so_luong_toi_da' => 40, 'trang_thai' => 'dang_hoc',
        ]);
        $this->lich = LichHoc::create([
            'ma_lop_hoc' => $this->lop->id, 'ngay_hoc' => now()->toDateString(),
            'gio_bat_dau' => '08:00:00', 'gio_ket_thuc' => '10:00:00',
            'co_hoc_truc_tuyen' => true, 'trang_thai' => 'ke_hoach',
        ]);
        $this->gv = $this->taiKhoan('giang_vien', 'gv-room');
        $giangVien = GiangVien::create(['ma_giang_vien' => 'GVROOM', 'ma_tai_khoan' => $this->gv->id]);
        PhanCongGiangDay::create(['ma_giang_vien' => $giangVien->id, 'ma_lop_hoc' => $this->lop->id]);
        $this->sv = $this->taiKhoan('sinh_vien', 'sv-room');
        $this->sinhVien = SinhVien::create(['ma_sinh_vien' => 'SVROOM', 'ma_tai_khoan' => $this->sv->id]);
        DangKyLopHoc::create([
            'ma_sinh_vien' => $this->sinhVien->id, 'ma_lop_hoc' => $this->lop->id,
            'ngay_dang_ky' => now()->toDateString(), 'trang_thai' => 'da_duyet',
        ]);
        Event::fake([
            CapQuyenPhong::class, NguoiChiaSeManHinh::class, PhienDiemDanhDong::class,
            PhienDiemDanhMo::class, PhongHocKetThuc::class, SinhVienGioTay::class,
            ThanhVienPhongCapNhat::class, ThongBaoMoi::class, TinNhanMoi::class,
        ]);
    }

    public function test_giang_vien_mo_phong_va_bam_lai_khong_tao_trung(): void
    {
        $ma = $this->moPhong();
        $this->postJson('/api/phong/bat-dau', ['ma_lich_hoc' => $this->lich->id])
            ->assertOk()->assertJsonPath('phong.ma_phong', $ma)
            ->assertJsonPath('thong_tin_agora.app_id', null);
        $this->assertDatabaseCount('phong_hoc_truc_tuyen', 1);
        $this->assertDatabaseCount('thanh_vien_phong_truc_tuyen', 1);
        $this->assertDatabaseHas('lich_hoc', ['id' => $this->lich->id, 'trang_thai' => 'dang_dien_ra']);
        Event::assertDispatched(ThanhVienPhongCapNhat::class, fn ($e) => $e->maPhong === $ma && $e->hanhDong === 'tham_gia');
    }

    public function test_khong_mo_phong_cho_buoi_truc_tiep_da_huy_hoac_da_hoc(): void
    {
        Sanctum::actingAs($this->gv);
        $this->lich->update(['co_hoc_truc_tuyen' => false]);
        $this->postJson('/api/phong/bat-dau', ['ma_lich_hoc' => $this->lich->id])->assertUnprocessable();
        foreach (['da_huy', 'da_hoc'] as $trangThai) {
            $this->lich->update(['co_hoc_truc_tuyen' => true, 'trang_thai' => $trangThai]);
            $this->postJson('/api/phong/bat-dau', ['ma_lich_hoc' => $this->lich->id])->assertUnprocessable();
        }
        $this->assertDatabaseCount('phong_hoc_truc_tuyen', 0);
    }

    public function test_cau_hinh_agora_sai_khong_tao_phong_va_khong_lo_certificate(): void
    {
        config(['services.agora.app_id' => str_repeat('a', 32), 'services.agora.certificate' => 'invalid-test-secret']);
        Sanctum::actingAs($this->gv);
        $response = $this->postJson('/api/phong/bat-dau', ['ma_lich_hoc' => $this->lich->id])->assertStatus(503);
        $this->assertStringNotContainsString('invalid-test-secret', $response->getContent());
        $this->assertDatabaseCount('phong_hoc_truc_tuyen', 0);
        $this->assertDatabaseCount('thanh_vien_phong_truc_tuyen', 0);
        $this->assertDatabaseHas('lich_hoc', ['id' => $this->lich->id, 'trang_thai' => 'ke_hoach']);
    }

    public function test_chi_giang_vien_phu_trach_duoc_mo_phong(): void
    {
        foreach ([$this->sv, $this->taiKhoan('admin', 'admin-open'), $this->taiKhoan('giang_vien', 'gv-khong-ho-so'), $this->giangVienNgoaiLop()] as $user) {
            Sanctum::actingAs($user);
            $this->postJson('/api/phong/bat-dau', ['ma_lich_hoc' => $this->lich->id])->assertForbidden();
        }
        $this->assertDatabaseCount('phong_hoc_truc_tuyen', 0);
    }

    public function test_sinh_vien_da_duyet_vao_phong_va_nhan_quyen_mac_dinh(): void
    {
        $ma = $this->moPhong();
        Sanctum::actingAs($this->sv);
        $this->postJson("/api/phong/$ma/tham-gia")->assertOk()
            ->assertJsonPath('vai_tro', 'sinh_vien')->assertJsonPath('quyen.mac', false)
            ->assertJsonPath('quyen.chia_se', false)->assertJsonPath('kenh_websocket', 'phong.'.$ma)
            ->assertJsonPath('thong_tin_agora.uid', $this->sv->id);
        $this->postJson("/api/phong/$ma/tham-gia")->assertOk();
        $this->assertDatabaseCount('thanh_vien_phong_truc_tuyen', 2);
        $this->getJson("/api/phong/$ma/thanh-vien")->assertOk()->assertJsonCount(2, 'danh_sach');
    }

    public function test_chua_duyet_huy_dang_ky_ngoai_lop_hoac_khoa_khong_duoc_vao(): void
    {
        $ma = $this->moPhong();
        Sanctum::actingAs($this->sv);
        foreach (['cho_duyet', 'huy'] as $trangThai) {
            DangKyLopHoc::where('ma_sinh_vien', $this->sinhVien->id)->update(['trang_thai' => $trangThai]);
            $this->postJson("/api/phong/$ma/tham-gia")->assertForbidden();
        }
        DangKyLopHoc::where('ma_sinh_vien', $this->sinhVien->id)->update(['trang_thai' => 'da_duyet']);
        $this->sv->update(['trang_thai' => 'khoa']);
        $this->postJson("/api/phong/$ma/tham-gia")->assertForbidden();
        $ngoaiLop = $this->taiKhoan('sinh_vien', 'sv-ngoai');
        SinhVien::create(['ma_sinh_vien' => 'SVNGOAI', 'ma_tai_khoan' => $ngoaiLop->id]);
        Sanctum::actingAs($ngoaiLop);
        $this->postJson("/api/phong/$ma/tham-gia")->assertForbidden();
        Sanctum::actingAs($this->giangVienNgoaiLop());
        $this->postJson("/api/phong/$ma/tham-gia")->assertForbidden();
        $this->assertDatabaseCount('thanh_vien_phong_truc_tuyen', 1);
    }

    public function test_chua_dang_nhap_bi_tu_choi(): void
    {
        $this->postJson('/api/phong/bat-dau', ['ma_lich_hoc' => $this->lich->id])->assertUnauthorized();
        $this->getJson('/api/phong/PHNONE/tin-nhan')->assertUnauthorized();
        $this->postJson('/api/broadcasting/auth', ['channel_name' => 'private-phong.PHNONE', 'socket_id' => '1.1'])->assertUnauthorized();
    }

    public function test_phong_khong_ton_tai_va_du_lieu_mo_phong_sai(): void
    {
        Sanctum::actingAs($this->gv);
        $this->postJson('/api/phong/bat-dau', [])->assertUnprocessable();
        $this->postJson('/api/phong/bat-dau', ['ma_lich_hoc' => 999999])->assertUnprocessable();
        $this->postJson('/api/phong/PHNONE/tham-gia')->assertNotFound();
    }

    public function test_chua_vao_phong_khong_doc_chat_gui_chat_gio_tay_hay_doc_thanh_vien(): void
    {
        $ma = $this->moPhong();
        Sanctum::actingAs($this->sv);
        $this->getJson("/api/phong/$ma/thanh-vien")->assertForbidden();
        $this->getJson("/api/phong/$ma/tin-nhan")->assertForbidden();
        $this->postJson("/api/phong/$ma/tin-nhan", ['noi_dung' => 'khong hop le'])->assertForbidden();
        $this->postJson("/api/phong/$ma/gio-tay")->assertForbidden();
        $this->assertDatabaseCount('tin_nhan_phong', 0);
    }

    public function test_chat_duoc_luu_va_broadcast_dung_kenh_phong(): void
    {
        $ma = $this->moVaVaoPhong();
        $this->postJson("/api/phong/$ma/tin-nhan", ['noi_dung' => 'Em chào thầy'])
            ->assertCreated()->assertJsonPath('tin_nhan.noi_dung', 'Em chào thầy')
            ->assertJsonPath('tin_nhan.vai_tro', 'sinh_vien');
        $this->getJson("/api/phong/$ma/tin-nhan")->assertOk()->assertJsonCount(1, 'danh_sach')
            ->assertJsonPath('danh_sach.0.ho_ten', $this->sv->ho_ten);
        Event::assertDispatched(TinNhanMoi::class, fn ($e) => $e->maPhong === $ma
            && $e->broadcastOn()[0]->name === 'private-phong.'.$ma
            && $e->broadcastAs() === 'tin.nhan.moi'
            && $e->broadcastWith()['noi_dung'] === 'Em chào thầy');
        $this->assertDatabaseCount('tin_nhan_phong', 1);
    }

    public function test_chat_rong_hoac_qua_1000_ky_tu_bi_tu_choi(): void
    {
        $ma = $this->moVaVaoPhong();
        foreach (['', '   ', str_repeat('a', 1001)] as $noiDung) {
            $this->postJson("/api/phong/$ma/tin-nhan", ['noi_dung' => $noiDung])->assertUnprocessable();
        }
        $this->assertDatabaseCount('tin_nhan_phong', 0);
        Event::assertNotDispatched(TinNhanMoi::class);
    }

    public function test_khong_doc_hoac_gui_tin_nhan_sang_phong_lop_khac(): void
    {
        $ma = $this->moVaVaoPhong();
        $lopKhac = $this->lop->replicate();
        $lopKhac->ma_lop_hoc = 'ROOM-OTHER';
        $lopKhac->save();
        $lichKhac = $this->lich->replicate();
        $lichKhac->ma_lop_hoc = $lopKhac->id;
        $lichKhac->save();
        PhongHocTrucTuyen::create(['ma_phong' => 'PHOTHER', 'ma_lich_hoc' => $lichKhac->id, 'trang_thai' => 'dang_dien_ra']);
        $this->postJson('/api/phong/PHOTHER/tin-nhan', ['noi_dung' => 'ngoai phong'])->assertForbidden();
        $this->getJson('/api/phong/PHOTHER/tin-nhan')->assertForbidden();
        $this->getJson('/api/phong/PHOTHER/thanh-vien')->assertForbidden();
        $this->getJson("/api/phong/$ma/tin-nhan")->assertOk();
    }

    public function test_sinh_vien_gio_tay_va_ha_tay_co_su_kien(): void
    {
        $ma = $this->moVaVaoPhong();
        $this->postJson("/api/phong/$ma/gio-tay")->assertOk()->assertJsonPath('dang_gio', true);
        $this->postJson("/api/phong/$ma/gio-tay")->assertOk()->assertJsonPath('dang_gio', false);
        Event::assertDispatchedTimes(SinhVienGioTay::class, 2);
        Sanctum::actingAs($this->gv);
        $this->postJson("/api/phong/$ma/gio-tay")->assertForbidden();
    }

    public function test_cap_quyen_chia_se_va_thu_hoi_luu_dung_trang_thai(): void
    {
        $ma = $this->moVaVaoPhong();
        $this->postJson("/api/phong/$ma/chia-se-trang-thai", ['dang_chia_se' => true])->assertForbidden();
        $this->postJson("/api/phong/$ma/gio-tay")->assertOk();
        Sanctum::actingAs($this->gv);
        $quyen = ['ma_tai_khoan' => $this->sv->id, 'duoc_phep_mac' => true, 'duoc_phep_chia_se' => true];
        $this->postJson("/api/phong/$ma/cap-quyen", $quyen)->assertOk();
        Sanctum::actingAs($this->sv);
        $this->postJson("/api/phong/$ma/chia-se-trang-thai", ['dang_chia_se' => true])->assertOk();
        $this->assertDatabaseHas('thanh_vien_phong_truc_tuyen', ['ma_tai_khoan' => $this->sv->id, 'dang_chia_se' => true, 'gio_tay' => false]);
        Sanctum::actingAs($this->gv);
        $this->postJson("/api/phong/$ma/cap-quyen", array_merge($quyen, ['duoc_phep_mac' => false, 'duoc_phep_chia_se' => false]))->assertOk();
        $this->assertDatabaseHas('thanh_vien_phong_truc_tuyen', ['ma_tai_khoan' => $this->sv->id, 'dang_chia_se' => false]);
        Event::assertDispatched(CapQuyenPhong::class, fn ($e) => $e->maPhong === $ma && ! $e->duocPhepMac && ! $e->duocPhepChiaSe);
        Event::assertDispatched(NguoiChiaSeManHinh::class, fn ($e) => $e->maPhong === $ma && ! $e->dangChiaSe);
    }

    public function test_sinh_vien_va_giang_vien_ngoai_lop_khong_duoc_cap_quyen(): void
    {
        $ma = $this->moVaVaoPhong();
        $payload = ['ma_tai_khoan' => $this->sv->id, 'duoc_phep_mac' => true, 'duoc_phep_chia_se' => true];
        $this->postJson("/api/phong/$ma/cap-quyen", $payload)->assertForbidden();
        Sanctum::actingAs($this->giangVienNgoaiLop());
        $this->postJson("/api/phong/$ma/cap-quyen", $payload)->assertForbidden();
        $this->postJson("/api/phong/$ma/ket-thuc")->assertForbidden();
        $this->assertDatabaseHas('thanh_vien_phong_truc_tuyen', ['ma_tai_khoan' => $this->sv->id, 'duoc_phep_mac' => false]);
    }

    public function test_roi_phong_xoa_quyen_va_khong_con_tuong_tac_duoc(): void
    {
        $ma = $this->moVaVaoPhong();
        $this->postJson("/api/phong/$ma/gio-tay")->assertOk();
        $this->postJson("/api/phong/$ma/roi")->assertOk();
        $this->postJson("/api/phong/$ma/roi")->assertOk();
        $this->postJson("/api/phong/$ma/gio-tay")->assertForbidden();
        $this->postJson("/api/phong/$ma/tin-nhan", ['noi_dung' => 'sau khi roi'])->assertForbidden();
        $this->postJson("/api/phong/$ma/chia-se-trang-thai", ['dang_chia_se' => true])->assertForbidden();
        $this->assertNotNull(ThanhVienPhongTrucTuyen::where('ma_tai_khoan', $this->sv->id)->first()->thoi_gian_roi);
        Event::assertDispatched(ThanhVienPhongCapNhat::class, fn ($e) => $e->maTaiKhoan === $this->sv->id && $e->hanhDong === 'roi');
        Sanctum::actingAs($this->gv);
        $this->getJson("/api/phong/$ma/thanh-vien")->assertOk()->assertJsonCount(1, 'danh_sach');
        $this->postJson("/api/phong/$ma/cap-quyen", ['ma_tai_khoan' => $this->sv->id, 'duoc_phep_mac' => true, 'duoc_phep_chia_se' => true])->assertNotFound();
        Sanctum::actingAs($this->sv);
        $this->postJson("/api/phong/$ma/tham-gia")->assertOk()->assertJsonPath('quyen.gio_tay', false);
        $this->assertDatabaseCount('thanh_vien_phong_truc_tuyen', 2);
    }

    public function test_ket_thuc_dong_qr_ghi_vang_va_giu_vang_co_phep(): void
    {
        $ma = $this->moVaVaoPhong();
        $svPhep = $this->taiKhoan('sinh_vien', 'sv-phep');
        $phep = SinhVien::create(['ma_sinh_vien' => 'SVPHEP', 'ma_tai_khoan' => $svPhep->id]);
        DangKyLopHoc::create(['ma_sinh_vien' => $phep->id, 'ma_lop_hoc' => $this->lop->id, 'ngay_dang_ky' => now()->toDateString(), 'trang_thai' => 'da_duyet']);
        DonXinPhep::create([
            'ma_sinh_vien' => $phep->id, 'ma_lop_hoc' => $this->lop->id, 'ma_lich_hoc' => $this->lich->id,
            'ngay_nghi' => now()->toDateString(), 'ly_do' => 'Bị ốm', 'trang_thai' => 'duoc_duyet',
        ]);
        Sanctum::actingAs($this->gv);
        $phien = $this->postJson('/api/phien-diem-danh', ['ma_lich_hoc' => $this->lich->id])->assertCreated()->json('phien');
        $this->postJson("/api/phong/$ma/ket-thuc")->assertOk();
        $this->postJson("/api/phong/$ma/ket-thuc")->assertOk();
        $this->assertDatabaseHas('phien_diem_danh', ['id' => $phien['id'], 'trang_thai' => 'da_dong']);
        $this->assertDatabaseHas('chi_tiet_diem_danh', ['ma_phien_diem_danh' => $phien['id'], 'ma_sinh_vien' => $this->sinhVien->id, 'trang_thai_diem_danh' => 'vang']);
        $this->assertDatabaseHas('chi_tiet_diem_danh', ['ma_phien_diem_danh' => $phien['id'], 'ma_sinh_vien' => $phep->id, 'trang_thai_diem_danh' => 'vang_co_phep']);
        $this->assertDatabaseCount('chi_tiet_diem_danh', 2);
        $this->assertDatabaseHas('lich_hoc', ['id' => $this->lich->id, 'trang_thai' => 'da_hoc']);
        $this->assertSame(0, ThanhVienPhongTrucTuyen::whereNull('thoi_gian_roi')->count());
        Event::assertDispatchedTimes(PhongHocKetThuc::class, 1);
        Sanctum::actingAs($this->sv);
        $this->postJson('/api/sinh-vien/diem-danh/qr/'.$phien['qr_token'])->assertUnprocessable();
    }

    public function test_phong_da_ket_thuc_khong_vao_lai_gui_chat_gio_tay_hay_cap_quyen(): void
    {
        $ma = $this->moVaVaoPhong();
        Sanctum::actingAs($this->gv);
        $this->postJson("/api/phong/$ma/ket-thuc")->assertOk();
        $this->postJson('/api/phong/bat-dau', ['ma_lich_hoc' => $this->lich->id])->assertUnprocessable();
        $this->postJson("/api/phong/$ma/cap-quyen", ['ma_tai_khoan' => $this->sv->id, 'duoc_phep_mac' => true, 'duoc_phep_chia_se' => true])->assertUnprocessable();
        Sanctum::actingAs($this->sv);
        $this->postJson("/api/phong/$ma/tham-gia")->assertUnprocessable();
        $this->postJson("/api/phong/$ma/tin-nhan", ['noi_dung' => 'phong dong'])->assertUnprocessable();
        $this->postJson("/api/phong/$ma/gio-tay")->assertUnprocessable();
        $this->postJson("/api/phong/$ma/roi")->assertOk();
    }

    public function test_admin_ket_thuc_phong_nhung_khong_bi_gan_vai_tro_sinh_vien(): void
    {
        $ma = $this->moPhong();
        Sanctum::actingAs($this->taiKhoan('admin', 'admin-end'));
        $this->postJson("/api/phong/$ma/tham-gia")->assertForbidden();
        $this->postJson("/api/phong/$ma/ket-thuc")->assertOk();
        $this->assertDatabaseHas('phong_hoc_truc_tuyen', ['ma_phong' => $ma, 'trang_thai' => 'da_ket_thuc']);
    }

    public function test_vao_muon_nhan_phien_qr_dang_mo_va_thoi_han(): void
    {
        $ma = $this->moPhong();
        $phien = $this->postJson('/api/phien-diem-danh', ['ma_lich_hoc' => $this->lich->id])->assertCreated()->json('phien');
        Sanctum::actingAs($this->sv);
        $this->postJson("/api/phong/$ma/tham-gia")->assertOk()
            ->assertJsonPath('phien_diem_danh.ma_phien', $phien['ma_phien'])
            ->assertJsonPath('phien_diem_danh.duong_dan_qr', $phien['duong_dan_qr'])
            ->assertJsonPath('phien_diem_danh.qr_het_han_luc', $phien['qr_het_han_luc']);
    }

    public function test_lay_dung_phong_tren_lich_day_va_lich_hoc_sau_khi_mo(): void
    {
        $ma = $this->moPhong();
        $this->getJson('/api/lop-day/buoi-hoc')->assertOk()->assertJsonPath('danh_sach.0.phong.ma_phong', $ma);
        Sanctum::actingAs($this->sv);
        $this->getJson('/api/lich-hoc')->assertOk()->assertJsonPath('danh_sach.0.phong_truc_tuyen.ma_phong', $ma);
    }

    public function test_xac_thuc_websocket_chi_cho_thanh_vien_lop_va_tu_choi_phong_dong(): void
    {
        $ma = $this->moPhong();
        // Chỉ ký xác thực private channel cục bộ, không gọi máy chủ Reverb.
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret', 'broadcasting.connections.reverb.app_id' => 'test-id',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            'broadcasting.connections.reverb.options.port' => 8080, 'broadcasting.connections.reverb.options.scheme' => 'http']);
        Broadcast::purge('reverb');
        require base_path('routes/channels.php');
        $payload = ['channel_name' => 'private-phong.'.$ma, 'socket_id' => '123.456'];
        Sanctum::actingAs($this->sv);
        $this->postJson('/api/broadcasting/auth', $payload)->assertOk()->assertJsonStructure(['auth']);
        Sanctum::actingAs($this->giangVienNgoaiLop());
        $this->postJson('/api/broadcasting/auth', $payload)->assertForbidden();
        DangKyLopHoc::where('ma_sinh_vien', $this->sinhVien->id)->update(['trang_thai' => 'cho_duyet']);
        Sanctum::actingAs($this->sv);
        $this->postJson('/api/broadcasting/auth', $payload)->assertForbidden();
        Sanctum::actingAs($this->gv);
        $this->postJson('/api/broadcasting/auth', $payload)->assertOk();
        PhongHocTrucTuyen::where('ma_phong', $ma)->update(['trang_thai' => 'da_ket_thuc']);
        $this->postJson('/api/broadcasting/auth', $payload)->assertForbidden();
    }

    private function moPhong(): string
    {
        Sanctum::actingAs($this->gv);

        return $this->postJson('/api/phong/bat-dau', ['ma_lich_hoc' => $this->lich->id])
            ->assertCreated()->assertJsonPath('phong.nen_tang', 'Agora')->json('phong.ma_phong');
    }

    private function moVaVaoPhong(): string
    {
        $ma = $this->moPhong();
        Sanctum::actingAs($this->sv);
        $this->postJson("/api/phong/$ma/tham-gia")->assertOk();

        return $ma;
    }

    private function taiKhoan(string $vaiTro, string $ma): User
    {
        return User::create(['ho_ten' => $ma, 'email' => $ma.'@portal.test', 'mat_khau' => 'password', 'vai_tro' => $vaiTro, 'trang_thai' => 'hoat_dong']);
    }

    private function giangVienNgoaiLop(): User
    {
        $user = $this->taiKhoan('giang_vien', 'gv-ngoai');
        GiangVien::create(['ma_giang_vien' => 'GVNGOAI', 'ma_tai_khoan' => $user->id]);

        return $user;
    }
}
