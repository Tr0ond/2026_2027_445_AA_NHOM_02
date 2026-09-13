<?php

namespace Tests\Feature;

use App\Events\ThongBaoMoi;
use App\Models\ThongBao;
use App\Models\User;
use App\Services\ThongBaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ThongBaoRealtimeTest extends TestCase
{
    use RefreshDatabase;

    private User $nguoiNhan;

    private User $nguoiKhac;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nguoiNhan = $this->taoTaiKhoan('nguoi-nhan');
        $this->nguoiKhac = $this->taoTaiKhoan('nguoi-khac');

        Event::fake([ThongBaoMoi::class]);
    }

    public function test_tao_thong_bao_luu_database_va_phat_dung_kenh_rieng(): void
    {
        $thongBao = app(ThongBaoService::class)->tao(
            $this->nguoiNhan->id,
            'diem_danh',
            'Điểm danh thành công',
            'Bạn đã được ghi nhận có mặt.',
            ['ma_phien' => 15],
        );

        $this->assertDatabaseHas('thong_bao', [
            'id' => $thongBao->id,
            'ma_tai_khoan' => $this->nguoiNhan->id,
            'loai' => 'diem_danh',
            'da_doc' => false,
        ]);

        Event::assertDispatched(ThongBaoMoi::class, function (ThongBaoMoi $event) use ($thongBao): bool {
            return $event->maTaiKhoan === $this->nguoiNhan->id
                && $event->broadcastOn()[0]->name === 'private-nguoi-dung.'.$this->nguoiNhan->id
                && $event->broadcastAs() === 'thong.bao.moi'
                && $event->broadcastWith()['thong_bao']['id'] === $thongBao->id
                && $event->broadcastWith()['thong_bao']['du_lieu']['ma_phien'] === 15;
        });
    }

    public function test_api_chi_cho_xem_va_danh_dau_thong_bao_cua_chinh_minh(): void
    {
        $thongBaoMot = $this->taoThongBao($this->nguoiNhan, 'Thông báo 1');
        $thongBaoHai = $this->taoThongBao($this->nguoiNhan, 'Thông báo 2');
        $thongBaoNguoiKhac = $this->taoThongBao($this->nguoiKhac, 'Thông báo riêng');

        Sanctum::actingAs($this->nguoiNhan);

        $this->getJson('/api/thong-bao')
            ->assertOk()
            ->assertJsonCount(2, 'danh_sach')
            ->assertJsonPath('chua_doc', 2);

        $this->postJson("/api/thong-bao/{$thongBaoMot->id}/da-doc")
            ->assertOk();

        $this->assertDatabaseHas('thong_bao', [
            'id' => $thongBaoMot->id,
            'ma_tai_khoan' => $this->nguoiNhan->id,
            'da_doc' => true,
        ]);

        $this->postJson("/api/thong-bao/{$thongBaoNguoiKhac->id}/da-doc")
            ->assertForbidden();

        $this->postJson('/api/thong-bao/doc-tat-ca')
            ->assertOk();

        $this->assertSame(0, ThongBao::where('ma_tai_khoan', $this->nguoiNhan->id)
            ->where('da_doc', false)
            ->count());
        $this->assertDatabaseHas('thong_bao', [
            'id' => $thongBaoHai->id,
            'da_doc' => true,
        ]);
        $this->assertDatabaseHas('thong_bao', [
            'id' => $thongBaoNguoiKhac->id,
            'ma_tai_khoan' => $this->nguoiKhac->id,
            'da_doc' => false,
        ]);
    }

    public function test_xac_thuc_websocket_chi_cho_dung_chu_kenh_thong_bao(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-id',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            'broadcasting.connections.reverb.options.port' => 8080,
            'broadcasting.connections.reverb.options.scheme' => 'http',
        ]);
        Broadcast::purge('reverb');
        require base_path('routes/channels.php');

        $payload = [
            'channel_name' => 'private-nguoi-dung.'.$this->nguoiNhan->id,
            'socket_id' => '123.456',
        ];

        Sanctum::actingAs($this->nguoiNhan);
        $this->postJson('/api/broadcasting/auth', $payload)
            ->assertOk()
            ->assertJsonStructure(['auth']);

        Sanctum::actingAs($this->nguoiKhac);
        $this->postJson('/api/broadcasting/auth', $payload)
            ->assertForbidden();
    }

    private function taoTaiKhoan(string $ma): User
    {
        return User::create([
            'ho_ten' => 'Tài khoản '.$ma,
            'email' => $ma.'@portal.test',
            'mat_khau' => 'password',
            'vai_tro' => 'sinh_vien',
            'trang_thai' => 'hoat_dong',
        ]);
    }

    private function taoThongBao(User $taiKhoan, string $tieuDe): ThongBao
    {
        return ThongBao::create([
            'ma_tai_khoan' => $taiKhoan->id,
            'loai' => 'he_thong',
            'tieu_de' => $tieuDe,
            'noi_dung' => 'Nội dung kiểm thử.',
        ]);
    }
}
