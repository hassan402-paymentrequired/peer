<?php

namespace App\Exceptions;

use App\Utils\Traits\ResponseTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ResponseTrait;

    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // Handle API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        // Handle web requests with Inertia
        if ($this->isHttpException($e)) {
            $status = $e->getStatusCode();

            if (in_array($status, [403, 404, 500])) {
                return Inertia::render("Errors/{$status}")
                    ->toResponse($request)
                    ->setStatusCode($status);
            }
        }

        return parent::render($request, $e);
    }

    protected function handleApiException($request, Throwable $e): JsonResponse
    {
        $exceptionClass = get_class($e);
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = "Something went wrong internally. Kindly try again later";
        $errors = null;

        switch ($exceptionClass) {
            case AuthenticationException::class:
                $status = Response::HTTP_UNAUTHORIZED;
                $message = 'Unauthenticated';
                break;
                
            case AuthorizationException::class:
            case AccessDeniedHttpException::class:
                $status = Response::HTTP_FORBIDDEN;
                $message = !empty($e->getMessage()) ? $e->getMessage() : 'Forbidden';
                break;
                
            case MethodNotAllowedHttpException::class:
                $status = Response::HTTP_METHOD_NOT_ALLOWED;
                $message = 'Method not allowed';
                break;
                
            case NotFoundHttpException::class:
            case ModelNotFoundException::class:
                $status = Response::HTTP_NOT_FOUND;
                $message = 'The requested resource was not found';
                break;
                
            case ValidationException::class:
                $status = Response::HTTP_UNPROCESSABLE_ENTITY;
                $message = 'Validation failed';
                $errors = collect($e->validator->getMessageBag()->toArray())
                    ->map(fn($messages) => $messages[0])
                    ->toArray();
                break;
                
            case ThrottleRequestsException::class:
                $status = Response::HTTP_TOO_MANY_REQUESTS;
                $message = 'Too many requests';
                break;
                
            case QueryException::class:
                $status = Response::HTTP_INTERNAL_SERVER_ERROR;
                $message = 'Database error occurred';
                Log::error('Database error: ' . $e->getMessage());
                break;
        }

        Log::error('API Exception: ' . $e->getMessage(), [
            'exception' => $exceptionClass,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => $request->fullUrl(),
        ]);

        return $this->responseWithError(
            message: $message, 
            error: $errors ?? $message, 
            code: $status
        );
    }
}