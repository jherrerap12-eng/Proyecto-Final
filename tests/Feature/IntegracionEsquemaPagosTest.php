<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class IntegracionEsquemaPagosTest extends TestCase
{
    use RefreshDatabase;

    private function prepararLegacy(bool $huerfano = false): int
    {
        // Solo base SQLite en memoria: reproduce la instalación antigua del compañero.
        Schema::drop('pagos');
        $legacy = require database_path('migrations/2026_11_02_000200_crear_tabla_pagos.php');
        $legacy->up();
        $usuario = User::factory()->create();
        $cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'telefono' => '55550000']);
        $pedido = Pedido::create(['referencia' => (string) Str::uuid(), 'cliente_id' => $cliente->id, 'usuario_id' => $usuario->id, 'cliente_nombre' => $cliente->nombre, 'cliente_telefono' => $cliente->telefono, 'total' => '100.00', 'modalidad' => 'deposito']);

        return DB::table('pagos')->insertGetId(['pedido_id' => $huerfano ? 999999 : $pedido->id, 'usuario_id' => $usuario->id, 'importe' => '25.50', 'metodo' => 'transferencia', 'referencia' => 'ORIGINAL-001', 'fecha' => '2026-11-01', 'observaciones' => 'Registro conservado', 'clave_operacion' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function integrar(): void
    {
        $migracion = require database_path('migrations/2026_11_02_000300_integrar_esquema_pagos.php');
        $migracion->up();
    }

    public function test_conserva_pago_original_y_no_lo_verifica_automaticamente(): void
    {
        $id = $this->prepararLegacy();
        $this->integrar();
        $this->integrar();
        $pago = Pago::findOrFail($id);
        $this->assertSame('25.50', $pago->monto);
        $this->assertSame('2026-11-01', $pago->fecha_pago->format('Y-m-d'));
        $this->assertSame('Registro conservado', $pago->observaciones);
        $this->assertNull($pago->verificado_en);
        $this->assertSame(0, $pago->pedido->pagadoCentavos());
        $this->assertDatabaseHas('pagos', ['id' => $id, 'importe' => '25.50', 'referencia' => 'ORIGINAL-001']);
        $this->assertTrue(collect(Schema::getForeignKeys('pagos'))->contains(fn ($clave) => in_array('pedido_id', $clave['columns'], true)));
        Pago::create(['pedido_id' => $pago->pedido_id, 'usuario_id' => $pago->usuario_id, 'monto' => '10.00', 'metodo' => 'efectivo', 'fecha_pago' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()]);
        $this->assertDatabaseCount('pagos', 2);
    }

    public function test_pago_huerfano_detiene_migracion_sin_inventar_pedidos(): void
    {
        $id = $this->prepararLegacy(true);
        try {
            $this->integrar();
            $this->fail('La migración debió detenerse por el pago huérfano.');
        } catch (RuntimeException $excepcion) {
            $this->assertStringContainsString('sin pedido válido', $excepcion->getMessage());
        }
        $this->assertDatabaseHas('pagos', ['id' => $id, 'pedido_id' => 999999, 'importe' => '25.50']);
        $this->assertFalse(Schema::hasColumn('pagos', 'monto'));
        $this->assertDatabaseCount('pedidos', 1);
    }

    public function test_sobrepago_legacy_se_detiene_sin_modificar_importe(): void
    {
        $id = $this->prepararLegacy();
        DB::table('pagos')->where('id', $id)->update(['importe' => '100.01']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('superan el total');
        $this->integrar();
    }

    public function test_migracion_legacy_no_duplica_tabla_canonica(): void
    {
        $migracion = require database_path('migrations/2026_11_02_000200_crear_tabla_pagos.php');
        $migracion->up();
        $migracion->down();
        $this->assertTrue(Schema::hasColumns('pagos', ['monto', 'fecha_pago', 'verificado_en', 'observaciones']));
    }
}
