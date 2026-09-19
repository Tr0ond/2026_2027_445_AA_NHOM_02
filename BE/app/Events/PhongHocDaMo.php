<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhongHocDaMo implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $maTaiKhoan,
        public int $maLichHoc,
        public array $phong,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('nguoi-dung.'.$this->maTaiKhoan)];
    }

    public function broadcastAs(): string
    {
        return 'phong.hoc.da.mo';
    }

    public function broadcastWith(): array
    {
        return [
            'ma_lich_hoc' => $this->maLichHoc,
            'phong' => $this->phong,
        ];
    }
}
