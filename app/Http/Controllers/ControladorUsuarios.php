<?php

namespace App\Http\Controllers;

use App\Acciones\Usuarios\GestionarUsuarios;
use App\Http\Requests\SolicitudUsuario;
use App\Models\User;
use App\Soporte\Permisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ControladorUsuarios extends Controller
{
    public function index(): View
    {
        $usuarios = User::orderByDesc('activo')->orderBy('name')->paginate(15);

        return view('usuarios.indice', compact('usuarios'));
    }

    public function create(): View
    {
        return view('usuarios.formulario', ['usuario' => new User(['name' => '', 'email' => '']), 'roles' => Permisos::ROLES]);
    }

    public function store(SolicitudUsuario $solicitud, GestionarUsuarios $gestion): RedirectResponse
    {
        $usuario = $gestion->crear($solicitud->validated(), $solicitud->user());

        return redirect()->route('usuarios.index')->with('estado', 'Cuenta de '.$usuario->name.' creada.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.formulario', ['usuario' => $usuario, 'roles' => Permisos::ROLES]);
    }

    public function update(SolicitudUsuario $solicitud, User $usuario, GestionarUsuarios $gestion): RedirectResponse
    {
        $gestion->actualizar($usuario, $solicitud->validated(), $solicitud->user());

        return redirect()->route('usuarios.index')->with('estado', 'Datos de '.$usuario->name.' actualizados.');
    }

    public function estado(Request $solicitud, User $usuario, GestionarUsuarios $gestion): RedirectResponse
    {
        $datos = $solicitud->validate(['activo' => ['required', 'boolean']]);
        $usuario = $gestion->cambiarEstado($usuario, (bool) $datos['activo'], $solicitud->user());

        return redirect()->route('usuarios.index')->with('estado', 'La cuenta de '.$usuario->name.' quedó '.($usuario->activo ? 'activa.' : 'desactivada. Sus sesiones abiertas se cerraron.'));
    }

    public function contrasena(Request $solicitud, User $usuario, GestionarUsuarios $gestion): RedirectResponse
    {
        if ($usuario->is($solicitud->user())) {
            return redirect()->route('cuenta.contrasena')->withErrors(['password' => 'Para tu propia cuenta usa “Mi contraseña”, que solicita la contraseña actual.']);
        }
        $datos = $solicitud->validate(
            ['password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()], 'contrasena_responsable' => ['required', 'current_password']],
            ['contrasena_responsable.current_password' => 'Tu contraseña de administrador no es correcta.'],
            ['password' => 'contraseña nueva', 'contrasena_responsable' => 'tu contraseña']
        );
        $gestion->establecerContrasena($usuario, $datos['password']);

        return redirect()->route('usuarios.index')->with('estado', 'Contraseña de '.$usuario->name.' restablecida. Sus sesiones abiertas se cerraron.');
    }
}
