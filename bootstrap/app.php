<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix(config('custom.routes.prefix'))
                ->group(base_path('routes/custom/travel.php'));

            Route::middleware('api')
                ->prefix(prefix: config('custom.routes.prefix'))
                ->group(base_path('routes/custom/admin.php'));

            Route::middleware('api')
                ->prefix(config('custom.routes.prefix'))
                ->group(base_path('routes/custom/login.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
