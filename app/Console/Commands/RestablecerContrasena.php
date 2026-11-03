<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RestablecerContrasena extends Command
{
    protected $signature = 'usuario:restablecer-contrasena';

    protected $description = 'Restablece una cuenta desde la consola administrativa, sin exponer contraseñas en argumentos.';

    public function handle(): int
    {
        $correo = Str::lower(trim((string) $this->ask('Correo electrónico de la cuenta')));
        $validacionCorreo = Validator::make(['email' => $correo], ['email' => ['required', 'email', 'max:255']]);
        if ($validacionCorreo->fails()) {
            $this->error('Escribe un correo electrónico válido.');

            return self::FAILURE;
        }
        $usuario = User::where('email', $correo)->first();
        if (! $usuario) {
            $this->error('No existe una cuenta con ese correo en la base configurada. No se creó ni modificó ninguna cuenta.');

            return self::FAILURE;
        }

        $contrasena = $this->secret('Nueva contraseña (mínimo 12 caracteres, letras y números)');
        $confirmacion = $this->secret('Confirma la nueva contraseña');
        $validacion = Validator::make(['password' => $contrasena, 'password_confirmation' => $confirmacion], [
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);
        if ($validacion->fails()) {
            foreach ($validacion->errors()->all() as $mensaje) {
                $this->error($mensaje);
            }
            $this->warn('No se cambió la contraseña.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($usuario, $contrasena, $correo) {
            $usuario = User::whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            // El cast hashed de User guarda un hash, nunca la contraseña en texto.
            $usuario->forceFill(['password' => $contrasena, 'remember_token' => Str::random(60)])->save();
            DB::table('password_reset_tokens')->where('email', $correo)->delete();
        }, 3);
        foreach (['127.0.0.1', '::1'] as $direccionLocal) {
            RateLimiter::clear('ingreso:'.hash('sha256', $correo.'|'.$direccionLocal));
        }
        $this->info('Contraseña restablecida. Inicia sesión con tu correo y la nueva contraseña.');

        return self::SUCCESS;
    }
}
