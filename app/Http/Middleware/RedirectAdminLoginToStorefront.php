<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminLoginToStorefront
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin/login') && $request->method() === 'GET' && ! $request->user()) {
            // Setelah logout dari admin, arahkan ke beranda storefront
            return redirect()->to(route('home'));
        }

        return $next($request);
    }
}
