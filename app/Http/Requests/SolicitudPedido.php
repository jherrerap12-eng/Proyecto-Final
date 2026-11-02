<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudPedido extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $reglas = [
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'modalidad' => ['required', Rule::in(['contra_entrega', 'deposito'])],
            'productos' => ['required', 'array', 'min:1', 'max:50'],
            'productos.*' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
        if ($this->route('pedido')) {
            $reglas['version'] = ['required', 'integer', 'min:1'];
        } else {
            $reglas['referencia'] = ['required', 'uuid'];
        }

        return $reglas;
    }
}
