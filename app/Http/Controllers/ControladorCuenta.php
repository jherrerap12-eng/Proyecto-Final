<?php

namespace App\Http\Controllers;

use App\Acciones\Usuarios\GestionarUsuarios;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ControladorCuenta extends Controller
{
    public function contrasena(): View
    {
        return view('cuenta.contrasena');
    }

    public function actualizarContrasena(Request $solicitud, GestionarUsuarios $gestion): RedirectResponse
    {
        $datos = $solicitud->validate([
            'contrasena_actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:contrasena_actual', Password::min(12)->letters()->numbers()],
        ], [
            'contrasena_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.different' => 'La contraseña nueva debe ser distinta de la actual.',
        ], ['contrasena_actual' => 'contraseña actual', 'password' => 'contraseña nueva']);

        $gestion->cambiarPropia($solicitud->user(), $datos['password'], $solicitud->session()->getId());
        $solicitud->session()->regenerate();

        return redirect()->route('panel')->with('estado', 'Contraseña actualizada. Se cerraron tus sesiones en otros dispositivos.');
    }
}
