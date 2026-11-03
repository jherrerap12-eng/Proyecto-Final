<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_vacio_responde_sin_columnas_inventadas(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create())->get('/panel')->assertOk()
            ->assertViewHas('saldosPorCobrar', '0.00')
            ->assertViewHas('resumenVentas', fn ($resumen) => $resumen->total_ventas === '0.00' && $resumen->total_ganancia === '0.00');
    }

    public function test_cifras_usan_estados_logisticos_historicos_y_pagos_verificados(): void
    {
        $this->withoutVite();
        $usuario = User::factory()->create();
        $cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'telefono' => '55550000']);
        $crear = function (string $total, string $estado, string $logistico) use ($usuario, $cliente) {
            return Pedido::create(['referencia' => (string) Str::uuid(), 'usuario_id' => $usuario->id, 'cliente_id' => $cliente->id, 'cliente_nombre' => $cliente->nombre, 'cliente_telefono' => $cliente->telefono, 'total' => $total, 'modalidad' => 'contra_entrega', 'estado' => $estado, 'estado_logistico' => $logistico]);
        };
        $entregado = $crear('100.10', 'confirmado', 'entregado');
        $empacado = $crear('50.00', 'confirmado', 'empacado');
        $crear('500.00', 'cancelado', 'entregado');
        $crear('80.00', 'borrador', 'por_preparar');
        $pieza = Producto::create(['codigo' => 'HIST-001', 'nombre' => 'Pieza', 'costo' => '90.00', 'precio' => '200.00']);
        $entregado->detalles()->create(['producto_id' => $pieza->id, 'codigo' => $pieza->codigo, 'nombre' => $pieza->nombre, 'precio' => '100.10', 'costo' => '60.05', 'version_producto' => 1]);
        foreach ([[$entregado, '25.10', true, false], [$entregado, '20.00', false, false], [$entregado, '5.00', true, true], [$empacado, '10.00', true, false]] as [$pedido, $monto, $verificado, $anulado]) {
            Pago::create(['pedido_id' => $pedido->id, 'usuario_id' => $usuario->id, 'monto' => $monto, 'metodo' => 'efectivo', 'clave_operacion' => (string) Str::uuid(), 'fecha_pago' => now()->toDateString(), 'verificado_en' => $verificado ? now() : null, 'verificado_por' => $verificado ? $usuario->id : null, 'anulado_en' => $anulado ? now() : null]);
        }
        $disponible = Producto::create(['codigo' => 'DISP-001', 'nombre' => 'Disponible', 'costo' => '1.00', 'precio' => '2.00']);
        $disponible->forceFill(['existencias' => 1])->save();
        $this->actingAs($usuario)->get('/panel')->assertOk()
            ->assertViewHas('resumenVentas', fn ($resumen) => $resumen->total_ventas === '100.10' && $resumen->total_ganancia === '40.05')
            ->assertViewHas('saldosPorCobrar', '115.00')
            ->assertViewHas('pedidosPorPreparar', 0)
            ->assertViewHas('pedidosEmpacados', 1)
            ->assertViewHas('pedidosEntregados', 1)
            ->assertViewHas('piezasDisponibles', 1);
    }
}
