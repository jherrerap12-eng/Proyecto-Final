<?php

namespace App\Http\Requests;

class SolicitudCorreccionPago extends SolicitudPago
{
    public function authorize(): bool
    {
        return $this->user()?->can('corregir-pagos') ?? false;
    }

    public function rules(): array
    {
        return parent::rules() + [
            'motivo' => ['required', 'string', 'min:5', 'max:300'],
            'verificar' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['motivo' => 'motivo de la corrección', 'verificar' => 'verificación'];
    }
}
