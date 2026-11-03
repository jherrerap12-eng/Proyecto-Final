<?php

namespace App\Console\Commands;

use App\Acciones\Usuarios\GestionarUsuarios;
use App\Models\User;
use App\Soporte\Permisos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CrearUsuario extends Command
{
    protected $signature = 'usuario:crear';

    protected $description = 'Crea una cuenta interna sin guardar contraseñas en el código ni en argumentos.';

    public function handle(GestionarUsuarios $gestion): int
    {
        $nombre = trim((string) $this->ask('Nombre del usuario'));
        $correo = Str::lower(trim((string) $this->ask('Correo electrónico')));
        // La primera cuenta debe poder administrar a las demás desde la aplicación.
        $hayAdministrador = User::where('rol', 'administrador')->where('activo', true)->exists();
        $rol = $hayAdministrador
            ? (string) $this->choice('Rol', array_keys(Permisos::ROLES), 'operador')
            : 'administrador';
        if (! $hayAdministrador) {
            $this->line('No hay administradores activos: esta cuenta se creará como administrador.');
        }
        $contrasena = $this->secret('Contraseña (mínimo 12 caracteres, letras y números)');
        $confirmacion = $this->secret('Confirma la contraseña');

        $datos = [
            'name' => $nombre,
            'email' => $correo,
            'rol' => $rol,
            'password' => $contrasena,
            'password_confirmation' => $confirmacion,
        ];

        $validacion = Validator::make($datos, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'rol' => ['required', Rule::in(array_keys(Permisos::ROLES))],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        if ($validacion->fails()) {
            foreach ($validacion->errors()->all() as $mensaje) {
                $this->error($mensaje);
            }

            return self::FAILURE;
        }

        $usuario = $gestion->crear($datos, new User);
        $this->info('Usuario creado con rol '.$usuario->nombreRol().'. Ya puede iniciar sesión.');

        return self::SUCCESS;
    }
}
