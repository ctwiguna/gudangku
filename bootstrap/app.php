<?php

use App\Http\Middleware\EnsureManager;
use App\Http\Middleware\EnsureOwner;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'owner' => EnsureOwner::class,
            'manager' => EnsureManager::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Halaman 403 dan 404 dirender lewat React, bukan halaman bawaan framework.
        $exceptions->respond(function (SymfonyResponse $response, \Throwable $exception): SymfonyResponse {
            $status = $response->getStatusCode();
            $request = request();

            if (! in_array($status, [403, 404], true) || $request->expectsJson() || $request->is('api/*')) {
                return $response;
            }

            $page = $status === 403 ? 'Errors/403' : 'Errors/404';

            return Inertia::render($page, ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
