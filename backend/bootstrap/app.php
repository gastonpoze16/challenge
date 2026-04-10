<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\JsonException as SymfonyJsonException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Laravel aplica por defecto route('login'); esta API no define esa ruta nombrada.
        // Sin esto, un GET desde el navegador a rutas auth:sanctum lanza RouteNotFoundException en lugar de 401 JSON.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $apiStyle = static function (Request $request): bool {
            return $request->is('webhooks/*', 'payments', 'payments/*', 'admin/*', 'login', 'logout', 'me');
        };

        $exceptions->renderable(function (BadRequestHttpException $e, Request $request) use ($apiStyle) {
            if (! $apiStyle($request)) {
                return null;
            }

            $previous = $e->getPrevious();

            if ($previous instanceof SymfonyJsonException) {
                return response()->json([
                    'message' => 'Invalid JSON payload.',
                    'errors' => [
                        'body' => [$previous->getMessage()],
                    ],
                ], 400);
            }

            return response()->json([
                'message' => 'Bad request.',
                'errors' => [
                    'request' => [$e->getMessage()],
                ],
            ], 400);
        });

        $exceptions->renderable(function (ValidationException $e, Request $request) use ($apiStyle) {
            if (! $apiStyle($request)) {
                return null;
            }

            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], $e->status);
        });

        $exceptions->renderable(function (TooManyRequestsHttpException $e, Request $request) use ($apiStyle) {
            if (! $apiStyle($request)) {
                return null;
            }

            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'method' => $request->method(),
                'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
            ]);

            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429)->withHeaders($e->getHeaders());
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) use ($apiStyle) {
            if (! $apiStyle($request)) {
                return null;
            }

            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        });
    })->create();
