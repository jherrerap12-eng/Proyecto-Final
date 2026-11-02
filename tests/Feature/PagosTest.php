<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PagosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // Contrato de integración: fixture exclusiva de pruebas, no crea pedidos reales.
    private function pedido(string $total = '100.00', string $estado = 'confirmado'): int
    {
        if (! Schema::hasTable('pedidos')) {
            Schema::create('pedidos', function (Blueprint $tabla) {
                $tabla->id();
                $tabla->foreignId('cliente_id')->constrained('clientes');
                $tabla->decimal('total', 10, 2);
                $tabla->string('estado');
            });
        }
        $cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'telefono' => '55551234']);

        return DB::table('pedidos')->insertGetId(['cliente_id' => $cliente->id, 'total' => $total, 'estado' => $estado]);
    }

    private function datos(array $cambios = []): array
    {
        return array_replace(['importe' => '25.50', 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()], $cambios);
    }

    public function test_visitantes_no_pueden_consultar_ni_registrar(): void
    {
        $this->get('/pagos')->assertRedirect(route('login'));
        $this->get('/pagos/pedidos/1')->assertRedirect(route('login'));
        $this->post('/pagos/pedidos/1', $this->datos())->assertRedirect(route('login'));
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_sin_pedidos_muestra_integracion_pendiente_y_bloquea_el_registro(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/pagos')->assertOk()->assertSee('Integración con pedidos pendiente');
        $this->get('/pagos/pedidos/1')->assertStatus(409);
        $this->post('/pagos/pedidos/1', $this->datos())->assertStatus(409);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_registra_abonos_y_calcula_pendiente_parcial_y_pagado_sin_redondeo(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $pedido = $this->pedido('0.30');
        $this->get('/pagos')->assertOk()->assertSee('Pendiente');
        $this->get("/pagos/pedidos/$pedido")->assertOk()->assertSee('Registrar abono recibido');
        $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => '0.10']))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('pagos', ['pedido_id' => $pedido, 'usuario_id' => $usuario->id, 'importe' => '0.10']);
        $this->get('/pagos')->assertOk()->assertSee('Parcial')->assertSee('Q 0.20');
        $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => '0.20']))->assertSessionHasNoErrors()->assertRedirect();
        $this->get("/pagos/pedidos/$pedido")->assertOk()->assertSee('El pedido está pagado.')->assertSee($usuario->name);
        $this->get('/pagos')->assertOk()->assertSee('Pagado')->assertSee('Q 0.00');
    }

    public function test_reenvio_no_duplica_y_otra_operacion_no_puede_sobrepagar(): void
    {
        $this->actingAs(User::factory()->create());
        $pedido = $this->pedido('50.00');
        $datos = $this->datos(['importe' => '40.00']);
        $this->post("/pagos/pedidos/$pedido", $datos)->assertSessionHasNoErrors();
        $this->post("/pagos/pedidos/$pedido", $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pagos', 1);
        $this->post("/pagos/pedidos/$pedido", array_replace($datos, ['importe' => '5.00']))->assertSessionHasErrors('clave_operacion');
        $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => '10.01']))->assertSessionHasErrors('importe');
        $this->assertDatabaseCount('pagos', 1);
        $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => '10.00']))->assertSessionHasNoErrors();
        $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => '0.01']))->assertSessionHasErrors('importe');
        $this->assertDatabaseCount('pagos', 2);
    }

    public function test_rechaza_cancelados_inexistentes_y_datos_invalidos(): void
    {
        $this->actingAs(User::factory()->create());
        $pedido = $this->pedido('100.00', 'cancelado');
        $this->post("/pagos/pedidos/$pedido", $this->datos())->assertSessionHasErrors('importe');
        $this->post('/pagos/pedidos/999', $this->datos())->assertNotFound();
        foreach (['-1', '0', '1.001', '1e2', '100000000', ['10']] as $importe) {
            $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => $importe]))->assertSessionHasErrors('importe');
        }
        $this->post("/pagos/pedidos/$pedido", $this->datos(['metodo' => 'transferencia', 'fecha' => now()->addDay()->toDateString(), 'clave_operacion' => 'incorrecta']))
            ->assertSessionHasErrors(['referencia', 'fecha', 'clave_operacion']);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_transferencias_guardan_referencia_y_el_historial_escapa_html(): void
    {
        $this->actingAs(User::factory()->create());
        $pedido = $this->pedido();
        $this->post("/pagos/pedidos/$pedido", $this->datos(['metodo' => 'transferencia', 'referencia' => 'BANCO-123', 'observaciones' => '<script>alert(1)</script>']))->assertSessionHasNoErrors();
        $this->get("/pagos/pedidos/$pedido")->assertOk()->assertSee('BANCO-123')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $cliente = Cliente::firstOrFail();
        $cliente->delete();
        $this->get("/pagos/pedidos/$pedido")->assertOk()->assertSee($cliente->nombre);
    }

    public function test_busqueda_y_paginacion_y_referencia_ajena(): void
    {
        $this->actingAs(User::factory()->create());
        for ($i = 0; $i < 11; $i++) {
            $this->pedido();
        }
        $this->get('/pagos?buscar=Cliente')->assertOk()->assertSee('buscar=Cliente', false)->assertViewHas('pedidos', fn ($pedidos) => $pedidos->count() === 10);
        $this->get('/pagos?buscar=Cliente&page=2')->assertOk()->assertViewHas('pedidos', fn ($pedidos) => $pedidos->count() === 1);
        $datos = $this->datos();
        $this->post('/pagos/pedidos/1', $datos)->assertSessionHasNoErrors();
        $this->post('/pagos/pedidos/2', $datos)->assertSessionHasErrors('clave_operacion');
        $this->assertDatabaseCount('pagos', 1);
    }
}
