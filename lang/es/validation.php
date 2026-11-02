<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'accepted' => 'Debes confirmar el regreso físico de todas las piezas.',
    'array' => 'El campo :attribute debe ser una selección válida.',
    'exists' => 'La selección de :attribute no existe o está de baja.',
    'distinct' => 'No puedes seleccionar la misma pieza más de una vez.',
    'string' => 'El campo :attribute debe ser texto.',
    'email' => 'El campo :attribute debe contener un correo electrónico válido.',
    'unique' => 'El valor de :attribute ya está registrado.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'numeric' => 'El campo :attribute debe ser numérico.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'decimal' => 'El campo :attribute admite hasta dos decimales.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'in' => 'El valor de :attribute no es válido.',
    'uuid' => 'La referencia no es válida. Recarga el formulario.',
    'min' => [
        'array' => 'Selecciona al menos :min pieza.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
        'array' => 'Selecciona como máximo :max piezas.',
        'numeric' => 'El campo :attribute no debe superar :max.',
        'string' => 'El campo :attribute no debe superar :max caracteres.',
    ],
    'password' => [
        'letters' => 'El campo :attribute debe incluir al menos una letra.',
        'numbers' => 'El campo :attribute debe incluir al menos un número.',
    ],
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
    ],
];
