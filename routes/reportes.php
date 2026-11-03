<?php

use App\Http\Controllers\ControladorReportes;
use Illuminate\Support\Facades\Route;

Route::get('/reportes', [ControladorReportes::class, 'index'])->name('reportes.index');
Route::get('/reportes/exportar.csv', [ControladorReportes::class, 'exportar'])->name('reportes.exportar');
