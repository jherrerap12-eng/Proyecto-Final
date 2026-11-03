<?php

use App\Http\Controllers\ControladorEntregas;
use Illuminate\Support\Facades\Route;

Route::prefix('entregas')->name('entregas.')->controller(ControladorEntregas::class)->group(function () {
    Route::get('/', 'listar')->name('listar');
    Route::get('/pedidos/{pedido}', 'mostrar')->whereNumber('pedido')->name('mostrar');
    Route::post('/pedidos/{pedido}/empacar', 'empacar')->whereNumber('pedido')->name('empacar');
    Route::post('/pedidos/{pedido}/enviar', 'enviar')->whereNumber('pedido')->name('enviar');
    Route::post('/pedidos/{pedido}/entregar', 'entregar')->whereNumber('pedido')->name('entregar');
});
