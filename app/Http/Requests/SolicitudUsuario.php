<?php

namespace App\Http\Requests;

use App\Soporte\Permisos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SolicitudUsuario extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar-usuarios') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $reglas = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'rol' => ['required', Rule::in(array_keys(Permisos::ROLES))],
        ];
        if (! $usuario) {
            $reglas['password'] = ['required', 'confirmed', Password::min(12)->letters()->numbers()];
        }

        return $reglas;
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña', 'rol' => 'rol'];
    }
}
