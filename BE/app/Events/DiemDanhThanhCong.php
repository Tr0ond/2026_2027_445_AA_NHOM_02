<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiemDanhThanhCong implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $maPhong,
        public int $maSinhVien,
        public string $maSinhVienText,
        public string $hoTen,
        public string $thoiGianDiemDanh,
        public bool $choChinhMinh = false,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('phong.'.$this->maPhong)];
    }

    public function broadcastAs(): string
    {
        return 'diem.danh.thanh.cong';
    }

    public function broadcastWith(): array
    {
        return [
            'ma_sinh_vien' => $this->maSinhVien,
            'ma_sinh_vien_text' => $this->maSinhVienText,
            'ho_ten' => $this->hoTen,
            'thoi_gian_diem_danh' => $this->thoiGianDiemDanh,
        ];
    }
}
