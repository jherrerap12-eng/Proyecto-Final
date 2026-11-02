<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudProducto extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('codigo'))) {
            $this->merge(['codigo' => strtoupper(trim($this->input('codigo')))]);
        }
    }

    public function rules(): array
    {
        $producto = $this->route('producto');
        $reglas = [
            'codigo' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9][A-Z0-9_-]{0,19}$/', Rule::unique('productos', 'codigo')->ignore($producto)],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'costo' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'precio' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ];

        if ($producto) {
            $reglas['version'] = ['required', 'integer', 'min:1'];
        } else {
            $reglas['existencias'] = ['required', 'integer', 'min:0', 'max:1'];
        }

        return $reglas;
    }

    public function messages(): array
    {
        return ['codigo.regex' => 'Usa letras A–Z, números, guion o guion bajo; comienza con letra o número.'];
    }
}
