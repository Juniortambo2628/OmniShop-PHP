<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CatalogAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $event_slug = $request->route('event_slug');
        if (!$event_slug) return $next($request);

        if (!session("catalog_auth_{$event_slug}")) {
            return redirect()->route('catalog.login', $event_slug);
        }

        return $next($request);
    }
}
