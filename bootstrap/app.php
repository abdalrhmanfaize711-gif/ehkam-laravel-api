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

    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------------------------------
        | Hijri Middleware
        |--------------------------------------------------------------------------
        */

        $middleware->append(
            \App\Http\Middleware\ConvertHijriRequestMiddleware::class
        );

        $middleware->append(
            \App\Http\Middleware\ConvertHijriResponseMiddleware::class
        );

        /*
        |--------------------------------------------------------------------------
        | API Middleware
        |--------------------------------------------------------------------------
        */

        $middleware->api(
            append: [
                'throttle:api',
                \App\Http\Middleware\SecurityHeaders::class,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Custom Middleware Aliases
        |--------------------------------------------------------------------------
        */

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'student.scope' => \App\Http\Middleware\StudentScopeMiddleware::class,
            'teacher.scope' => \App\Http\Middleware\TeacherScopeMiddleware::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Prevent API Authentication Redirect
        |--------------------------------------------------------------------------
        |
        | API requests must return JSON 401 instead of redirecting to
        | a "login" route.
        |
        */

        $middleware->redirectGuestsTo(function ($request) {

            if ($request->is('api/*')) {
                return null;
            }

            return route('login');
        });
    })

    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        |--------------------------------------------------------------------------
        | Return JSON for API Requests
        |--------------------------------------------------------------------------
        */

        $exceptions->shouldRenderJsonWhen(function (
            \Illuminate\Http\Request $request,
            \Throwable $e
        ) {
            return $request->is('api/*') || $request->expectsJson();
        });

        /*
        |--------------------------------------------------------------------------
        | Unauthenticated API Response
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Illuminate\Auth\AuthenticationException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Production Internal Server Error
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Throwable $e,
            \Illuminate\Http\Request $request
        ) {
            if (
                $request->is('api/*')
                && app()->environment('production')
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Internal server error.',
                ], 500);
            }
        });
    })

    ->create();