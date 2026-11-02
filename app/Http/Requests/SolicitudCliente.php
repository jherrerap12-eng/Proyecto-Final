<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudCliente extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['bail', 'required', 'string', 'max:25', 'regex:/^\+?[0-9 ()-]+$/', function ($attribute, $value, $fail) {
                $digitos = preg_replace('/\D/', '', $value);
                if (strlen($digitos) < 8 || strlen($digitos) > 15) {
                    $fail('El teléfono debe contener entre 8 y 15 dígitos.');
                }
            }],
            'correo' => ['nullable', 'string', 'email', 'max:254'],
            'direccion' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['telefono' => 'teléfono', 'correo' => 'correo electrónico', 'direccion' => 'dirección'];
    }

    public function messages(): array
    {
        return ['telefono.regex' => 'El teléfono admite números, espacios, paréntesis, guiones y un + inicial.'];
    }
}
