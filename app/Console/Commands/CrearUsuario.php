<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CrearUsuario extends Command
{
    protected $signature = 'usuario:crear';

    protected $description = 'Crea una cuenta interna sin guardar contraseñas en el código ni en argumentos.';

    public function handle(): int
    {
        $nombre = trim((string) $this->ask('Nombre del usuario'));
        $correo = Str::lower(trim((string) $this->ask('Correo electrónico')));
        $contrasena = $this->secret('Contraseña (mínimo 12 caracteres, letras y números)');
        $confirmacion = $this->secret('Confirma la contraseña');

        $datos = [
            'name' => $nombre,
            'email' => $correo,
            'password' => $contrasena,
            'password_confirmation' => $confirmacion,
        ];

        $validacion = Validator::make($datos, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        if ($validacion->fails()) {
            foreach ($validacion->errors()->all() as $mensaje) {
                $this->error($mensaje);
            }

            return self::FAILURE;
        }

        User::create(collect($datos)->only(['name', 'email', 'password'])->all());
        $this->info('Usuario creado. Ya puede iniciar sesión.');

        return self::SUCCESS;
    }
}
