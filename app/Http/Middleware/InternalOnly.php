<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InternalOnly
{
    public function handle(Request $request, Closure $next)
    {
        // Must be an AJAX request from our site
        $xrw = $request->header('X-Requested-With');
        if (strtolower((string) $xrw) !== 'xmlhttprequest') {
            abort(403, 'Forbidden');
        }

        $allowedHosts = array_filter([
            parse_url(config('app.url'), PHP_URL_HOST),
            $request->getHost(),
        ]);

        $origin = (string) $request->headers->get('origin', '');
        $referer = (string) $request->headers->get('referer', '');
        $originHost = $origin ? parse_url($origin, PHP_URL_HOST) : null;
        $refererHost = $referer ? parse_url($referer, PHP_URL_HOST) : null;

        $hostOk = false;
        if ($originHost && in_array($originHost, $allowedHosts, true)) {
            $hostOk = true;
        } elseif ($refererHost && in_array($refererHost, $allowedHosts, true)) {
            $hostOk = true;
        }

        if (! $hostOk) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}

