<?php

namespace App\Exceptions;

use App\Components\ApiResponse;
use App\Constants\ResponseConstant;
use App\Mail\ExceptionOccurred;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register() : void {
        $this->reportable(function (Throwable $e) {
            return false;
        });
    }

    /**
     * @param $request
     * @param Throwable $e
     * @return JsonResponse|RedirectResponse|Response|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $e) {
        if ($request->is('api/*')) {
            $errCode = ResponseConstant::RET_ERR;
            if ($e instanceof BaseException) {
                $errCode = $e->getRespCode();
            }
            return response()->json(ApiResponse::error($errCode, $e->getMessage()), 400);
        }
        return parent::render($request, $e);
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Emails.
     *
     * @param Throwable $e
     * @return void
     * @throws Throwable
     */
    public function report(Throwable $e) : void {
        if ($this->shouldReport($e)) {
            $this->sendEmail($e);
        }

        parent::report($e);
    }

    /**
     * Sends an email to the developer about the exception.
     *
     * @param Throwable $thrown
     * @return void
     */
    public function sendEmail(Throwable $thrown) : void {
        try {
            $html = $this->renderExceptionWithSymfony($thrown, true);
            Mail::to(config('operating.err_mail.to'))->send(new ExceptionOccurred($html));
        } catch (Throwable $ex) {
            dd($ex);
        }
    }

}
