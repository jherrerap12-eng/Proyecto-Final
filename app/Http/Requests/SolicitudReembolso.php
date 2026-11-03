<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudReembolso extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registrar-reembolsos') ?? false;
    }

    public function rules(): array
    {
        return [
            'importe' => ['required', 'numeric', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D', 'gt:0'],
            'metodo' => ['required', Rule::in(['efectivo', 'transferencia', 'deposito'])],
            'referencia' => ['nullable', 'string', 'max:100', 'required_unless:metodo,efectivo'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'motivo' => ['required', 'string', 'min:5', 'max:300'],
            'clave_operacion' => ['required', 'uuid'],
        ];
    }

    public function attributes(): array
    {
        return ['metodo' => 'método', 'motivo' => 'motivo del reembolso', 'clave_operacion' => 'identificador de operación'];
    }
}
