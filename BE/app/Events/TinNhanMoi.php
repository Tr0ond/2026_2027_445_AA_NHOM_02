<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TinNhanMoi implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $maPhong,
        public int $maTaiKhoan,
        public string $hoTen,
        public string $vaiTro,
        public string $noiDung,
        public string $thoiGianGui,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('phong.'.$this->maPhong)];
    }

    public function broadcastAs(): string
    {
        return 'tin.nhan.moi';
    }

    public function broadcastWith(): array
    {
        return [
            'ma_tai_khoan' => $this->maTaiKhoan,
            'ho_ten' => $this->hoTen,
            'vai_tro' => $this->vaiTro,
            'noi_dung' => $this->noiDung,
            'thoi_gian_gui' => $this->thoiGianGui,
        ];
    }
}
