<?php

use App\Http\Controllers\ControladorAutenticacion;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'panel' : 'login');
});

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [ControladorAutenticacion::class, 'mostrar'])->name('login');
    Route::post('/ingresar', [ControladorAutenticacion::class, 'ingresar'])->name('sesion.ingresar');
});

Route::middleware('auth')->group(function () {
    require __DIR__.'/inventario.php';

    Route::view('/panel', 'panel.inicio')->name('panel');
    Route::post('/salir', [ControladorAutenticacion::class, 'salir'])->name('sesion.salir');
});
