<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ThongBaoMoi implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $maTaiKhoan,
        public array $thongBao,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('nguoi-dung.'.$this->maTaiKhoan)];
    }

    public function broadcastAs(): string
    {
        return 'thong.bao.moi';
    }

    public function broadcastWith(): array
    {
        return ['thong_bao' => $this->thongBao];
    }
}
