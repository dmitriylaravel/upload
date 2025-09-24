<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withBootstrappers([
        function ($app) {
            // Configure Laravel Cloud disks if available
            if (class_exists('Illuminate\Foundation\Cloud')) {
                \Illuminate\Foundation\Cloud::configureDisks($app);
            }
        }
    ])
    ->create();
