<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SinhVienGioTay implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $maPhong,
        public int $maTaiKhoan,
        public string $hoTen,
        public bool $dangGio,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('phong.'.$this->maPhong)];
    }

    public function broadcastAs(): string
    {
        return 'sinh.vien.gio.tay';
    }

    public function broadcastWith(): array
    {
        return [
            'ma_tai_khoan' => $this->maTaiKhoan,
            'ho_ten' => $this->hoTen,
            'dang_gio' => $this->dangGio,
        ];
    }
}
