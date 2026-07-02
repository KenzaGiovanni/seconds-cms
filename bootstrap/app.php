<?php

use App\Http\Middleware\EnsureEcommerceEnabled;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        $middleware->alias([
            'staff' => EnsureStaff::class,
            'ecommerce' => EnsureEcommerceEnabled::class,
        ]);

        // Xendit/KiriminAja post webhooks without a CSRF token; verified
        // instead via a shared-secret token header (Xendit/KiriminAjaWebhookController).
        $middleware->validateCsrfTokens(except: [
            'webhooks/xendit',
            'webhooks/kiriminaja',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
