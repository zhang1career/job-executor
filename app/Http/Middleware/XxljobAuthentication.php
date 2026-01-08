<?php

namespace App\Http\Middleware;

use App\Components\XxlResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Paganini\XxlJobExecutor\TokenAuthenticator;

class XxljobAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('XXL-JOB-ACCESS-TOKEN');
        $authenticator = new TokenAuthenticator(config('xxl.token'));

        if (!$authenticator->validate($token)) {
            Log::error('[xxljob] token validation failed: ' . $token);
            return XxlResponse::fail('Token validation failed');
        }

        return $next($request);
    }
}
