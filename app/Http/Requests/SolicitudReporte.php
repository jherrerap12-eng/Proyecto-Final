<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SolicitudReporte extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $hoy = now('America/Guatemala');
        $this->merge([
            'desde' => $this->input('desde', $hoy->copy()->startOfMonth()->toDateString()),
            'hasta' => $this->input('hasta', $hoy->copy()->endOfMonth()->toDateString()),
            'agrupacion' => $this->input('agrupacion', 'diaria'),
        ]);
    }

    public function rules(): array
    {
        return [
            'desde' => ['bail', 'required', 'string', 'date_format:Y-m-d'],
            'hasta' => ['bail', 'required', 'string', 'date_format:Y-m-d'],
            'agrupacion' => ['required', Rule::in(['diaria', 'semanal', 'mensual'])],
            'tipo' => [$this->routeIs('reportes.exportar') ? 'required' : 'sometimes', Rule::in(['resumen', 'ventas', 'cobros', 'reembolsos'])],
            'ventas_pagina' => ['sometimes', 'integer', 'min:1'],
            'cobros_pagina' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validador): void
    {
        $validador->after(function (Validator $validador) {
            // Comparar solo fechas ya válidas; parámetros como desde[]=... no deben causar un 500.
            if (! $validador->errors()->has('desde') && ! $validador->errors()->has('hasta') && $this->input('hasta') < $this->input('desde')) {
                $validador->errors()->add('hasta', 'La fecha final debe ser igual o posterior a la fecha inicial.');
            }
        });
    }

    public function filtros(): array
    {
        return $this->safe()->only(['desde', 'hasta', 'agrupacion']);
    }

    public function attributes(): array
    {
        return ['desde' => 'fecha inicial', 'hasta' => 'fecha final', 'agrupacion' => 'agrupación'];
    }
}
