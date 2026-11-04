<?php

// Solo MariaDB/MySQL local. Dos procesos PHP y conexiones independientes por escenario.
// Todas las escrituras usan clones InnoDB con prefijo aleatorio, nunca tablas del negocio.
use App\Acciones\Inventario\GestionarInventario;
use App\Acciones\Pagos\CorregirPago;
use App\Acciones\Pagos\RegistrarPago;
use App\Acciones\Pagos\RegistrarReembolso;
use App\Acciones\Pedidos\GestionarPedidos;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use App\Soporte\Importes;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$aplicacion = require __DIR__.'/../../bootstrap/app.php';
$aplicacion->make(Kernel::class)->bootstrap();
if (! $aplicacion->environment('local') || config('database.default') !== 'mysql' || DB::connection()->getTablePrefix() !== '') {
    fwrite(STDERR, "Se requiere entorno local, conexión mysql y sin prefijo de tablas.\n");
    exit(1);
}
$trabajador = ($argv[1] ?? '') === 'trabajador';
$prefijo = $trabajador ? ($argv[2] ?? '') : 'fin_'.bin2hex(random_bytes(6)).'_';
if (! preg_match('/^fin_[a-f0-9]{12}_$/D', $prefijo)) {
    exit(1);
}
$carpeta = storage_path('framework/testing/'.$prefijo);

function esperarMarcaFinanciera(string $ruta, $proceso): void
{
    $limite = microtime(true) + 15;
    while (! is_file($ruta)) {
        if (microtime(true) >= $limite || ! proc_get_status($proceso)['running']) {
            throw new RuntimeException('El trabajador no alcanzó la barrera '.$ruta);
        }
        usleep(20000);
    }
}

if ($trabajador) {
    $nombre = $argv[3] ?? '';
    if (! preg_match('/^\d+_[ab]\.json$/D', $nombre) || ! is_file($carpeta.'/'.$nombre)) {
        exit(1);
    }
    config(['database.connections.mysql.prefix' => $prefijo]);
    DB::purge('mysql');
    DB::statement('SET SESSION innodb_lock_wait_timeout = 8');
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $trabajo = json_decode(file_get_contents($carpeta.'/'.$nombre), true, flags: JSON_THROW_ON_ERROR);
    $conexion = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
    $codigo = 0;
    try {
        DB::transaction(function () use ($trabajo, $carpeta, $conexion) {
            if ($trabajo['orden'] === 'b') {
                // NOWAIT demuestra que el otro proceso mantiene la fila bloqueada ahora.
                try {
                    DB::table('pedidos')->where('id', $trabajo['pedido_id'])->lock('for update nowait')->first();
                    throw new RuntimeException('El segundo trabajador no encontró contención real.');
                } catch (QueryException $excepcion) {
                    if (! in_array((int) ($excepcion->errorInfo[1] ?? 0), [1205, 3572], true)) {
                        throw $excepcion;
                    }
                }
                // Establecer deliberadamente una vista anterior al commit de A, como
                // sucede al resolver modelos antes de esperar el bloqueo del pedido.
                DB::table('reservas')->where('pedido_id', $trabajo['pedido_id'])->get();
                file_put_contents($carpeta.'/contencion', (string) $conexion);
            }
            $usuario = User::firstOrFail();
            $pago = Pago::findOrFail($trabajo['pago_id']);
            $datos = ['importe' => $trabajo['importe'], 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'clave_operacion' => $trabajo['clave'], 'motivo' => 'Verificación concurrente aislada', 'verificar' => false];
            match ($trabajo['accion']) {
                'corregir' => app(CorregirPago::class)->corregir($pago, $datos, $usuario),
                'anular' => app(CorregirPago::class)->anular($pago, $datos['motivo'], $usuario),
                'verificar' => app(RegistrarPago::class)->verificar($pago, $usuario),
                'reembolsar' => app(RegistrarReembolso::class)->registrar($trabajo['pedido_id'], $datos, $usuario),
                'cancelar' => app(GestionarPedidos::class)->cancelar($pedido = Pedido::findOrFail($trabajo['pedido_id']), $pedido->version, $datos['motivo'], $usuario),
                default => throw new RuntimeException('Acción desconocida.'),
            };
            if ($trabajo['orden'] === 'a') {
                file_put_contents($carpeta.'/bloqueado', (string) $conexion);
                $limite = microtime(true) + 15;
                while (! is_file($carpeta.'/liberar')) {
                    if (microtime(true) >= $limite) {
                        throw new RuntimeException('No se recibió permiso de la barrera para confirmar.');
                    }
                    usleep(20000);
                }
            }
        }, 3);
        $resultado = ['resultado' => 'confirmado', 'conexion' => $conexion];
    } catch (ValidationException $excepcion) {
        $codigo = 2;
        $resultado = ['resultado' => 'rechazado', 'conexion' => $conexion, 'errores' => $excepcion->errors()];
    } catch (Throwable $excepcion) {
        $codigo = 1;
        $resultado = ['resultado' => 'fallo', 'conexion' => $conexion, 'error' => $excepcion->getMessage()];
    }
    file_put_contents($carpeta.'/'.$trabajo['orden'].'_terminado', '1');
    echo json_encode($resultado, JSON_THROW_ON_ERROR).PHP_EOL;
    exit($codigo);
}

$tablas = ['users', 'clientes', 'productos', 'movimientos_inventario', 'pedidos', 'detalles_pedido', 'reservas', 'eventos_pedido', 'pagos', 'reembolsos'];
$creadas = [];
$procesos = [];
$archivos = [];
$comprobar = function (bool $condicion, string $mensaje): void {
    if (! $condicion) {
        throw new RuntimeException($mensaje);
    }
};
$iniciar = function (array $trabajo, int $numero) use ($prefijo, $carpeta, &$procesos, &$archivos): array {
    $nombre = $numero.'_'.$trabajo['orden'].'.json';
    $ruta = $carpeta.'/'.$nombre;
    file_put_contents($ruta, json_encode($trabajo, JSON_THROW_ON_ERROR));
    $archivos[] = $ruta;
    $proceso = proc_open([PHP_BINARY, __FILE__, 'trabajador', $prefijo, $nombre], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $salidas);
    if (! is_resource($proceso)) {
        throw new RuntimeException('No se pudo iniciar el trabajador financiero.');
    }
    fclose($salidas[0]);
    $procesos[] = [$proceso, $salidas];

    return [$proceso, $salidas];
};
$recoger = function (array $trabajador): array {
    [$proceso, $salidas] = $trabajador;
    $limite = microtime(true) + 15;
    do {
        $estado = proc_get_status($proceso);
        if (! $estado['running']) {
            break;
        }
        if (microtime(true) >= $limite) {
            throw new RuntimeException('El trabajador excedió el tiempo límite.');
        }
        usleep(20000);
    } while (true);
    $texto = stream_get_contents($salidas[1]);
    $errores = stream_get_contents($salidas[2]);
    fclose($salidas[1]);
    fclose($salidas[2]);
    $codigoCierre = proc_close($proceso);
    $codigo = $estado['exitcode'] >= 0 ? $estado['exitcode'] : $codigoCierre;
    if ($errores !== '' || ! in_array($codigo, [0, 2], true)) {
        throw new RuntimeException('Falló un trabajador: '.$texto.' '.$errores);
    }

    return ['codigo' => $codigo, 'datos' => json_decode($texto, true, flags: JSON_THROW_ON_ERROR)];
};

try {
    if (! is_dir($carpeta) && ! mkdir($carpeta, 0700, true)) {
        throw new RuntimeException('No se pudo crear la carpeta de coordinación.');
    }
    foreach ($tablas as $tabla) {
        DB::statement('CREATE TABLE `'.$prefijo.$tabla.'` LIKE `'.$tabla.'`');
        $creadas[] = $tabla;
        $motor = DB::selectOne('SELECT ENGINE AS motor FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$prefijo.$tabla])->motor;
        $comprobar(strtolower($motor) === 'innodb', 'La prueba requiere tablas InnoDB.');
    }
    config(['database.connections.mysql.prefix' => $prefijo]);
    DB::purge('mysql');
    $conexionPadre = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
    echo 'Motor: '.DB::selectOne('SELECT VERSION() AS version')->version."; trabajadores en REPEATABLE READ.\n";
    $usuario = new User(['name' => 'Operador de prueba aislada', 'email' => 'financiera@example.test', 'password' => bin2hex(random_bytes(24))]);
    $usuario->forceFill(['rol' => 'administrador', 'activo' => true])->save();
    $cliente = Cliente::create(['nombre' => 'Cliente de prueba aislada', 'telefono' => '55550000']);
    // escenario, fixture, acción A, acción B, códigos, pagos/anulados/reemplazos/verificado/reembolsado/versión
    $casos = [
        ['correcciones distintas', 'pendiente', 'corregir', 'corregir', [0, 2], [2, 1, 1, 0, 0, 1]],
        ['correcciones de abonos distintos', 'dos_pendientes', 'corregir', 'corregir', [0, 2], [3, 1, 1, 0, 0, 1]],
        ['reenvío de corrección', 'pendiente', 'corregir', 'corregir', [0, 0], [2, 1, 1, 0, 0, 1]],
        ['doble anulación', 'pendiente', 'anular', 'anular', [0, 0], [1, 1, 0, 0, 0, 1]],
        ['corrección y anulación', 'pendiente', 'corregir', 'anular', [0, 0], [2, 1, 1, 0, 0, 1]],
        ['anulación y corrección', 'pendiente', 'anular', 'corregir', [0, 2], [1, 1, 0, 0, 0, 1]],
        ['anulación y verificación', 'pendiente', 'anular', 'verificar', [0, 2], [1, 1, 0, 0, 0, 1]],
        ['verificación y anulación', 'pendiente', 'verificar', 'anular', [0, 2], [1, 0, 0, 6000, 0, 1]],
        ['cancelación y anulación verificada', 'verificado', 'cancelar', 'anular', [0, 2], [1, 0, 0, 10000, 0, 1]],
        ['cancelación enviada y reembolso', 'enviado', 'cancelar', 'reembolsar', [0, 2], [1, 0, 0, 10000, 0, 1]],
        ['reembolsos que excederían el saldo', 'cancelado', 'reembolsar', 'reembolsar', [0, 2], [1, 0, 0, 10000, 6000, 1]],
        ['reenvío de reembolso', 'cancelado', 'reembolsar', 'reembolsar', [0, 0], [1, 0, 0, 10000, 6000, 1]],
        ['reembolsos parciales compatibles', 'cancelado', 'reembolsar', 'reembolsar', [0, 0], [1, 0, 0, 10000, 10000, 2]],
        ['reembolso y anulación verificada', 'cancelado', 'reembolsar', 'anular', [0, 2], [1, 0, 0, 10000, 6000, 1]],
        ['reembolso y corrección', 'cancelado', 'reembolsar', 'corregir', [0, 2], [1, 0, 0, 10000, 6000, 1]],
    ];
    foreach ($casos as $numero => [$nombre, $fixture, $accionA, $accionB, $codigos, $esperado]) {
        foreach (['bloqueado', 'contencion', 'liberar', 'a_terminado', 'b_terminado'] as $marca) {
            $ruta = $carpeta.'/'.$marca;
            if (is_file($ruta)) {
                unlink($ruta);
            }
        }
        $pieza = app(GestionarInventario::class)->registrar(['codigo' => 'FIN-'.($numero + 1), 'nombre' => 'Pieza aislada', 'costo' => '10.00', 'precio' => '100.00', 'existencias' => 1], $usuario);
        $pedido = app(GestionarPedidos::class)->guardar(['cliente_id' => $cliente->id, 'modalidad' => 'contra_entrega', 'productos' => [$pieza->id], 'referencia' => (string) Str::uuid()], $usuario);
        app(GestionarPedidos::class)->confirmar($pedido, $pedido->version, $usuario);
        $importeOriginal = match ($fixture) {
            'pendiente' => '60.00',
            'dos_pendientes' => '40.00',
            default => '100.00',
        };
        $pago = app(RegistrarPago::class)->registrar($pedido->id, ['importe' => $importeOriginal, 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()], $usuario);
        $pagoB = $pago;
        if ($fixture === 'dos_pendientes') {
            $pagoB = app(RegistrarPago::class)->registrar($pedido->id, ['importe' => '40.00', 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()], $usuario);
        }
        if (! in_array($fixture, ['pendiente', 'dos_pendientes'], true)) {
            app(RegistrarPago::class)->verificar($pago, $usuario);
        }
        if ($fixture === 'enviado') {
            $pedido->refresh()->update(['estado_logistico' => 'enviado']);
        }
        if ($fixture === 'cancelado') {
            $pedido->refresh();
            app(GestionarPedidos::class)->cancelar($pedido, $pedido->version, 'Cancelación de prueba', $usuario);
        }
        $version = $pedido->refresh()->version;
        $eventos = $pedido->eventos()->count();
        $clave = (string) Str::uuid();
        $base = ['pedido_id' => $pedido->id, 'pago_id' => $pago->id, 'importe' => '60.00'];
        $a = $iniciar(array_merge($base, ['orden' => 'a', 'accion' => $accionA, 'clave' => $clave]), $numero);
        esperarMarcaFinanciera($carpeta.'/bloqueado', $a[0]);
        $b = $iniciar(array_merge($base, ['orden' => 'b', 'accion' => $accionB, 'pago_id' => $pagoB->id, 'clave' => str_starts_with($nombre, 'reenvío') ? $clave : (string) Str::uuid(), 'importe' => $nombre === 'reembolsos parciales compatibles' ? '40.00' : '60.00']), $numero);
        esperarMarcaFinanciera($carpeta.'/contencion', $b[0]);
        $idA = (int) file_get_contents($carpeta.'/bloqueado');
        $idB = (int) file_get_contents($carpeta.'/contencion');
        $comprobar(count(array_unique([$conexionPadre, $idA, $idB])) === 3, 'Se reutilizó una conexión en vez de competir.');
        usleep(150000);
        $comprobar(! is_file($carpeta.'/b_terminado'), 'El segundo proceso terminó antes de liberar el bloqueo.');
        file_put_contents($carpeta.'/liberar', '1');
        $resultados = [$recoger($a), $recoger($b)];
        $procesos = [];
        $comprobar(array_column($resultados, 'codigo') === $codigos, $nombre.': resultado inesperado '.json_encode($resultados));
        $pedido->refresh();
        $pagos = $pedido->pagos()->get();
        $reembolsos = $pedido->reembolsos()->get();
        $devuelto = $reembolsos->sum(fn ($registro) => Importes::aCentavos($registro->monto));
        $actual = [$pagos->count(), $pagos->whereNotNull('anulado_en')->count(), $pagos->whereNotNull('corrige_pago_id')->count(), $pedido->pagadoCentavos(), $devuelto, $pedido->version - $version];
        $comprobar($actual === $esperado, $nombre.': se alteró el saldo, la versión o el historial '.json_encode($actual));
        $vigente = $pagos->whereNull('anulado_en')->sum(fn ($registro) => Importes::aCentavos($registro->monto));
        $vigenteEsperado = $fixture === 'dos_pendientes' ? 10000 : ($fixture === 'pendiente' ? ($esperado[1] === 1 && $esperado[2] === 0 ? 0 : 6000) : 10000);
        $comprobar($vigente === $vigenteEsperado && $vigente <= Importes::aCentavos($pedido->total), $nombre.': los abonos vigentes exceden el total o perdieron importe.');
        $comprobar($pago->fresh()->monto === $importeOriginal, $nombre.': se sobrescribió el importe histórico original.');
        $mutaciones = $esperado[5];
        $promocion = $nombre === 'verificación y anulación' ? 1 : 0;
        $comprobar($pedido->eventos()->count() - $eventos === $mutaciones + $promocion, $nombre.': eventos duplicados o escritura parcial.');
        $comprobar($devuelto <= $pedido->pagadoCentavos(), $nombre.': se devolvió más dinero del verificado.');
        $comprobar($reembolsos->count() === ($devuelto === 0 ? 0 : ($nombre === 'reembolsos parciales compatibles' ? 2 : 1)), $nombre.': reembolso duplicado.');
        if ($accionA === 'verificar') {
            $comprobar($pedido->reservas()->where('estado', 'activa')->where('tipo', 'firme')->count() === 1, 'La reserva firme no conservó su mínimo.');
        }
        if ($fixture === 'enviado') {
            $comprobar($pedido->reservas()->where('estado', 'devolucion_pendiente')->count() === 1, 'La cancelación enviada debe conservar la devolución pendiente.');
        }
        echo 'APROBADO: '.$nombre.'; conexiones '.$idA.'/'.$idB." con contención comprobada.\n";
    }
} catch (Throwable $excepcion) {
    $fallo = $excepcion;
} finally {
    foreach ($procesos as [$proceso, $salidas]) {
        if (is_resource($proceso)) {
            if (proc_get_status($proceso)['running']) {
                proc_terminate($proceso);
            }
            proc_close($proceso);
        }
        foreach ([$salidas[1], $salidas[2]] as $salida) {
            if (is_resource($salida)) {
                fclose($salida);
            }
        }
    }
    foreach (array_reverse($creadas) as $tabla) {
        DB::statement('DROP TABLE IF EXISTS `'.$prefijo.$tabla.'`');
    }
    foreach (array_merge($archivos, array_map(fn ($nombre) => $carpeta.'/'.$nombre, ['bloqueado', 'contencion', 'liberar', 'a_terminado', 'b_terminado'])) as $ruta) {
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }
    if (is_dir($carpeta)) {
        rmdir($carpeta);
    }
}
if (isset($fallo)) {
    fwrite(STDERR, $fallo->getMessage().PHP_EOL);
    exit(1);
}
echo "OK: 15 escenarios financieros concurrentes; clones y archivos de coordinación eliminados.\n";
