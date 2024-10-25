<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CompressResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($this->shouldCompress($request)) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            ob_start('ob_gzhandler');

            $response->header('Content-Encoding', 'gzip');
            $response->header('Vary', 'Accept-Encoding');
        }

        return $response;
    }

    private function shouldCompress(Request $request): bool
    {
        return str_contains($request->header('Accept-Encoding'), 'gzip');
    }
}
