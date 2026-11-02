<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'email' => 'El campo :attribute debe contener un correo electrónico válido.',
    'unique' => 'El valor de :attribute ya está registrado.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
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
