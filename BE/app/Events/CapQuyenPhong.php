<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CapQuyenPhong implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $maPhong,
        public int $maTaiKhoan,
        public string $hoTen,
        public bool $duocPhepMac,
        public bool $duocPhepChiaSe,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('phong.'.$this->maPhong)];
    }

    public function broadcastAs(): string
    {
        return 'cap.quyen.phong';
    }

    public function broadcastWith(): array
    {
        return [
            'ma_tai_khoan' => $this->maTaiKhoan,
            'ho_ten' => $this->hoTen,
            'duoc_phep_mac' => $this->duocPhepMac,
            'duoc_phep_chia_se' => $this->duocPhepChiaSe,
        ];
    }
}
