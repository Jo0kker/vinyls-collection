<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SeoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add SEO-friendly headers
        $response->headers->set('X-Robots-Tag', 'index, follow');

        // Set canonical URL
        if (!$request->ajax() && !$request->expectsJson()) {
            $canonicalUrl = $request->url();
            $response->headers->set('Link', "<{$canonicalUrl}>; rel=\"canonical\"");
        }

        return $response;
    }
}
