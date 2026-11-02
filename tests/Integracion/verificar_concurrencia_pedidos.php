<?php

// Prueba explícita sobre MySQL/MariaDB local. No forma parte de PHPUnit/SQLite.
// Clona tres tablas con prefijo aleatorio; no inserta en las tablas del negocio.
use App\Acciones\Inventario\GestionarInventario;
use App\Acciones\Pedidos\GestionarPedidos;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$aplicacion = require __DIR__.'/../../bootstrap/app.php';
$aplicacion->make(Kernel::class)->bootstrap();

if (! $aplicacion->environment('local') || config('database.default') !== 'mysql') {
    fwrite(STDERR, "Esta verificación requiere entorno local y conexión mysql.\n");
    exit(1);
}

$trabajador = ($argv[1] ?? '') === 'trabajador';
$prefijo = $trabajador ? ($argv[2] ?? '') : 'verif_'.bin2hex(random_bytes(6)).'_';
if (! preg_match('/^verif_[a-f0-9]{12}_$/', $prefijo)) {
    exit(1);
}
$marca = storage_path('framework/testing/'.$prefijo.'bloqueo');

if ($trabajador) {
    config(['database.connections.mysql.prefix' => $prefijo]);
    DB::purge('mysql');
    try {
        DB::transaction(function () use ($argv, $marca) {
            $pedido = Pedido::findOrFail((int) $argv[4]);
            $usuario = User::firstOrFail();
            app(GestionarPedidos::class)->confirmar($pedido, $pedido->version, $usuario);
            if (($argv[3] ?? '') === 'primero') {
                file_put_contents($marca, 'bloqueado');
                // Mantiene abierto el bloqueo para que el segundo proceso compita.
                usleep(1200000);
            }
        });
        echo "confirmado\n";
        exit(0);
    } catch (ValidationException $excepcion) {
        echo "rechazado\n";
        exit(2);
    }
}

$tablas = ['users', 'clientes', 'productos', 'movimientos_inventario', 'pedidos', 'detalles_pedido', 'reservas', 'eventos_pedido', 'pagos'];
$procesos = [];
$iniciar = function (string $orden, int $pedidoId) use ($prefijo, &$procesos): array {
    $proceso = proc_open([PHP_BINARY, __FILE__, 'trabajador', $prefijo, $orden, (string) $pedidoId], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias);
    if (! is_resource($proceso)) {
        throw new RuntimeException('No se pudo iniciar el proceso de prueba.');
    }
    fclose($tuberias[0]);
    $procesos[] = $proceso;

    return [$proceso, $tuberias];
};

try {
    foreach ($tablas as $tabla) {
        DB::statement('CREATE TABLE `'.$prefijo.$tabla.'` LIKE `'.$tabla.'`');
    }
    config(['database.connections.mysql.prefix' => $prefijo]);
    DB::purge('mysql');
    $usuario = User::create(['name' => 'Verificación aislada', 'email' => 'verificacion@example.test', 'password' => bin2hex(random_bytes(24))]);
    $pieza = app(GestionarInventario::class)->registrar(['codigo' => 'VERIF-001', 'nombre' => 'Pieza aislada', 'costo' => '10.25', 'precio' => '20.50', 'existencias' => 1], $usuario);

    $cliente = Cliente::create(['nombre' => 'Verificación', 'telefono' => '55550000']);
    $crear = fn () => app(GestionarPedidos::class)->guardar(['cliente_id' => $cliente->id, 'modalidad' => 'contra_entrega', 'productos' => [$pieza->id], 'referencia' => (string) Str::uuid()], $usuario);
    $pedidoUno = $crear();
    $pedidoDos = $crear();
    [$primero, $salidasPrimero] = $iniciar('primero', $pedidoUno->id);
    $limite = microtime(true) + 10;
    while (! is_file($marca) && microtime(true) < $limite) {
        usleep(100000);
    }
    if (! is_file($marca)) {
        throw new RuntimeException('El primer proceso no adquirió el bloqueo a tiempo.');
    }
    [$segundo, $salidasSegundo] = $iniciar('segundo', $pedidoDos->id);
    $respuestaPrimero = stream_get_contents($salidasPrimero[1]);
    $respuestaSegundo = stream_get_contents($salidasSegundo[1]);
    $erroresPrimero = stream_get_contents($salidasPrimero[2]);
    $erroresSegundo = stream_get_contents($salidasSegundo[2]);
    foreach ([$salidasPrimero, $salidasSegundo] as $salidas) {
        fclose($salidas[1]);
        fclose($salidas[2]);
    }
    $codigoPrimero = proc_close($primero);
    $codigoSegundo = proc_close($segundo);
    $procesos = [];
    if ($codigoPrimero !== 0 || $codigoSegundo !== 2 || trim($respuestaPrimero) !== 'confirmado' || trim($respuestaSegundo) !== 'rechazado' || $erroresPrimero || $erroresSegundo) {
        throw new RuntimeException('Resultado inesperado en los procesos concurrentes.');
    }
    if (Producto::firstOrFail()->existencias !== 1 || DB::table('reservas')->whereNotNull('producto_bloqueado_id')->count() !== 1 || Pedido::where('estado', 'confirmado')->count() !== 1) {
        throw new RuntimeException('Existencias o movimientos inconsistentes.');
    }
    $restriccionActiva = false;
    try {
        DB::table('productos')->update(['existencias' => 2]);
    } catch (QueryException $excepcion) {
        $restriccionActiva = true;
    }
    if (! $restriccionActiva) {
        throw new RuntimeException('La base permitió más de una pieza por código.');
    }
    echo "APROBADO: dos pedidos concurrentes, uno confirmado y otro rechazado; una sola reserva, cero disponibilidad y CHECK activo.\n";
} finally {
    foreach ($procesos as $proceso) {
        if (is_resource($proceso)) {
            proc_terminate($proceso);
            proc_close($proceso);
        }
    }
    foreach (array_reverse($tablas) as $tabla) {
        DB::statement('DROP TABLE IF EXISTS `'.$prefijo.$tabla.'`');
    }
    if (is_file($marca)) {
        unlink($marca);
    }
}
