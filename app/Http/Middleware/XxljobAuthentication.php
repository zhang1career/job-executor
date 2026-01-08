<?php

namespace App\Http\Middleware;

use App\Components\XxlResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XxljobAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('XXL-JOB-ACCESS-TOKEN');
        if (config('xxl.token') != $token) {
            Log::error('Token validation failed: ' . $token);
            return XxlResponse::fail('Token validation failed');
        }
        return $next($request);
    }
}
