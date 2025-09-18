<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        // Register our RouteServiceProvider to define rate limiters
        App\Providers\RouteServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'seller' => \App\Http\Middleware\SellerMiddleware::class,
            'sanctum.seller' => \App\Http\Middleware\SanctumSellerMiddleware::class,
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'api.cors' => \App\Http\Middleware\CorsMiddleware::class,
            'api.version' => \App\Http\Middleware\ApiVersionMiddleware::class,
            'api.error' => \App\Http\Middleware\ApiErrorHandlerMiddleware::class,
            'api.validate' => \App\Http\Middleware\RequestValidationMiddleware::class,
        ]);

        // Global API middleware
        $middleware->group('api', [
            'api.cors',
            'api.version',
            'api.error',
            // Temporarily disable api.validate middleware globally
            // We'll handle validation in the controllers directly for now
            // 'api.validate',
            'throttle:api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
