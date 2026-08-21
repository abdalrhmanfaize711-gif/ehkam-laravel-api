<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Routing;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
       
    })
    ->withMiddleware(function ($middleware) {

    $middleware->append(\App\Http\Middleware\ConvertHijriRequestMiddleware::class);

    $middleware->append(\App\Http\Middleware\ConvertHijriResponseMiddleware::class);

})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
