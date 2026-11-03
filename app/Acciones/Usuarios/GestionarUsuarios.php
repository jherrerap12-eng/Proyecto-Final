<?php

namespace App\Acciones\Usuarios;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GestionarUsuarios
{
    public function crear(array $datos, User $responsable): User
    {
        return DB::transaction(function () use ($datos) {
            $usuario = new User;
            $usuario->forceFill([
                'name' => trim($datos['name']),
                'email' => Str::lower(trim($datos['email'])),
                'password' => $datos['password'],
                'rol' => $datos['rol'],
                'activo' => true,
            ])->save();

            return $usuario;
        }, 3);
    }

    public function actualizar(User $usuario, array $datos, User $responsable): User
    {
        return DB::transaction(function () use ($usuario, $datos, $responsable) {
            $usuario = User::whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            if ($usuario->rol === 'administrador' && $datos['rol'] !== 'administrador') {
                if ($usuario->id === $responsable->id) {
                    $this->error('rol', 'No puedes quitarte el rol de administrador a ti mismo.');
                }
                $this->conservarAdministrador($usuario);
            }
            $usuario->forceFill([
                'name' => trim($datos['name']),
                'email' => Str::lower(trim($datos['email'])),
                'rol' => $datos['rol'],
            ])->save();

            return $usuario;
        }, 3);
    }

    public function cambiarEstado(User $usuario, bool $activo, User $responsable): User
    {
        return DB::transaction(function () use ($usuario, $activo, $responsable) {
            $usuario = User::whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            if ($usuario->activo === $activo) {
                return $usuario;
            }
            if (! $activo) {
                if ($usuario->id === $responsable->id) {
                    $this->error('estado', 'No puedes desactivar tu propia cuenta.');
                }
                if ($usuario->rol === 'administrador') {
                    $this->conservarAdministrador($usuario);
                }
            }
            $usuario->forceFill(['activo' => $activo, 'desactivado_en' => $activo ? null : now()])->save();
            if (! $activo) {
                $this->cerrarSesiones($usuario);
            }

            return $usuario;
        }, 3);
    }

    /** El administrador establece una contraseña nueva (por ejemplo, si la persona la olvidó). */
    public function establecerContrasena(User $usuario, string $contrasena): void
    {
        DB::transaction(function () use ($usuario, $contrasena) {
            $usuario = User::whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            $usuario->forceFill(['password' => $contrasena])->save();
            $this->cerrarSesiones($usuario);
        }, 3);
    }

    /** Cambio de la propia contraseña: la sesión actual sigue abierta y se cierran las demás. */
    public function cambiarPropia(User $usuario, string $contrasena, ?string $sesionActual): void
    {
        $usuario->forceFill(['password' => $contrasena, 'remember_token' => Str::random(60)])->save();
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $usuario->id)->where('id', '!=', (string) $sesionActual)->delete();
        }
    }

    private function conservarAdministrador(User $usuario): void
    {
        // Bloquea a todos los administradores activos para que dos cambios simultáneos
        // no dejen el sistema sin ninguno.
        $otros = User::where('rol', 'administrador')->where('activo', true)->whereKeyNot($usuario->id)->lockForUpdate()->count();
        if ($otros === 0) {
            $this->error('rol', 'Debe quedar al menos un administrador activo.');
        }
    }

    private function cerrarSesiones(User $usuario): void
    {
        // El middleware de cuenta activa y AuthenticateSession invalidan las sesiones abiertas;
        // con sesiones en base de datos además se eliminan de inmediato.
        $usuario->forceFill(['remember_token' => Str::random(60)])->save();
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $usuario->id)->delete();
        }
    }

    private function error(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
