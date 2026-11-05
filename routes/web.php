<?php

use App\Http\Controllers\ControladorAutenticacion;
use App\Http\Controllers\ControladorClientes;
use App\Http\Controllers\ControladorDashboard;
use Illuminate\Support\Facades\Route;
use App\Support\BabybuTikTok;

// Página de presentación pública de Babybu Toys.
// Los módulos de administración siguen protegidos como antes.
Route::get('/', function () {
    $videos = [];
    foreach (array_slice(array_pad(config('babybu_publicidad.videos', []), 2, ''), 0, 2) as $url) {
        $url = is_string($url) ? trim($url) : '';
        $videos[] = [
            'id' => BabybuTikTok::videoId($url),
            'url' => filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_HOST), ['vt.tiktok.com', 'vm.tiktok.com', 'www.tiktok.com', 'tiktok.com'], true) ? $url : null,
        ];
    }
    return view('publico.portada', compact('videos'));
})->name('inicio');

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [ControladorAutenticacion::class, 'mostrar'])->name('login');
    Route::post('/ingresar', [ControladorAutenticacion::class, 'ingresar'])->name('sesion.ingresar');
});

Route::middleware('auth')->group(function () {
    Route::resource('clientes', ControladorClientes::class)->parameters(['clientes' => 'cliente']);
    require __DIR__.'/inventario.php';
    require __DIR__.'/pedidos.php';
    require __DIR__.'/pagos.php';
    require __DIR__.'/reportes.php';
    require __DIR__.'/entregas.php';
    require __DIR__.'/usuarios.php';

    Route::get('/panel', [ControladorDashboard::class, 'index'])->name('panel');
    Route::post('/salir', [ControladorAutenticacion::class, 'salir'])->name('sesion.salir');
});
