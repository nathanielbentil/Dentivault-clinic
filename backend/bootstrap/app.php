<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\SecurityHeadersMiddleware;
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
    ->withMiddleware(function (Middleware $middleware) {
        // Trust all upstream reverse proxies (essential for Vercel SSL detection)
        $middleware->trustProxies(at: '*');

        // Global Middleware: Applied to all incoming HTTP requests
        $middleware->append(SecurityHeadersMiddleware::class);

        // Named Middleware Aliases for Route Protection
        $middleware->alias([
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
        ]);

        // Prevent 419 Page Expired errors during local demo/development testing
        $middleware->validateCsrfTokens(except: [
            'login',
            'register',
            'two-factor-challenge',
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
