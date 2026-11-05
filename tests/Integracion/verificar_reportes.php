<?php

// Comprueba SQL real en una conexión local con tablas TEMPORARY privadas de la sesión.
// No modifica tablas ni registros del negocio. Al cerrar la conexión desaparecen.
use App\Acciones\Reportes\ConsultarReportes;
use App\Acciones\Reportes\ExportarReporte;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$aplicacion = require __DIR__.'/../../bootstrap/app.php';
$aplicacion->make(Kernel::class)->bootstrap();

if (! $aplicacion->environment('local') || config('database.default') !== 'mysql' || DB::connection()->getTablePrefix() !== '') {
    fwrite(STDERR, "Esta verificación requiere entorno local, conexión mysql y sin prefijo de tablas.\n");
    exit(1);
}

$creadas = [];
try {
    DB::statement('CREATE TEMPORARY TABLE pedidos (id BIGINT UNSIGNED PRIMARY KEY, cliente_nombre VARCHAR(150) NOT NULL, total DECIMAL(12,2) NOT NULL, estado VARCHAR(30) NOT NULL, estado_logistico VARCHAR(30) NOT NULL, confirmado_en TIMESTAMP NULL) ENGINE=InnoDB');
    $creadas[] = 'pedidos';
    DB::statement('CREATE TEMPORARY TABLE detalles_pedido (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, pedido_id BIGINT UNSIGNED NOT NULL, precio DECIMAL(10,2) NOT NULL, costo DECIMAL(10,2) NOT NULL) ENGINE=InnoDB');
    $creadas[] = 'detalles_pedido';
    DB::statement('CREATE TEMPORARY TABLE pagos (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, pedido_id BIGINT UNSIGNED NOT NULL, monto DECIMAL(12,2) NOT NULL, metodo VARCHAR(40) NOT NULL, fecha_pago DATE NOT NULL, verificado_en TIMESTAMP NULL, verificado_por BIGINT UNSIGNED NULL, anulado_en TIMESTAMP NULL) ENGINE=InnoDB');
    $creadas[] = 'pagos';
    DB::statement('CREATE TEMPORARY TABLE envios (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, pedido_id BIGINT UNSIGNED NOT NULL UNIQUE, entregado_en TIMESTAMP NULL) ENGINE=InnoDB');
    $creadas[] = 'envios';
    DB::statement('CREATE TEMPORARY TABLE reembolsos (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, pedido_id BIGINT UNSIGNED NOT NULL, monto DECIMAL(12,2) NOT NULL, fecha_reembolso DATE NOT NULL, metodo VARCHAR(40) NOT NULL) ENGINE=InnoDB');
    $creadas[] = 'reembolsos';

    foreach ([1 => ['100.00', 'confirmado'], 2 => ['20.00', 'cancelado'], 3 => ['50.00', 'confirmado'], 4 => ['0.30', 'confirmado'], 5 => ['100.00', 'confirmado'], 6 => ['100.00', 'confirmado'], 7 => ['100.00', 'borrador'], 8 => ['40.00', 'confirmado']] as $id => [$total, $estado]) {
        DB::table('pedidos')->insert(['id' => $id, 'cliente_nombre' => $id === 1 ? '=1+1' : 'Cliente de prueba', 'total' => $total, 'estado' => $estado, 'estado_logistico' => $id === 8 ? 'enviado' : 'entregado', 'confirmado_en' => $estado === 'borrador' ? null : '2026-11-01 09:00:00']);
    }
    DB::table('detalles_pedido')->insert([
        ['pedido_id' => 1, 'precio' => '60.00', 'costo' => '20.00'],
        ['pedido_id' => 1, 'precio' => '40.00', 'costo' => '10.00'],
        ['pedido_id' => 4, 'precio' => '0.10', 'costo' => '0.11'],
        ['pedido_id' => 4, 'precio' => '0.20', 'costo' => '0.20'],
        ['pedido_id' => 8, 'precio' => '40.00', 'costo' => '10.00'],
    ]);
    DB::table('envios')->insert([['pedido_id' => 1, 'entregado_en' => '2026-11-03 09:00:00'], ['pedido_id' => 4, 'entregado_en' => '2026-11-02 09:00:00']]);
    DB::table('reembolsos')->insert([['pedido_id' => 2, 'monto' => '5.00', 'fecha_reembolso' => '2026-11-03', 'metodo' => 'efectivo'], ['pedido_id' => 2, 'monto' => '5.00', 'fecha_reembolso' => '2026-12-01', 'metodo' => 'efectivo']]);
    foreach ([[1, '30.00', '2026-10-31'], [1, '70.00', '2026-11-02'], [2, '20.00', '2026-11-02'], [3, '5.00', '2026-11-02'], [4, '0.10', '2026-11-02'], [4, '0.20', '2026-11-02'], [5, '100.00', '2026-11-02'], [6, '100.00', '2026-11-02'], [7, '100.00', '2026-11-02'], [8, '40.00', '2026-11-02']] as [$id, $monto, $fecha]) {
        DB::table('pagos')->insert(['pedido_id' => $id, 'monto' => $monto, 'metodo' => 'efectivo', 'fecha_pago' => $fecha,
            'verificado_por' => $id === 5 ? null : 1, 'verificado_en' => $id === 5 ? null : '2026-11-02 12:00:00', 'anulado_en' => $id === 6 ? '2026-11-03 10:00:00' : null]);
    }
    $filtros = ['desde' => '2026-11-01', 'hasta' => '2026-11-30', 'agrupacion' => 'diaria'];
    $consulta = app(ConsultarReportes::class);
    $resumen = DB::transaction(fn () => $consulta->resumen($filtros));
    $esperado = ['pedidos' => 2, 'ventas' => 10030, 'costos' => 3031, 'ganancia' => 6999, 'cobros' => 23530, 'reembolsos' => 500, 'cobro_neto' => 23030];
    if ($resumen['totales'] !== $esperado) {
        throw new RuntimeException('Los totales MySQL no coinciden: '.json_encode($resumen['totales']));
    }
    $octubre = $consulta->resumen(array_replace($filtros, ['desde' => '2026-10-01', 'hasta' => '2026-10-31']));
    if ($octubre['totales']['cobros'] !== 3000 || $octubre['totales']['ventas'] !== 0) {
        throw new RuntimeException('No se separaron correctamente cobros y ventas entre meses.');
    }
    $semanal = $consulta->resumen(array_replace($filtros, ['agrupacion' => 'semanal']));
    $mensual = $consulta->resumen(array_replace($filtros, ['agrupacion' => 'mensual']));
    if ($semanal['totales'] !== $esperado || $mensual['totales'] !== $esperado || $semanal['periodos'][0]['desde'] !== '2026-11-02') {
        throw new RuntimeException('La agrupación cambió los totales o el inicio de semana.');
    }
    $paginacion = $consulta->ventas($filtros)->orderBy('pedido_id')->paginate(20);
    if ($paginacion->total() !== 2) {
        throw new RuntimeException('La consulta paginada duplicó ventas.');
    }
    if ($consulta->totalesHistoricos() !== ['pedidos' => 2, 'ventas' => 10030, 'ganancia' => 6999]) {
        throw new RuntimeException('Dashboard y reportes no comparten el criterio de venta entregada y pagada.');
    }
    if ($consulta->resumen(array_replace($filtros, ['desde' => '2026-11-02', 'hasta' => '2026-11-02']))['totales']['ventas'] !== 30) {
        throw new RuntimeException('La venta se reconoció antes de la entrega física.');
    }
    $diciembre = $consulta->resumen(array_replace($filtros, ['desde' => '2026-12-01', 'hasta' => '2026-12-31']));
    if ($diciembre['totales']['reembolsos'] !== 500 || $diciembre['totales']['cobro_neto'] !== -500) {
        throw new RuntimeException('No se agrupó el reembolso por su fecha ni se conservó el cobro neto negativo.');
    }
    ob_start();
    try {
        app(ExportarReporte::class)->descargar('ventas', $filtros)->sendContent();
        $csv = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    if (! str_starts_with($csv, "\xEF\xBB\xBF") || ! str_contains($csv, "'=1+1") || ! str_contains($csv, '-0.01')) {
        throw new RuntimeException('El CSV no conservó BOM, protección de fórmulas o pérdida exacta.');
    }
    foreach (['resumen', 'reembolsos'] as $tipo) {
        ob_start();
        try {
            app(ExportarReporte::class)->descargar($tipo, array_replace($filtros, ['agrupacion' => 'mensual']))->sendContent();
            $csv = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $esperadosCsv = $tipo === 'resumen' ? ['Reembolsos (Q)', 'Cobro neto (Q)', '230.30'] : ['Fecha de reembolso', '2026-11-03', '5.00'];
        foreach ($esperadosCsv as $valor) {
            if (! str_contains($csv, $valor)) {
                throw new RuntimeException('El CSV '.$tipo.' no contiene el resultado esperado: '.$valor);
            }
        }
    }
    fwrite(STDOUT, 'OK MySQL/MariaDB: ventas entregadas y pagadas, cobros, reembolsos, neto, agrupaciones, paginación y CSV. Totales (centavos): '.json_encode($esperado)."\n");
} catch (Throwable $excepcion) {
    $fallo = $excepcion;
} finally {
    foreach (array_reverse($creadas) as $tabla) {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS '.$tabla);
    }
}
if (isset($fallo)) {
    fwrite(STDERR, 'Error al verificar reportes: '.$fallo->getMessage()."\n");
    exit(1);
}
