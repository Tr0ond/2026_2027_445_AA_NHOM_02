<?php

namespace App\Support;

use Normalizer;

class EmailCanonicalizer
{
    /** Chuẩn hóa email trước khi validation, lưu token và lookup tài khoản. */
    public function chuanHoa(string $email): string
    {
        $email = trim($email);

        if (class_exists(Normalizer::class)) {
            $email = Normalizer::normalize($email, Normalizer::FORM_C) ?: $email;
        }

        return mb_strtolower($email, 'UTF-8');
    }
}
