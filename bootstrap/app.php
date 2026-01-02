<?php

use App\Components\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Paganini\Constants\ResponseConstant;
use Paganini\Exceptions\BaseException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            // 判断是否是 API 请求
            $isApiRequest = (
                str_contains($request->getRequestUri(), '/api/') ||
                str_contains($request->getPathInfo(), '/api/') ||
                str_starts_with($request->path(), 'api/') ||
                $request->expectsJson() ||
                $request->wantsJson() ||
                $request->isJson() ||
                $request->ajax() ||
                $request->hasHeader('Authorization')
            );

            if (!$isApiRequest) {
                return null; // 返回 null 让默认处理器处理非 API 请求
            }

            $errCode = ResponseConstant::RET_ERR;
            $statusCode = 500;
            $message = $e->getMessage();

            // 处理自定义 BaseException
            if ($e instanceof BaseException) {
                $errCode = $e->getRespCode();
            } elseif ($e instanceof HttpException) {
                // 处理 HTTP 异常（404, 403 等）
                $statusCode = $e->getStatusCode();
                $message = $e->getMessage() ?: match ($statusCode) {
                    404 => 'Resource not found',
                    403 => 'Forbidden',
                    401 => 'Unauthorized',
                    405 => 'Method not allowed',
                    422 => 'Validation error',
                    429 => 'Too many requests',
                    500 => 'Internal server error',
                    503 => 'Service unavailable',
                    default => 'An error occurred',
                };
            }

            return response()->json(ApiResponse::error($errCode, $message), $statusCode);
        });
    })->create();
