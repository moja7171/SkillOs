<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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
        // Action URLs (POST/PUT/DELETE only) reached with GET — typically Back/Forward or a refresh
        // after a form submit. A 405 page helps nobody; return the learner to where they were.
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->isMethod('GET') && ! $request->expectsJson()) {
                $back = url()->previous();

                return redirect($back !== $request->fullUrl() ? $back : route('home'))
                    ->with('status', 'این عملیات فقط با دکمه انجام می‌شه؛ برگشتی به صفحه‌ی قبل.');
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
