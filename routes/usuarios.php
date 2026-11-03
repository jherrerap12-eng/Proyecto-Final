<?php

use App\Http\Controllers\ControladorCuenta;
use App\Http\Controllers\ControladorUsuarios;
use Illuminate\Support\Facades\Route;

// Se carga dentro del grupo autenticado de web.php.
Route::get('/cuenta/contrasena', [ControladorCuenta::class, 'contrasena'])->name('cuenta.contrasena');
Route::put('/cuenta/contrasena', [ControladorCuenta::class, 'actualizarContrasena'])->middleware('throttle:6,1')->name('cuenta.contrasena.actualizar');

Route::middleware('can:administrar-usuarios')->group(function () {
    Route::resource('usuarios', ControladorUsuarios::class)->except(['show', 'destroy'])->parameters(['usuarios' => 'usuario']);
    Route::patch('/usuarios/{usuario}/estado', [ControladorUsuarios::class, 'estado'])->name('usuarios.estado');
    Route::put('/usuarios/{usuario}/contrasena', [ControladorUsuarios::class, 'contrasena'])->middleware('throttle:6,1')->name('usuarios.contrasena');
});
