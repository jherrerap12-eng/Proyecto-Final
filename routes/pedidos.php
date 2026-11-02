<?php

use App\Http\Controllers\ControladorPedidos;
use Illuminate\Support\Facades\Route;

Route::prefix('pedidos')->name('pedidos.')->controller(ControladorPedidos::class)->group(function () {
    Route::get('/', 'listar')->name('listar');
    Route::get('/crear', 'crear')->name('crear');
    Route::get('/opciones/{tipo}', 'opciones')->whereIn('tipo', ['clientes', 'piezas'])->name('opciones');
    Route::post('/', 'guardar')->name('guardar');
    Route::get('/{pedido}', 'mostrar')->whereNumber('pedido')->name('mostrar');
    Route::get('/{pedido}/editar', 'editar')->whereNumber('pedido')->name('editar');
    Route::put('/{pedido}', 'actualizar')->whereNumber('pedido')->name('actualizar');
    Route::post('/{pedido}/confirmar', 'confirmar')->whereNumber('pedido')->name('confirmar');
    Route::post('/{pedido}/cancelar', 'cancelar')->whereNumber('pedido')->name('cancelar');
    Route::post('/{pedido}/devolucion', 'devolver')->whereNumber('pedido')->name('devolver');
});
