<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            // Arahkan tamu ke halaman login storefront agar tidak terjadi loop /admin/login
            return redirect()->guest(route('login'));
        }

        if ((string) ($user->type ?? '') !== 'admin') {
            abort(403, 'Only admin users may access the admin panel.');
        }

        return $next($request);
    }
}
