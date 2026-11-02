<?php

use App\Http\Controllers\ControladorInventario;
use Illuminate\Support\Facades\Route;

// Este archivo se carga dentro del grupo autenticado de web.php.
Route::prefix('inventario')->name('inventario.')->controller(ControladorInventario::class)->group(function () {
    Route::get('/', 'listar')->name('listar');
    Route::get('/crear', 'crear')->name('crear');
    Route::post('/', 'guardar')->name('guardar');
    Route::get('/{producto}', 'mostrar')->whereNumber('producto')->name('mostrar');
    Route::get('/{producto}/editar', 'editar')->whereNumber('producto')->name('editar');
    Route::put('/{producto}', 'actualizar')->whereNumber('producto')->name('actualizar');
    Route::post('/{producto}/movimientos', 'ajustar')->whereNumber('producto')->name('ajustar');
    Route::patch('/{producto}/estado', 'cambiarEstado')->whereNumber('producto')->name('estado');
});
