<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Auth\PasswordResetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Payload hàng đợi chỉ chứa email đã chuẩn hóa, không chứa reset token. */
class ProcessPasswordResetRequest implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $email,
        public string $correlationId,
    ) {}

    public function handle(PasswordResetService $service): void
    {
        $service->xuLyYeuCauHangDoi($this->email, $this->correlationId);
    }
}
