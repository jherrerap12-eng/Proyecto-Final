<?php

use App\Http\Controllers\ControladorAutenticacion;
use App\Http\Controllers\ControladorClientes;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'panel' : 'login');
});

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [ControladorAutenticacion::class, 'mostrar'])->name('login');
    Route::post('/ingresar', [ControladorAutenticacion::class, 'ingresar'])->name('sesion.ingresar');
});

Route::middleware('auth')->group(function () {
    Route::resource('clientes', ControladorClientes::class)->parameters(['clientes' => 'cliente']);
    require __DIR__.'/inventario.php';
    require __DIR__.'/pagos.php';

    Route::view('/panel', 'panel.inicio')->name('panel');
    Route::post('/salir', [ControladorAutenticacion::class, 'salir'])->name('sesion.salir');
});
