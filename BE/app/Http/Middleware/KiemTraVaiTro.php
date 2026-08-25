<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KiemTraVaiTro
{
    public function handle(Request $request, Closure $next, string ...$vaiTros): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Chưa đăng nhập.'], 401);
        }

        if ($user->trang_thai !== 'hoat_dong') {
            return response()->json(['message' => 'Tài khoản đã bị khóa.'], 403);
        }

        if (! in_array($user->vai_tro, $vaiTros, true)) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        }

        return $next($request);
    }
}
