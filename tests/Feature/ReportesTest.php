<?php

namespace Tests\Feature;

use App\Acciones\Reportes\ConsultarReportes;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Soporte\ImporteReporte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 11, 15)->setTime(12, 0));
        $this->operador = User::factory()->create();
    }

    private function pedido(array $cambios = [], array $piezas = [['60.00', '20.00'], ['40.00', '10.00']]): Pedido
    {
        $cliente = Cliente::create(['nombre' => 'Nombre actual', 'telefono' => '55551234']);
        $pedido = Pedido::create(array_replace([
            'referencia' => (string) Str::uuid(), 'cliente_id' => $cliente->id, 'usuario_id' => $this->operador->id,
            'cliente_nombre' => 'Cliente histórico', 'cliente_telefono' => '55551234', 'modalidad' => 'contra_entrega',
            'total' => '100.00', 'estado' => 'confirmado', 'confirmado_en' => '2026-11-01 09:00:00',
        ], $cambios));
        foreach ($piezas as [$precio, $costo]) {
            $producto = Producto::create(['codigo' => 'R-'.Str::random(12), 'nombre' => 'Pieza actual', 'precio' => $precio, 'costo' => $costo]);
            $pedido->detalles()->create(['producto_id' => $producto->id, 'codigo' => $producto->codigo, 'nombre' => 'Pieza histórica', 'precio' => $precio, 'costo' => $costo, 'version_producto' => 1]);
        }

        return $pedido;
    }

    private function abono(Pedido $pedido, array $cambios = []): Pago
    {
        return $pedido->pagos()->create(array_replace([
            'usuario_id' => $this->operador->id, 'clave_operacion' => (string) Str::uuid(), 'monto' => '100.00',
            'metodo' => 'efectivo', 'fecha_pago' => '2026-11-02', 'verificado_por' => $this->operador->id,
            'verificado_en' => '2026-11-02 12:00:00',
        ], $cambios));
    }

    private function filtros(array $cambios = []): array
    {
        return array_replace(['desde' => '2026-11-01', 'hasta' => '2026-11-30', 'agrupacion' => 'diaria'], $cambios);
    }

    private function resumen(array $cambios = []): array
    {
        return app(ConsultarReportes::class)->resumen($this->filtros($cambios));
    }

    private function csv(string $tipo, array $cambios = []): array
    {
        $respuesta = $this->get(route('reportes.exportar', array_merge($this->filtros(), ['tipo' => $tipo], $cambios)))
            ->assertOk()->assertDownload();
        $contenido = $respuesta->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $archivo = fopen('php://temp', 'w+');
        fwrite($archivo, substr($contenido, 3));
        rewind($archivo);
        $filas = [];
        while (($fila = fgetcsv($archivo, 0, ',', '"', '')) !== false) {
            $filas[] = $fila;
        }
        fclose($archivo);

        return $filas;
    }

    public function test_acceso_y_validacion_de_fechas_agrupaciones_y_exportaciones(): void
    {
        $this->get('/reportes')->assertRedirect(route('login'));
        $this->get('/reportes/exportar.csv?tipo=ventas')->assertRedirect(route('login'));
        $this->actingAs($this->operador);
        foreach ([['desde' => '2026-02-30'], ['desde' => 'no-fecha'], ['desde' => ['2026-11-01']], ['hasta' => '2026-10-01'], ['agrupacion' => 'anual'], ['ventas_pagina' => '-1']] as $invalido) {
            $campo = array_key_first($invalido);
            $this->get(route('reportes.index', $this->filtros($invalido)))->assertSessionHasErrors($campo);
        }
        $this->get('/reportes/exportar.csv')->assertSessionHasErrors('tipo');
        $this->get('/reportes/exportar.csv?tipo=sql')->assertSessionHasErrors('tipo');
        $this->from('/reportes')->get('/reportes?desde[]=2026-11-01')->assertRedirect('/reportes');
        $this->get('/reportes')->assertOk()->assertSee('Revisa las fechas');
    }

    public function test_mes_predeterminado_y_periodo_vacio(): void
    {
        $this->actingAs($this->operador)->get('/reportes')->assertOk()
            ->assertViewHas('filtros', $this->filtros())->assertSee('No hay ventas ni cobros verificados')
            ->assertViewHas('resumen', fn ($resumen) => $resumen['totales'] === ['pedidos' => 0, 'ventas' => 0, 'costos' => 0, 'ganancia' => 0, 'cobros' => 0]);
        $this->assertCount(1, $this->csv('resumen'));
        $this->assertCount(1, $this->csv('ventas'));
        $this->assertCount(1, $this->csv('cobros'));
    }

    public function test_pagos_y_piezas_no_multiplican_las_ventas_y_conservan_historial(): void
    {
        $pedido = $this->pedido();
        $this->abono($pedido, ['monto' => '30.00', 'verificado_en' => '2026-11-01 10:00:00']);
        $this->assertSame(0, $this->resumen()['totales']['ventas']);
        $this->abono($pedido, ['monto' => '70.00']);
        $pedido->cliente->update(['nombre' => 'Cliente modificado']);
        $pedido->cliente->delete();
        Producto::query()->update(['precio' => '999.00', 'costo' => '888.00']);
        $this->assertSame(['pedidos' => 1, 'ventas' => 10000, 'costos' => 3000, 'ganancia' => 7000, 'cobros' => 10000], $this->resumen()['totales']);
        $this->actingAs($this->operador)->get('/reportes')->assertOk()->assertSee('Cliente histórico')->assertDontSee('Cliente modificado');
    }

    public function test_excluye_pendientes_anulados_borradores_cancelados_y_gratuitos(): void
    {
        $pendiente = $this->pedido();
        $this->abono($pendiente, ['verificado_en' => null, 'verificado_por' => null]);
        $anulado = $this->pedido();
        $this->abono($anulado, ['anulado_en' => '2026-11-03 10:00:00']);
        $sinVerificador = $this->pedido();
        $this->abono($sinVerificador, ['verificado_por' => null]);
        $borrador = $this->pedido(['estado' => 'borrador', 'confirmado_en' => null, 'modalidad' => 'deposito']);
        $this->abono($borrador);
        $cancelado = $this->pedido(['estado' => 'cancelado', 'cancelado_en' => '2026-11-03 10:00:00']);
        $this->abono($cancelado);
        $this->pedido(['total' => '0.00'], [['0.00', '0.00']]);
        $this->assertSame(['pedidos' => 0, 'ventas' => 0, 'costos' => 0, 'ganancia' => 0, 'cobros' => 20000], $this->resumen()['totales']);
        $this->actingAs($this->operador)->get('/reportes')->assertOk()->assertSee('Cancelado');
    }

    public function test_venta_por_verificacion_y_cobros_por_fecha_de_pago_en_otro_mes(): void
    {
        $pedido = $this->pedido();
        $this->abono($pedido, ['monto' => '20.00', 'fecha_pago' => '2026-10-30', 'verificado_en' => '2026-11-01 10:00:00']);
        $this->abono($pedido, ['monto' => '80.00', 'fecha_pago' => '2026-10-31', 'verificado_en' => '2026-11-02 12:00:00']);
        $noviembre = $this->resumen()['totales'];
        $octubre = $this->resumen(['desde' => '2026-10-01', 'hasta' => '2026-10-31'])['totales'];
        $this->assertSame(10000, $noviembre['ventas']);
        $this->assertSame(0, $noviembre['cobros']);
        $this->assertSame(0, $octubre['ventas']);
        $this->assertSame(10000, $octubre['cobros']);
        $this->assertSame(0, $this->resumen(['desde' => '2026-11-01', 'hasta' => '2026-11-01'])['totales']['ventas']);
        $this->assertSame(10000, $this->resumen(['desde' => '2026-11-02', 'hasta' => '2026-11-02'])['totales']['ventas']);
    }

    public function test_no_reconoce_una_venta_antes_de_confirmar_y_cancelar_recalcula(): void
    {
        $pedido = $this->pedido(['confirmado_en' => '2026-11-05 09:00:00']);
        $this->abono($pedido);
        $this->assertSame(0, $this->resumen(['hasta' => '2026-11-04'])['totales']['ventas']);
        $this->assertSame(10000, $this->resumen(['desde' => '2026-11-05', 'hasta' => '2026-11-05'])['totales']['ventas']);
        $pedido->update(['estado' => 'cancelado', 'cancelado_en' => '2026-11-10 10:00:00']);
        $this->assertSame(0, $this->resumen()['totales']['ganancia']);
        $this->assertSame(10000, $this->resumen()['totales']['cobros']);
    }

    public function test_fronteras_inclusivas_y_semanas_lunes_a_domingo_recortadas(): void
    {
        foreach (['2026-10-31 23:59:59', '2026-11-01 00:00:00', '2026-11-01 23:59:59', '2026-11-02 00:00:00', '2026-12-01 00:00:00'] as $fecha) {
            $pedido = $this->pedido(['confirmado_en' => $fecha]);
            $this->abono($pedido, ['verificado_en' => $fecha, 'fecha_pago' => substr($fecha, 0, 10)]);
        }
        $diario = $this->resumen();
        $this->assertSame(3, $diario['totales']['pedidos']);
        $this->assertCount(2, $diario['periodos']);
        $semana = $this->resumen(['agrupacion' => 'semanal']);
        $this->assertSame(['2026-11-01', '2026-11-02'], array_column($semana['periodos'], 'desde'));
        $this->assertSame(['2026-11-01', '2026-11-08'], array_column($semana['periodos'], 'hasta'));
        $mes = $this->resumen(['agrupacion' => 'mensual', 'desde' => '2026-10-31', 'hasta' => '2026-11-02']);
        $this->assertSame(['2026-10-31', '2026-11-01'], array_column($mes['periodos'], 'desde'));
        $this->assertSame(['2026-10-31', '2026-11-02'], array_column($mes['periodos'], 'hasta'));
    }

    public function test_centavos_exactos_perdida_y_acumulados_grandes(): void
    {
        $pedido = $this->pedido(['total' => '0.30'], [['0.10', '0.11'], ['0.20', '0.20']]);
        $this->abono($pedido, ['monto' => '0.10']);
        $this->abono($pedido, ['monto' => '0.20']);
        $this->assertSame(['pedidos' => 1, 'ventas' => 30, 'costos' => 31, 'ganancia' => -1, 'cobros' => 30], $this->resumen()['totales']);
        $this->actingAs($this->operador)->get('/reportes')->assertOk()->assertSee('Q -0.01');
        for ($i = 0; $i < 2; $i++) {
            $grande = $this->pedido(['total' => '99999999.99'], [['99999999.99', '0.00']]);
            $this->abono($grande, ['monto' => '99999999.99']);
        }
        $this->assertSame(20000000028, $this->resumen()['totales']['ventas']);
        $this->assertSame('12000000000.00', ImporteReporte::decimal(1200000000000));
    }

    public function test_paginacion_independiente_y_csv_completo_coinciden_con_resumen(): void
    {
        $this->actingAs($this->operador);
        for ($i = 0; $i < 21; $i++) {
            $this->abono($this->pedido());
        }
        $this->get(route('reportes.index', array_merge($this->filtros(), ['ventas_pagina' => 2, 'cobros_pagina' => 1])))
            ->assertOk()->assertViewHas('ventas', fn ($ventas) => $ventas->count() === 1 && $ventas->total() === 21)
            ->assertViewHas('cobros', fn ($cobros) => $cobros->count() === 20 && $cobros->total() === 21)
            ->assertSee('desde=2026-11-01', false)->assertSee('agrupacion=diaria', false);
        $ventas = $this->csv('ventas', ['ventas_pagina' => 2]);
        $cobros = $this->csv('cobros', ['cobros_pagina' => 2]);
        $resumen = $this->csv('resumen');
        $this->assertCount(22, $ventas);
        $this->assertCount(22, $cobros);
        $this->assertSame('2100.00', $resumen[1][3]);
        $this->assertSame('2100.00', $resumen[1][6]);
        $this->assertSame(21, $this->resumen()['totales']['pedidos']);
    }

    public function test_csv_protege_formulas_y_conserva_comillas_acentos_y_saltos(): void
    {
        $this->actingAs($this->operador);
        foreach (['=1+1', '  +SUM(1)', '-CMD()', '@SUM(1)', "\t=1+1", 'Ángela, "López"'."\n".'Línea dos'] as $nombre) {
            $this->abono($this->pedido(['cliente_nombre' => $nombre]));
        }
        $ventas = $this->csv('ventas');
        $cobros = $this->csv('cobros');
        foreach (array_slice($ventas, 1, 5) as $indice => $fila) {
            $this->assertStringStartsWith("'", $fila[1]);
            $this->assertSame($fila[1], $cobros[$indice + 1][3]);
        }
        $this->assertSame('Ángela, "López"'."\n".'Línea dos', $ventas[6][1]);
        $this->get('/reportes')->assertOk()->assertSee('Ángela');
    }
}
