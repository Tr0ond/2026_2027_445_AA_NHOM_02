<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaQrDiemDanhCapNhat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $maPhong,
        public string $maPhien,
        public string $duongDanQr,
        public string $qrHetHanLuc,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('phong.'.$this->maPhong)];
    }

    public function broadcastAs(): string
    {
        return 'ma.qr.diem.danh.cap.nhat';
    }

    public function broadcastWith(): array
    {
        return [
            'ma_phien' => $this->maPhien,
            'duong_dan_qr' => $this->duongDanQr,
            'qr_het_han_luc' => $this->qrHetHanLuc,
        ];
    }
}
