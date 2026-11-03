<?php

use App\Http\Controllers\ControladorPagos;
use Illuminate\Support\Facades\Route;

Route::get('/pagos', [ControladorPagos::class, 'index'])->name('pagos.index');
Route::get('/pagos/pedidos/{pedido}', [ControladorPagos::class, 'show'])->whereNumber('pedido')->name('pagos.show');
Route::post('/pagos/pedidos/{pedido}', [ControladorPagos::class, 'store'])->whereNumber('pedido')->name('pagos.store');
Route::post('/pagos/abonos/{pago}/verificar', [ControladorPagos::class, 'verificar'])->whereNumber('pago')->middleware('can:verificar-pagos')->name('pagos.verificar');
