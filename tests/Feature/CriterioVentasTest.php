<?php

namespace Tests\Feature;

use App\Acciones\Inventario\GestionarInventario;
use App\Acciones\Pagos\ConsultarPedidos;
use App\Acciones\Pagos\RegistrarPago;
use App\Acciones\Pagos\RegistrarReembolso;
use App\Acciones\Pedidos\GestionarPedidos;
use App\Acciones\Reportes\ConsultarReportes;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CriterioVentasTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 11, 10)->setTime(10, 0));
        $this->usuario = User::factory()->create();
        $this->actingAs($this->usuario);
    }

    private function pedido(string $precio = '100.00', string $costo = '40.00'): Pedido
    {
        $cliente = Cliente::create(['nombre' => 'Cliente ventas', 'telefono' => '55554444']);
        $pieza = app(GestionarInventario::class)->registrar(['codigo' => 'CV-'.(Producto::count() + 1), 'nombre' => 'Pieza', 'costo' => $costo, 'precio' => $precio, 'existencias' => 1], $this->usuario);
        $pedido = app(GestionarPedidos::class)->guardar(['cliente_id' => $cliente->id, 'modalidad' => 'contra_entrega', 'productos' => [$pieza->id], 'referencia' => (string) Str::uuid()], $this->usuario);
        app(GestionarPedidos::class)->confirmar($pedido, $pedido->version, $this->usuario);

        return $pedido->refresh();
    }

    private function pagar(Pedido $pedido, string $importe): void
    {
        $pago = app(RegistrarPago::class)->registrar($pedido->id, ['importe' => $importe, 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()], $this->usuario);
        app(RegistrarPago::class)->verificar($pago, $this->usuario);
    }

    private function entregar(Pedido $pedido): void
    {
        $this->post(route('entregas.empacar', $pedido), ['version' => $pedido->refresh()->version])->assertSessionHasNoErrors();
        $this->post(route('entregas.enviar', $pedido), ['version' => $pedido->refresh()->version, 'transportista' => 'mensajeria_propia', 'destinatario' => 'Cliente', 'telefono' => '55554444', 'direccion' => 'Zona 1'])->assertSessionHasNoErrors();
        $this->post(route('entregas.entregar', $pedido), ['version' => $pedido->refresh()->version, 'confirmar_entrega' => 1])->assertSessionHasNoErrors();
    }

    private function filtros(): array
    {
        return ['desde' => '2026-11-01', 'hasta' => '2026-11-30', 'agrupacion' => 'mensual'];
    }

    private function ventasPanel(): string
    {
        return $this->get('/panel')->assertOk()->viewData('resumenVentas')->total_ventas;
    }

    public function test_entregado_sin_pagar_no_es_venta_en_ninguno(): void
    {
        $pedido = $this->pedido();
        $this->pagar($pedido, '30.00');
        $this->entregar($pedido);

        $this->assertSame(0, app(ConsultarReportes::class)->resumen($this->filtros())['totales']['ventas']);
        $this->assertSame('0.00', $this->ventasPanel());
    }

    public function test_pagado_sin_entregar_no_es_venta_en_ninguno(): void
    {
        $pedido = $this->pedido();
        $this->pagar($pedido, '100.00');

        $this->assertSame(0, app(ConsultarReportes::class)->resumen($this->filtros())['totales']['ventas']);
        $this->assertSame('0.00', $this->ventasPanel());
    }

    public function test_entregado_y_pagado_es_venta_igual_en_panel_y_reportes_con_fecha_de_entrega(): void
    {
        $pedido = $this->pedido('100.00', '40.00');
        $this->pagar($pedido, '100.00');
        $this->travelTo(now()->setDate(2026, 11, 12)->setTime(15, 30));
        $this->entregar($pedido);

        $resumen = app(ConsultarReportes::class)->resumen($this->filtros());
        $this->assertSame(1, $resumen['totales']['pedidos']);
        $this->assertSame(10000, $resumen['totales']['ventas']);
        $this->assertSame(6000, $resumen['totales']['ganancia']);
        $this->assertSame('100.00', $this->ventasPanel());
        $venta = app(ConsultarReportes::class)->ventas($this->filtros())->sole();
        $this->assertStringStartsWith('2026-11-12', (string) $venta->fecha_venta, 'La venta se fecha al completar la última condición: la entrega.');
    }

    public function test_pago_que_se_completa_despues_de_la_entrega_fecha_la_venta_ese_dia(): void
    {
        $pedido = $this->pedido();
        $this->pagar($pedido, '30.00');
        $this->entregar($pedido);
        $this->travelTo(now()->setDate(2026, 11, 20)->setTime(9, 0));
        $this->pagar($pedido, '70.00');

        $venta = app(ConsultarReportes::class)->ventas($this->filtros())->sole();
        $this->assertStringStartsWith('2026-11-20', (string) $venta->fecha_venta);
        $this->assertSame(0, app(ConsultarPedidos::class)->resumen($pedido->refresh())['saldo']);
    }

    public function test_reembolsos_se_restan_en_el_cobro_neto_y_salen_en_csv(): void
    {
        $pedido = $this->pedido();
        $this->pagar($pedido, '60.00');
        app(GestionarPedidos::class)->cancelar($pedido->refresh(), $pedido->version, 'Cliente desistió', $this->usuario);
        app(RegistrarReembolso::class)->registrar($pedido->id, ['importe' => '50.00', 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'motivo' => 'Se retienen Q 10', 'clave_operacion' => (string) Str::uuid()], $this->usuario);

        $totales = app(ConsultarReportes::class)->resumen($this->filtros())['totales'];
        $this->assertSame([6000, 5000, 1000], [$totales['cobros'], $totales['reembolsos'], $totales['cobro_neto']]);
        $this->assertSame(0, $totales['ventas']);

        $csv = $this->get(route('reportes.exportar', $this->filtros() + ['tipo' => 'reembolsos']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Fecha de reembolso', $csv);
        $this->assertStringContainsString('50.00', $csv);
        $resumen = $this->get(route('reportes.exportar', $this->filtros() + ['tipo' => 'resumen']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Cobro neto (Q)', $resumen);
        $this->get(route('reportes.index', $this->filtros()))->assertOk()->assertSee('Cobro neto')->assertSee('Q 10.00');
    }
}
