<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cho phép tải file (Excel export) qua <a href> bằng cách nhận Sanctum token
 * từ query string ?token= và gắn vào header Authorization.
 */
class ChenTokenTuQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->filled('token') && ! $request->bearerToken()) {
            $request->headers->set('Authorization', 'Bearer '.$request->query('token'));
        }

        return $next($request);
    }
}
