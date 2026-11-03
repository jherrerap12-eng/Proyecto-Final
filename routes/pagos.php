<?php

use App\Http\Controllers\ControladorPagos;
use Illuminate\Support\Facades\Route;

Route::get('/pagos', [ControladorPagos::class, 'index'])->name('pagos.index');
Route::get('/pagos/pedidos/{pedido}', [ControladorPagos::class, 'show'])->whereNumber('pedido')->name('pagos.show');
Route::post('/pagos/pedidos/{pedido}', [ControladorPagos::class, 'store'])->whereNumber('pedido')->name('pagos.store');
Route::post('/pagos/abonos/{pago}/verificar', [ControladorPagos::class, 'verificar'])->whereNumber('pago')->middleware('can:verificar-pagos')->name('pagos.verificar');

// Correcciones y anulaciones: conservan el abono original y su historial.
Route::middleware('can:corregir-pagos')->group(function () {
    Route::get('/pagos/abonos/{pago}/corregir', [ControladorPagos::class, 'corregir'])->whereNumber('pago')->name('pagos.corregir');
    Route::post('/pagos/abonos/{pago}/corregir', [ControladorPagos::class, 'guardarCorreccion'])->whereNumber('pago')->name('pagos.corregir.guardar');
    Route::post('/pagos/abonos/{pago}/anular', [ControladorPagos::class, 'anular'])->whereNumber('pago')->name('pagos.anular');
});
