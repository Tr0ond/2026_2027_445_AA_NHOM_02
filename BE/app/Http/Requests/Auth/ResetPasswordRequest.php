<?php

namespace App\Http\Requests\Auth;

use App\Support\EmailCanonicalizer;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'min:8', 'max:4096', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Liên kết đặt lại mật khẩu không hợp lệ.',
            'email.required' => 'Liên kết đặt lại mật khẩu không hợp lệ.',
            'email.email' => 'Email không hợp lệ.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge([
                'email' => app(EmailCanonicalizer::class)->chuanHoa($this->input('email')),
            ]);
        }
    }
}
