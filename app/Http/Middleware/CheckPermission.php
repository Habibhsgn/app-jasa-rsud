<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Contoh pemakaian di route:
     *   Route::middleware(['permission:jasa.index'])->group(...)
     *   Route::middleware(['permission:jasa.index,jasa.storeTotal'])->group(...) // OR
     *
     * Pengecekan lewat $user->hasPermission() supaya role permission DAN
     * user_permission_override (grant/revoke per-user) sama-sama ikut dihitung.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            abort(403, 'Anda tidak memiliki role yang valid.');
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
