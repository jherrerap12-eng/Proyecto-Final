<?php

// Solo entorno local: escrituras y limpieza limitadas a clones con prefijo aleatorio.
use App\Acciones\Entregas\GestionarEntregas;
use App\Acciones\Inventario\GestionarInventario;
use App\Acciones\Pedidos\GestionarPedidos;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
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
$datosEnvio = fn (Pedido $pedido) => ['version' => $pedido->version, 'transportista' => 'forza', 'guia' => 'VERIFICACION-001', 'destinatario' => 'Destinatario aislado', 'telefono' => '55550000', 'direccion' => 'Dirección de prueba', 'observaciones' => null];
if ($trabajador) {
    config(['database.connections.mysql.prefix' => $prefijo]);
    DB::purge('mysql');
    try {
        DB::transaction(function () use ($argv, $marca, $datosEnvio) {
            // Lectura previa intencional: también comprueba transacciones con snapshot antiguo.
            $pedido = Pedido::findOrFail((int) $argv[4]);
            $usuario = User::firstOrFail();
            match ($argv[5]) {
                'enviar' => app(GestionarEntregas::class)->enviar($pedido, $datosEnvio($pedido), $usuario),
                'entregar' => app(GestionarEntregas::class)->entregar($pedido, $pedido->version, null, $usuario),
                'cancelar' => app(GestionarPedidos::class)->cancelar($pedido, $pedido->version, 'Cancelación aislada', $usuario),
            };
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
$tablas = ['users', 'clientes', 'productos', 'movimientos_inventario', 'pedidos', 'detalles_pedido', 'reservas', 'eventos_pedido', 'pagos', 'envios'];
$procesos = [];
$iniciar = function (string $orden, int $pedidoId, string $accion) use ($prefijo, &$procesos): array {
    $proceso = proc_open([PHP_BINARY, __FILE__, 'trabajador', $prefijo, $orden, (string) $pedidoId, $accion], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias);
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
    $casos = [
        ['doble_envio', 'enviar', 'enviar', 0],
        ['cancelar_antes', 'cancelar', 'enviar', 2],
        ['cancelar_formulario_obsoleto', 'enviar', 'cancelar', 2],
        ['doble_entrega', 'entregar', 'entregar', 0],
    ];
    foreach ($casos as $numero => [$caso, $accionUno, $accionDos, $esperadoDos]) {
        if (is_file($marca)) {
            unlink($marca);
        }
        $pieza = app(GestionarInventario::class)->registrar(['codigo' => 'EV-'.($numero + 1), 'nombre' => 'Pieza aislada', 'costo' => '10.00', 'precio' => '100.00', 'existencias' => 1], $usuario);
        $pedido = app(GestionarPedidos::class)->guardar(['cliente_id' => $cliente->id, 'modalidad' => 'contra_entrega', 'productos' => [$pieza->id], 'referencia' => (string) Str::uuid()], $usuario);
        app(GestionarPedidos::class)->confirmar($pedido, $pedido->version, $usuario);
        $pedido->refresh();
        app(GestionarEntregas::class)->empacar($pedido, $pedido->version, $usuario);
        $pedido->refresh();
        if ($caso === 'doble_entrega') {
            app(GestionarEntregas::class)->enviar($pedido, $datosEnvio($pedido), $usuario);
            $pedido->refresh();
        }
        [$primero, $salidasUno] = $iniciar('primero', $pedido->id, $accionUno);
        $limite = microtime(true) + 10;
        while (! is_file($marca) && microtime(true) < $limite) {
            usleep(100000);
        }
        if (! is_file($marca)) {
            throw new RuntimeException('El primer proceso no adquirió el bloqueo.');
        }
        [$segundo, $salidasDos] = $iniciar('segundo', $pedido->id, $accionDos);
        $respuestas = [trim(stream_get_contents($salidasUno[1])), trim(stream_get_contents($salidasDos[1]))];
        $errores = stream_get_contents($salidasUno[2]).stream_get_contents($salidasDos[2]);
        foreach ([$salidasUno, $salidasDos] as $salidas) {
            fclose($salidas[1]);
            fclose($salidas[2]);
        }
        $codigos = [proc_close($primero), proc_close($segundo)];
        $procesos = [];
        if ($codigos !== [0, $esperadoDos] || $errores || $respuestas !== ['confirmado', $esperadoDos === 0 ? 'confirmado' : 'rechazado']) {
            throw new RuntimeException('Resultado inesperado: '.$caso.' '.json_encode([$codigos, $respuestas, $errores]));
        }
        $salidasFisicas = $pieza->movimientos()->where('tipo', 'envio')->count();
        if ($salidasFisicas !== ($caso === 'cancelar_antes' ? 0 : 1)) {
            throw new RuntimeException('Salida física duplicada o faltante.');
        }
        if ($pieza->refresh()->existencias !== ($caso === 'cancelar_antes' ? 1 : 0)) {
            throw new RuntimeException('Existencias incorrectas.');
        }
        if ($caso === 'doble_entrega' && ($pedido->refresh()->estado_logistico !== 'entregado' || $pedido->eventos()->where('tipo', 'entregado')->count() !== 1 || $pedido->pagadoCentavos() !== 0)) {
            throw new RuntimeException('Entrega duplicada o cobro ficticio.');
        }
        if ($caso === 'cancelar_formulario_obsoleto') {
            // Tras recargar, la cancelación conserva stock 0 hasta el regreso físico.
            $pedido->refresh();
            app(GestionarPedidos::class)->cancelar($pedido, $pedido->version, 'Regreso solicitado', $usuario);
            if ($pieza->refresh()->existencias !== 0 || ! $pieza->reservaActual) {
                throw new RuntimeException('Se liberó antes del regreso.');
            }
            app(GestionarPedidos::class)->confirmarDevolucion($pedido, $usuario);
            app(GestionarPedidos::class)->confirmarDevolucion($pedido, $usuario);
            if ($pieza->refresh()->existencias !== 1 || $pieza->movimientos()->where('tipo', 'devolucion')->count() !== 1 || ! $pedido->envio()->firstOrFail()->devuelto_en) {
                throw new RuntimeException('Regreso inconsistente.');
            }
        }
        echo 'APROBADO: '.$caso.".\n";
    }
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
