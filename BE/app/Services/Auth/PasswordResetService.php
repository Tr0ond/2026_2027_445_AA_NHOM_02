<?php

namespace App\Services\Auth;

use App\Jobs\ProcessPasswordResetRequest;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use App\Support\EmailCanonicalizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

class PasswordResetService
{
    public function __construct(private EmailCanonicalizer $emailCanonicalizer) {}

    /** HTTP chỉ chuẩn hóa dữ liệu và xếp job để luôn trả phản hồi trung lập. */
    public function yeuCau(string $email): void
    {
        try {
            ProcessPasswordResetRequest::dispatch(
                $this->emailCanonicalizer->chuanHoa($email),
                (string) Str::uuid(),
            );
        } catch (Throwable $exception) {
            Log::warning('Không thể xếp yêu cầu đặt lại mật khẩu.', [
                'loai_loi' => $exception::class,
            ]);

            throw new ServiceUnavailableHttpException(
                null,
                'Hệ thống chưa thể tiếp nhận yêu cầu đặt lại mật khẩu. Vui lòng thử lại sau.',
            );
        }
    }

    /** Lookup, tạo token và gửi mail chỉ diễn ra trong queue worker. */
    public function xuLyYeuCauHangDoi(string $email, string $correlationId): void
    {
        $taiKhoan = User::query()->where('email', $email)->first();
        if ($taiKhoan === null) {
            return;
        }

        $token = Password::broker()->createToken($taiKhoan);
        $expiresAt = now()->addMinutes((int) config('auth.passwords.users.expire', 60));

        try {
            $taiKhoan->notify(new PasswordResetNotification($token, $email, $expiresAt));
        } catch (Throwable $exception) {
            Log::warning('Không thể gửi email đặt lại mật khẩu.', [
                'tai_khoan_id' => $taiKhoan->getKey(),
                'correlation_id' => $correlationId,
                'loai_loi' => $exception::class,
            ]);
        }
    }

    /** Xác thực reset token, đổi mật khẩu và thu hồi toàn bộ phiên đăng nhập. */
    public function datLai(string $email, string $token, string $matKhauMoi): void
    {
        $status = Password::broker()->reset([
            'email' => $this->emailCanonicalizer->chuanHoa($email),
            'token' => $token,
            'password' => $matKhauMoi,
            'password_confirmation' => $matKhauMoi,
        ], function (User $taiKhoan, string $password): void {
            $taiKhoan->forceFill(['mat_khau' => $password])->save();
            $taiKhoan->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => 'Liên kết đã hết hạn hoặc không hợp lệ. Vui lòng yêu cầu liên kết mới.',
            ]);
        }
    }
}
