<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarUsuarioActivo
{
    /**
     * Cierra la sesión de una cuenta desactivada en su siguiente solicitud,
     * aunque la sesión se hubiera abierto antes de la desactivación.
     */
    public function handle(Request $solicitud, Closure $siguiente): Response
    {
        $usuario = $solicitud->user();
        if ($usuario && ! $usuario->activo) {
            Auth::guard('web')->logout();
            $solicitud->session()->invalidate();
            $solicitud->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta está desactivada. Consulta con el administrador.']);
        }

        return $siguiente($solicitud);
    }
}
