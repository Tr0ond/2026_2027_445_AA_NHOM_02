<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhongHocKetThuc implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $maPhong, public int $maLichHoc) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('phong.'.$this->maPhong)];
    }

    public function broadcastAs(): string
    {
        return 'phong.ket.thuc';
    }

    public function broadcastWith(): array
    {
        return ['ma_phong' => $this->maPhong, 'ma_lich_hoc' => $this->maLichHoc, 'trang_thai' => 'da_ket_thuc'];
    }
}
