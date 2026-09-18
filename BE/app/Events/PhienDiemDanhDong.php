<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhienDiemDanhDong implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $maPhong, public string $maPhien) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('phong.'.$this->maPhong)];
    }

    public function broadcastAs(): string
    {
        return 'phien.diem.danh.dong';
    }

    public function broadcastWith(): array
    {
        return ['ma_phien' => $this->maPhien];
    }
}
