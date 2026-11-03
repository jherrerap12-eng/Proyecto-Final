<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudEnvio extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'transportista' => ['required', Rule::in(['forza', 'mensajeria_propia'])],
            'guia' => ['nullable', 'string', 'max:100', 'required_if:transportista,forza'],
            'destinatario' => ['required', 'string', 'max:150'],
            'telefono' => ['bail', 'required', 'string', 'max:50', 'regex:/^\+?[0-9 ()-]+$/', function ($atributo, $valor, $fallar) {
                $digitos = preg_replace('/\D/', '', $valor);
                if (strlen($digitos) < 8 || strlen($digitos) > 15) {
                    $fallar('El teléfono debe contener entre 8 y 15 dígitos.');
                }
            }],
            'direccion' => ['required', 'string', 'max:500'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['guia.required_if' => 'Registra la guía o referencia del envío por Forza.'];
    }

    public function attributes(): array
    {
        return ['guia' => 'guía', 'telefono' => 'teléfono', 'direccion' => 'dirección'];
    }
}
