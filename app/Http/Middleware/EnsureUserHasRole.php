<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi endpoint hanya untuk role tertentu.
 * Pemakaian di routes: ->middleware('role:super_admin,wilayah,agen')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'Kamu tidak punya akses untuk melakukan aksi ini.',
            ], 403);
        }

        return $next($request);
    }
}
