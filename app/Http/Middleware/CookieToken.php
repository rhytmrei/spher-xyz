<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            $cookie_name = 'auth_token';

            /*
             * If no bearer token, check for a token in cookies
             */
            if ($request->hasCookie($cookie_name)) {
                $token = $request->cookie($cookie_name);

                /*
                 * Add the token to the Authorization header
                 */
                $request->headers->add(['Authorization' => 'Bearer '.$token]);
            }
        }

        return $next($request);
    }
}
