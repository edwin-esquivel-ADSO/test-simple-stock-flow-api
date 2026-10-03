<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use App\Domain\Exception\BusinessRuleViolation;
use App\Application\Exception\ConcurrencyConflict;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (ValidationException $e, $request) {
            return response()->json([
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Error de validación sintáctica en los datos enviados.',
                'errors' => $e->errors(),
            ], 400, ['Content-Type' => 'application/json']);
        });

        $this->renderable(function (BusinessRuleViolation $e, $request) {
            return response()->json([
                'title' => 'Unprocessable Entity',
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422, ['Content-Type' => 'application/problem+json']);
        });

        $this->renderable(function (ConcurrencyConflict $e, $request) {
            return response()->json([
                'title' => 'Conflict',
                'status' => 409,
                'detail' => $e->getMessage(),
            ], 409, ['Content-Type' => 'application/problem+json']);
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            return response('', 404, ['Content-Length' => '0']);
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            $headers = $e->getHeaders();
            $allow = $headers['Allow'] ?? 'POST, GET';
            return response('', 405, [
                'Content-Length' => '0',
                'Allow' => $allow,
            ]);
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($e instanceof HttpExceptionInterface) {
                $code = $e->getStatusCode();
                if (in_array($code, [401, 403, 404, 405], true)) {
                    return response('', $code, array_merge(['Content-Length' => '0'], $e->getHeaders()));
                }
            }

            return response()->json([
                'title' => 'Internal Server Error',
                'status' => 500,
                'detail' => 'Ha ocurrido un error interno en el servidor.',
            ], 500, ['Content-Type' => 'application/problem+json']);
        });
    }
}
