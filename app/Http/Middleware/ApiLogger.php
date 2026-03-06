<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiLogger
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::channel('api')->info('API Request', [

            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),

            'request' => $request->all(),

            'status' => $response->status(),
            'response' => $response->getContent(),

            'time_ms' => $duration

        ]);

        return $response;
    }
}