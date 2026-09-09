<?php

namespace Tests\Feature;

use App\Services\AgoraService;
use Peterujah\Agora\Util;
use Tests\TestCase;

class AgoraServiceTest extends TestCase
{
    public function test_thieu_app_id_tra_canh_bao_khong_gia_lap_video_da_chay(): void
    {
        config(['services.agora.app_id' => '', 'services.agora.certificate' => '']);
        $data = app(AgoraService::class)->thongTinThamGia('PHDEMO', 10);
        $this->assertNull($data['app_id']);
        $this->assertNull($data['token']);
        $this->assertNotEmpty($data['canh_bao']);
    }

    public function test_che_do_app_id_only_khong_sinh_token(): void
    {
        config(['services.agora.app_id' => str_repeat('a', 32), 'services.agora.certificate' => '']);
        $data = app(AgoraService::class)->thongTinThamGia('PHDEMO', 10);
        $this->assertSame(str_repeat('a', 32), $data['app_id']);
        $this->assertNull($data['token']);
        $this->assertSame(10, $data['uid']);
    }

    public function test_access_token_2_dung_channel_uid_thoi_luong_va_chu_ky(): void
    {
        // Giá trị giả cho kiểm thử; không dùng credential của nhóm và không gọi Agora.
        $appId = str_repeat('a', 32);
        $certificate = str_repeat('b', 32);
        config(['services.agora.app_id' => $appId, 'services.agora.certificate' => $certificate]);
        $data = app(AgoraService::class)->thongTinThamGia('PHDEMO', 10);
        $this->assertStringStartsWith('007', $data['token']);
        $binary = zlib_decode(base64_decode(substr($data['token'], 3), true));
        $signature = Util::unpackString($binary);
        $signedData = $binary;
        $this->assertSame($appId, Util::unpackString($binary));
        $issueTs = Util::unpackUint32($binary);
        $this->assertLessThanOrEqual(5, abs(time() - $issueTs));
        $this->assertSame(14400, Util::unpackUint32($binary));
        $salt = Util::unpackUint32($binary);
        $this->assertSame(1, Util::unpackUint16($binary));
        $this->assertSame(1, Util::unpackUint16($binary));
        $privileges = Util::unpackMapUint32($binary);
        $this->assertCount(4, $privileges);
        foreach ($privileges as $duration) {
            $this->assertSame(14400, $duration);
        }
        $this->assertSame('PHDEMO', Util::unpackString($binary));
        $this->assertSame('10', Util::unpackString($binary));
        $signing = hash_hmac('sha256', $certificate, pack('V', $issueTs), true);
        $signing = hash_hmac('sha256', $signing, pack('V', $salt), true);
        $this->assertSame(hash_hmac('sha256', $signedData, $signing, true), $signature);
        $this->assertArrayNotHasKey('certificate', $data);
    }
}
