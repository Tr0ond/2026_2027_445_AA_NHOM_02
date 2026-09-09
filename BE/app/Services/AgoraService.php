<?php

namespace App\Services;

use Peterujah\Agora\Agora;
use Peterujah\Agora\Builders\RtcToken;
use Peterujah\Agora\Roles;
use Peterujah\Agora\User;

class AgoraService
{
    /**
     * Thông tin kết nối Agora Web SDK cho một thành viên phòng học.
     * Kênh (channel) = ma_phong. UID = id tài khoản (số nguyên).
     * Token chỉ được sinh khi có App Certificate; project tạo ở chế độ
     * "App ID only" (Testing mode) thì token = null, SDK join bằng App ID.
     */
    public function thongTinThamGia(string $kenh, int $uid): array
    {
        $appId = (string) config('services.agora.app_id');

        if ($appId === '') {
            return [
                'app_id' => null,
                'kenh' => $kenh,
                'token' => null,
                'uid' => $uid,
                'canh_bao' => 'Chưa cấu hình AGORA_APP_ID trong BE/.env — video Agora chưa hoạt động.',
            ];
        }

        $token = null;
        $certificate = (string) config('services.agora.certificate');

        // Trả lỗi cấu hình rõ ràng, không để thông báo từ SDK lộ certificate.
        abort_unless(preg_match('/^[a-f0-9]{32}$/i', $appId), 503, 'AGORA_APP_ID chưa đúng định dạng.');
        abort_if($certificate !== '' && ! preg_match('/^[a-f0-9]{32}$/i', $certificate), 503, 'AGORA_APP_CERTIFICATE chưa đúng định dạng.');

        if ($certificate !== '') {
            // AccessToken2 dùng thời lượng tính bằng giây, không dùng Unix timestamp.
            $thoiLuong = 4 * 60 * 60;
            $client = new Agora($appId, $certificate);
            $client->setExpiration($thoiLuong);

            $nguoiDung = (new User($uid))
                ->setPrivilegeExpire($thoiLuong)
                ->setChannel($kenh)
                ->setRole(Roles::RTC_PUBLISHER);

            $token = RtcToken::buildTokenWithUid($client, $nguoiDung);
        }

        return [
            'app_id' => $appId,
            'kenh' => $kenh,
            'token' => $token,
            'uid' => $uid,
        ];
    }
}
