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
            Log::channel('xxljob')->info('token验证失败！' . $token);
            return XxlResponse::fail('token验证失败！');
        }
        return $next($request);
    }
}
