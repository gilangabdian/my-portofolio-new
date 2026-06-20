<?php

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // PERBAIKAN JEBAKAN IP (RENDER/CLOUDFLARE):
        // Mengizinkan Laravel untuk membaca IP asli dari Header X-Forwarded-For
        $middleware->trustProxies(at: '*');

        // Mendaftarkan middleware ForceJsonResponse ke grup API
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            // Hanya modifikasi response jika request mengarah ke API
            if ($request->is('api/*')) {

                // 1. Biarkan Laravel menangani Validation Error secara otomatis (Return 422)
                // Ini penting agar frontend tetap bisa membaca object 'errors' bawaan Laravel
                if ($e instanceof ValidationException) {
                    return null;
                }

                // 2. Tentukan Status Code
                $statusCode = 500;

                if ($e instanceof NotFoundHttpException) {
                    $statusCode = 404;
                    $message = 'Resource not found.';
                } elseif ($e instanceof AuthenticationException) {
                    $statusCode = 401;
                    $message = 'Unauthenticated.';
                } elseif (method_exists($e, 'getStatusCode')) {
                    $statusCode = $e->getStatusCode();
                    $message = $e->getMessage();
                } else {
                    $message = $e->getMessage();
                }

                // 3. Siapkan Response JSON yang Seragam
                $response = [
                    'success' => false,
                    'message' => $message,
                ];

                // 4. Debug Mode: Tambahkan detail error jika di Local/Development
                if (config('app.debug')) {
                    $response['debug'] = [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace(), // Menggunakan getTrace() array lebih rapi daripada string
                    ];
                }

                return response()->json($response, $statusCode);
            }
        });
    })->create();
