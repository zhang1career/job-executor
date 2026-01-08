<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Paganini\Constants\ResponseConstant;
use Paganini\Exceptions\BaseException;
use Paganini\POJOs\Response;
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
            // Check if this is an API request
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
                return null; // Return null to let default handler process non-API requests
            }

            $errCode = ResponseConstant::RET_ERR;
            $statusCode = 500;
            $message = $e->getMessage();

            // Handle custom BaseException
            if ($e instanceof BaseException) {
                $errCode = $e->getRespCode();
            } elseif ($e instanceof HttpException) {
                // Handle HTTP exceptions (404, 403, etc.)
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

            return response()->json(Response::failWithCode($errCode, $message), $statusCode);
        });
    })->create();
