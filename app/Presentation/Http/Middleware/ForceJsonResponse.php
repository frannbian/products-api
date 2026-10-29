<?php

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes every API request expect JSON, so errors are never rendered as HTML
 * or redirects regardless of the headers the client sends.
 */
class ForceJsonResponse
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
