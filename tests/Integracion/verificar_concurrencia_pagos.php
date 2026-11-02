<?php

// Solo local: todas las escrituras usan tablas clonadas con prefijo aleatorio.
use App\Acciones\Inventario\GestionarInventario;
use App\Acciones\Pagos\RegistrarPago;
use App\Acciones\Pedidos\GestionarPedidos;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$aplicacion = require __DIR__.'/../../bootstrap/app.php';
$aplicacion->make(Kernel::class)->bootstrap();
if (! $aplicacion->environment('local') || config('database.default') !== 'mysql') {
    fwrite(STDERR, "Se requiere entorno local y conexión mysql.\n");
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
            $usuario = User::firstOrFail();
            $pedido = Pedido::findOrFail((int) $argv[4]);
            if ($argv[5] === 'cancelar') {
                app(GestionarPedidos::class)->cancelar($pedido, $pedido->version, 'Cancelación aislada', $usuario);
            } else {
                app(RegistrarPago::class)->registrar($pedido->id, ['importe' => '60.00', 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'clave_operacion' => $argv[6]], $usuario);
            }
            if ($argv[3] === 'primero') {
                file_put_contents($marca, 'bloqueado');
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
$iniciar = function (string $orden, int $pedidoId, string $accion, string $clave) use ($prefijo, &$procesos): array {
    $proceso = proc_open([PHP_BINARY, __FILE__, 'trabajador', $prefijo, $orden, (string) $pedidoId, $accion, $clave], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias);
    if (! is_resource($proceso)) {
        throw new RuntimeException('No se pudo iniciar el proceso.');
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
    $cliente = Cliente::create(['nombre' => 'Verificación aislada', 'telefono' => '55550000']);
    foreach (['sobrepago', 'reenvio', 'cancelacion'] as $numero => $caso) {
        if (is_file($marca)) {
            unlink($marca);
        }
        $pieza = app(GestionarInventario::class)->registrar(['codigo' => 'PG-'.($numero + 1), 'nombre' => 'Pieza aislada', 'costo' => '10.00', 'precio' => '100.00', 'existencias' => 1], $usuario);
        $pedido = app(GestionarPedidos::class)->guardar(['cliente_id' => $cliente->id, 'modalidad' => 'contra_entrega', 'productos' => [$pieza->id], 'referencia' => (string) Str::uuid()], $usuario);
        app(GestionarPedidos::class)->confirmar($pedido, $pedido->version, $usuario);
        $clave = (string) Str::uuid();
        [$primero, $salidasPrimero] = $iniciar('primero', $pedido->id, $caso === 'cancelacion' ? 'cancelar' : 'registrar', $clave);
        $limite = microtime(true) + 10;
        while (! is_file($marca) && microtime(true) < $limite) {
            usleep(100000);
        }
        if (! is_file($marca)) {
            throw new RuntimeException('El primer proceso no adquirió el bloqueo.');
        }
        [$segundo, $salidasSegundo] = $iniciar('segundo', $pedido->id, 'registrar', $caso === 'reenvio' ? $clave : (string) Str::uuid());
        $respuestas = [trim(stream_get_contents($salidasPrimero[1])), trim(stream_get_contents($salidasSegundo[1]))];
        $errores = stream_get_contents($salidasPrimero[2]).stream_get_contents($salidasSegundo[2]);
        foreach ([$salidasPrimero, $salidasSegundo] as $salidas) {
            fclose($salidas[1]);
            fclose($salidas[2]);
        }
        $codigos = [proc_close($primero), proc_close($segundo)];
        $procesos = [];
        $esperados = $caso === 'reenvio' ? [0, 0] : [0, 2];
        if ($codigos !== $esperados || $errores || $respuestas[0] !== 'confirmado' || $respuestas[1] !== ($caso === 'reenvio' ? 'confirmado' : 'rechazado')) {
            throw new RuntimeException('Resultado concurrente inesperado: '.$caso.' '.json_encode(['codigos' => $codigos, 'respuestas' => $respuestas, 'errores' => $errores], JSON_UNESCAPED_UNICODE));
        }
        $cantidad = $pedido->pagos()->count();
        if ($cantidad !== ($caso === 'cancelacion' ? 0 : 1)) {
            throw new RuntimeException('Cantidad de pagos incorrecta: '.$caso);
        }
        if ($caso !== 'cancelacion') {
            $pago = $pedido->pagos()->firstOrFail();
            if ($pago->monto !== '60.00' || $pedido->pagadoCentavos() !== 0) {
                throw new RuntimeException('Un pendiente se contó como verificado.');
            }
            app(RegistrarPago::class)->verificar($pago, $usuario);
            if ($pedido->pagadoCentavos() !== 6000 || $pedido->reservas()->first()->tipo !== 'firme') {
                throw new RuntimeException('No se integró pago con reserva firme.');
            }
        }
        echo 'APROBADO: '.$caso." concurrente.\n";
    }
    // Comprueba también la ruta legacy en MariaDB, siempre dentro de clones.
    DB::statement('DROP TABLE `'.$prefijo.'pagos`');
    Schema::create('pagos', function (Blueprint $tabla) {
        $tabla->engine = 'InnoDB';
        $tabla->id();
        $tabla->unsignedBigInteger('pedido_id');
        $tabla->unsignedBigInteger('usuario_id');
        $tabla->decimal('importe', 10, 2);
        $tabla->date('fecha');
        $tabla->string('metodo', 30);
        $tabla->string('referencia', 100)->nullable();
        $tabla->string('observaciones', 500)->nullable();
        $tabla->uuid('clave_operacion')->unique();
        $tabla->timestamps();
    });
    $pedidoLegacy = Pedido::where('estado', 'confirmado')->firstOrFail();
    $idLegacy = DB::table('pagos')->insertGetId(['pedido_id' => $pedidoLegacy->id, 'usuario_id' => $usuario->id, 'importe' => '25.50', 'fecha' => '2026-11-01', 'metodo' => 'transferencia', 'referencia' => 'LEGACY-PRUEBA', 'clave_operacion' => (string) Str::uuid()]);
    $puente = require __DIR__.'/../../database/migrations/2026_11_02_000300_integrar_esquema_pagos.php';
    $puente->up();
    $puente->up();
    $legacy = Pago::findOrFail($idLegacy);
    if ($legacy->monto !== '25.50' || $legacy->verificado_en !== null || $legacy->referencia !== 'LEGACY-PRUEBA') {
        throw new RuntimeException('No se preservó el pago legacy.');
    }
    Pago::create(['pedido_id' => $pedidoLegacy->id, 'usuario_id' => $usuario->id, 'monto' => '10.00', 'fecha_pago' => now()->toDateString(), 'metodo' => 'efectivo', 'clave_operacion' => (string) Str::uuid()]);
    echo "APROBADO: migración legacy conserva datos, es repetible y admite pagos nuevos sin verificar automáticamente.\n";
} catch (Throwable $excepcion) {
    $fallo = $excepcion;
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
if (isset($fallo)) {
    fwrite(STDERR, $fallo->getMessage().PHP_EOL);
    exit(1);
}
