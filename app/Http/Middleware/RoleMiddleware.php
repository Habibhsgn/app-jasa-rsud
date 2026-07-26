<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $userRoleCode = $request->user()->role?->code;

        // Cek apakah role user yang login ada di dalam daftar role yang diizinkan
        if (!$userRoleCode || !in_array($userRoleCode, $roles)) {
            // Jika tidak punya akses, tampilkan error 403 Forbidden
            abort(403, 'Akses Ditolak. Anda tidak memiliki izin untuk membuka halaman ini.');
        }

        return $next($request);
    }
}
