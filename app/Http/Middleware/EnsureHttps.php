<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            config('app.force_https')
            && ! $request->secure()
            && ! $request->is('up')
        ) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        if (config('app.force_https') && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        return $response;
    }
}
