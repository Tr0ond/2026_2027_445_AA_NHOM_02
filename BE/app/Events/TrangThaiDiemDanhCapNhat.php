<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrangThaiDiemDanhCapNhat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $maPhong,
        public int $maSinhVien,
        public string $trangThai,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('phong.'.$this->maPhong)];
    }

    public function broadcastAs(): string
    {
        return 'trang.thai.diem.danh.cap.nhat';
    }

    public function broadcastWith(): array
    {
        return [
            'ma_sinh_vien' => $this->maSinhVien,
            'trang_thai_diem_danh' => $this->trangThai,
        ];
    }
}
