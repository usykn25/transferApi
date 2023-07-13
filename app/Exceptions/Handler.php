<?php

namespace App\Exceptions;

use App\Traits\ApiTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use ApiTrait;

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
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {

        if ($exception instanceof NotFoundHttpException) {
            return $this->returnWithMessage($exception->getMessage(), 1, Response::HTTP_NOT_FOUND);
        }
        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->returnWithMessage($exception->getMessage());
        }
        if ($exception instanceof ModelNotFoundException) {
            return $this->returnWithMessage($exception->getMessage());
        }

        return parent::render($request, $exception);
    }
}
