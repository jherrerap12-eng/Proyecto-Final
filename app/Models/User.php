<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Soporte\Permisos;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Rol, estado y desactivación se asignan solo desde GestionarUsuarios (forceFill),
     * nunca por asignación masiva desde el navegador.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'desactivado_en' => 'datetime',
        ];
    }

    public function puede(string $permiso): bool
    {
        return $this->activo && Permisos::permite($this->rol, $permiso);
    }

    public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    public function nombreRol(): string
    {
        return Permisos::etiqueta($this->rol);
    }
}
