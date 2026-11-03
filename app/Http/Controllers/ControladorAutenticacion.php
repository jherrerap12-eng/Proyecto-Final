<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ControladorAutenticacion extends Controller
{
    public function mostrar(): View
    {
        return view('autenticacion.ingresar');
    }

    public function ingresar(Request $solicitud): RedirectResponse
    {
        $credenciales = $solicitud->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $credenciales['email'] = Str::lower(trim($credenciales['email']));
        $claveIntentos = 'ingreso:'.hash('sha256', $credenciales['email'].'|'.$solicitud->ip());

        if (RateLimiter::tooManyAttempts($claveIntentos, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Intenta de nuevo en '.RateLimiter::availableIn($claveIntentos).' segundos.',
            ]);
        }

        // Una cuenta desactivada no puede ingresar; el mensaje no revela si existe.
        if (! Auth::attempt([...$credenciales, 'activo' => true])) {
            RateLimiter::hit($claveIntentos, 60);

            throw ValidationException::withMessages([
                'email' => 'El correo o la contraseña son incorrectos.',
            ]);
        }

        RateLimiter::clear($claveIntentos);
        $solicitud->session()->regenerate();

        return redirect()->intended(route('panel'));
    }

    public function salir(Request $solicitud): RedirectResponse
    {
        Auth::logout();
        $solicitud->session()->invalidate();
        $solicitud->session()->regenerateToken();

        return redirect()->route('login')->with('estado', 'Cerraste sesión correctamente.');
    }
}
