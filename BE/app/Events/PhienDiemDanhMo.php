<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhienDiemDanhMo implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $maPhong,
        public string $maPhien,
        public string $duongDanQr,
        public string $qrHetHanLuc,
        public string $thoiGianBatDau,
        public string $thoiGianKetThuc,
        public int $soGiay,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('phong.'.$this->maPhong)];
    }

    public function broadcastAs(): string
    {
        return 'phien.diem.danh.mo';
    }

    public function broadcastWith(): array
    {
        return [
            'ma_phien' => $this->maPhien,
            'duong_dan_qr' => $this->duongDanQr,
            'qr_het_han_luc' => $this->qrHetHanLuc,
            'thoi_gian_bat_dau' => $this->thoiGianBatDau,
            'thoi_gian_ket_thuc' => $this->thoiGianKetThuc,
            'so_giay' => $this->soGiay,
        ];
    }
}
