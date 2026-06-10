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
        then: function () {
            require __DIR__.'/../routes/admin.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\MergeChatCorsOrigins::class);

        $middleware->alias([
            'tenant.origin'   => \App\Http\Middleware\ValidateTenantOrigin::class,
            'tenant.api-key'  => \App\Http\Middleware\AuthenticateTenantApiKey::class,
            'chat.security'   => \App\Http\Middleware\AddChatSecurityHeaders::class,
            'dev.only'        => \App\Http\Middleware\RestrictDevelopmentRoutes::class,
            'admin'           => \App\Http\Middleware\AdminAuthenticate::class,
            'admin.guest'     => \App\Http\Middleware\RedirectIfAdminAuthenticated::class,
        ]);

        $middleware->appendToGroup('api', [
            'tenant.origin',
            'chat.security',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
