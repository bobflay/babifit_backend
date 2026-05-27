<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        // API-only app: never redirect unauthenticated requests to a "login"
        // route (there isn't one) — let them surface as a JSON 401.
        $middleware->redirectGuestsTo(static fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Uniform error envelope: { "error": { code, message, details } }
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('v1/*') || $request->expectsJson()
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('v1/*') || $request->expectsJson())) {
                return null;
            }

            [$status, $code, $message, $details] = match (true) {
                $e instanceof ValidationException => [
                    422, 'validation_error', 'The given data was invalid.', $e->errors(),
                ],
                $e instanceof AuthenticationException => [
                    401, 'unauthenticated', 'Unauthenticated.', (object) [],
                ],
                $e instanceof AuthorizationException => [
                    403, 'forbidden', $e->getMessage() ?: 'This action is unauthorized.', (object) [],
                ],
                $e instanceof ModelNotFoundException => [
                    404, 'not_found', 'Resource not found.', (object) [],
                ],
                $e instanceof HttpExceptionInterface => [
                    $e->getStatusCode(),
                    match ($e->getStatusCode()) {
                        404 => 'not_found',
                        405 => 'method_not_allowed',
                        403 => 'forbidden',
                        429 => 'too_many_requests',
                        default => 'http_error',
                    },
                    $e->getMessage() ?: 'Request failed.',
                    (object) [],
                ],
                default => [
                    500,
                    'server_error',
                    config('app.debug') ? $e->getMessage() : 'Something went wrong.',
                    (object) [],
                ],
            };

            return response()->json([
                'error' => [
                    'code' => $code,
                    'message' => $message,
                    'details' => $details,
                ],
            ], $status);
        });
    })->create();
