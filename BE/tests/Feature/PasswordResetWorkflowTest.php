<?php

namespace Tests\Feature;

use App\Jobs\ProcessPasswordResetRequest;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use App\Services\Auth\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PasswordResetWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_yeu_cau_reset_chuan_hoa_email_va_xep_job(): void
    {
        Queue::fake();

        $this->postJson('/api/quen-mat-khau', ['email' => '  USER@PORTAL.TEST  '])
            ->assertOk()
            ->assertJsonPath('message', 'Nếu email tồn tại, liên kết đặt lại mật khẩu đã được gửi.');

        Queue::assertPushed(ProcessPasswordResetRequest::class, fn ($job) => $job->email === 'user@portal.test');
    }

    public function test_worker_tao_token_va_gui_notification_tuy_bien(): void
    {
        Notification::fake();
        $taiKhoan = $this->taoTaiKhoan();

        app(PasswordResetService::class)->xuLyYeuCauHangDoi($taiKhoan->email, 'test-correlation-id');

        Notification::assertSentTo(
            $taiKhoan,
            PasswordResetNotification::class,
            fn ($notification) => $notification->email === $taiKhoan->email
                && str_contains($notification->toMail($taiKhoan)->actionUrl, 'dat-lai-mat-khau?token=')
                && str_contains($notification->toMail($taiKhoan)->actionUrl, 'email=user%40portal.test'),
        );
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $taiKhoan->email]);
    }

    public function test_reset_doi_mat_khau_va_thu_hoi_token_dang_nhap(): void
    {
        $taiKhoan = $this->taoTaiKhoan();
        $taiKhoan->createToken('test-token');
        $resetToken = Password::broker()->createToken($taiKhoan);

        $this->postJson('/api/dat-lai-mat-khau', [
            'email' => ' USER@PORTAL.TEST ',
            'token' => $resetToken,
            'password' => 'mat-khau-moi',
            'password_confirmation' => 'mat-khau-moi',
        ])->assertOk();

        $taiKhoan->refresh();
        $this->assertTrue(Hash::check('mat-khau-moi', $taiKhoan->mat_khau));
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $taiKhoan->id]);
    }

    private function taoTaiKhoan(): User
    {
        return User::create([
            'ho_ten' => 'Người dùng kiểm thử',
            'email' => 'user@portal.test',
            'mat_khau' => 'password',
            'vai_tro' => 'sinh_vien',
            'trang_thai' => 'hoat_dong',
        ]);
    }
}
