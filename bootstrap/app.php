<?php

use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Render termina HTTPS en su proxy; Laravel debe reconocer ese protocolo.
        if (env('RENDER') === true || env('RENDER') === 'true') {
            $middleware->trustProxies(at: '*');
        }
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('panel'));
        // Cierra sesiones de cuentas desactivadas o cuya contraseña cambió en otro dispositivo.
        $middleware->web(append: [
            AuthenticateSession::class,
            VerificarUsuarioActivo::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
