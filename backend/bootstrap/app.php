<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureAccountType::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Force every API failure into the same envelope the success responses
        // use, so the SPA never has to branch on error shape.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            [$status, $message, $errors] = match (true) {
                $e instanceof ValidationException => [
                    422,
                    'The given data was invalid.',
                    $e->errors(),
                ],
                $e instanceof AuthenticationException => [
                    401,
                    'Unauthenticated. Please sign in to continue.',
                    null,
                ],
                $e instanceof AuthorizationException => [
                    403,
                    'You are not authorised to perform this action.',
                    null,
                ],
                $e instanceof ThrottleRequestsException => [
                    429,
                    'Too many attempts. Please slow down and try again shortly.',
                    null,
                ],
                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => [
                    404,
                    'The requested resource was not found.',
                    null,
                ],
                $e instanceof HttpExceptionInterface => [
                    $e->getStatusCode(),
                    $e->getMessage() ?: 'Request failed.',
                    null,
                ],
                default => [
                    500,
                    config('app.debug') ? $e->getMessage() : 'Something went wrong on our end.',
                    null,
                ],
            };

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => null,
                'errors' => $errors,
            ], $status);
        });
    })->create();
