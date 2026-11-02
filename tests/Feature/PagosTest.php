<?php

namespace Tests\Feature;

use App\Acciones\Inventario\GestionarInventario;
use App\Acciones\Pedidos\GestionarPedidos;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    // Mantiene las pruebas originales, ahora sobre pedidos reales y su esquema definitivo.
    private function pedido(string $total = '100.00', string $estado = 'confirmado', string $modalidad = 'contra_entrega'): int
    {
        $usuario = auth()->user() ?? User::factory()->create();
        $cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'telefono' => '55551234']);
        $pieza = app(GestionarInventario::class)->registrar(['codigo' => 'PG-'.(Producto::count() + 1), 'nombre' => 'Pieza de prueba', 'costo' => '0.00', 'precio' => $total, 'existencias' => 1], $usuario);
        $pedido = app(GestionarPedidos::class)->guardar(['cliente_id' => $cliente->id, 'modalidad' => $modalidad, 'productos' => [$pieza->id], 'referencia' => (string) Str::uuid()], $usuario);
        if ($estado !== 'borrador') {
            app(GestionarPedidos::class)->confirmar($pedido, $pedido->version, $usuario);
        }
        if ($estado === 'cancelado') {
            app(GestionarPedidos::class)->cancelar($pedido->refresh(), $pedido->version, 'Cancelación de prueba', $usuario);
        }

        return $pedido->id;
    }

    private function datos(array $cambios = []): array
    {
        return array_replace(['importe' => '25.50', 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()], $cambios);
    }

    private function verificarUltimo(): void
    {
        $pago = Pago::orderByDesc('id')->firstOrFail();
        $this->post(route('pagos.verificar', $pago), ['confirmar_verificacion' => 1])->assertSessionHasNoErrors();
    }

    public function test_visitantes_no_pueden_consultar_ni_registrar(): void
    {
        $this->get('/pagos')->assertRedirect(route('login'));
        $this->get('/pagos/pedidos/1')->assertRedirect(route('login'));
        $this->post('/pagos/pedidos/1', $this->datos())->assertRedirect(route('login'));
        $this->post('/pagos/abonos/1/verificar', [])->assertRedirect(route('login'));
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_sin_pedidos_muestra_listado_vacio_y_no_inventa_pedidos(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/pagos')->assertOk()->assertSee('No hay pedidos que mostrar');
        $this->get('/pagos/pedidos/1')->assertNotFound();
        $this->post('/pagos/pedidos/1', $this->datos())->assertNotFound();
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_registra_y_verifica_abonos_y_calcula_estados_sin_redondeo(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $pedido = $this->pedido('0.30');
        $this->get('/pagos')->assertOk()->assertSee('Pendiente');
        $this->get("/pagos/pedidos/$pedido")->assertOk()->assertSee('Registrar abono recibido');
        $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => '0.10']))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('pagos', ['pedido_id' => $pedido, 'usuario_id' => $usuario->id, 'monto' => '0.10', 'verificado_en' => null]);
        $this->assertSame(0, Pedido::findOrFail($pedido)->pagadoCentavos());
        $this->verificarUltimo();
        $this->get('/pagos')->assertOk()->assertSee('Parcial')->assertSee('Q 0.20');
        $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => '0.20']))->assertSessionHasNoErrors();
        $this->verificarUltimo();
        $this->get("/pagos/pedidos/$pedido")->assertOk()->assertSee('El pedido está pagado.')->assertSee($usuario->name);
        $this->get('/pagos')->assertOk()->assertSee('Pagado')->assertSee('Q 0.00');
    }

    public function test_reenvio_no_duplica_y_pendientes_tambien_impiden_sobrepago(): void
    {
        $this->actingAs(User::factory()->create());
        $pedido = $this->pedido('50.00');
        $datos = $this->datos(['importe' => '40.00']);
        $this->post("/pagos/pedidos/$pedido", $datos)->assertSessionHasNoErrors();
        $this->post("/pagos/pedidos/$pedido", $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pagos', 1);
        $this->post("/pagos/pedidos/$pedido", array_replace($datos, ['importe' => '5.00']))->assertSessionHasErrors('clave_operacion');
        $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => '10.01']))->assertSessionHasErrors('importe');
        $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => '10.00']))->assertSessionHasNoErrors();
        $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => '0.01']))->assertSessionHasErrors('importe');
        $this->assertDatabaseCount('pagos', 2);
        $this->assertSame(0, Pedido::findOrFail($pedido)->pagadoCentavos());
    }

    public function test_rechaza_cancelados_inexistentes_y_datos_invalidos(): void
    {
        $this->actingAs(User::factory()->create());
        $pedido = $this->pedido('100.00', 'cancelado');
        $this->post("/pagos/pedidos/$pedido", $this->datos())->assertSessionHasErrors('importe');
        $this->post('/pagos/pedidos/999', $this->datos())->assertNotFound();
        foreach (['-1', '0', '1.001', '1e2', '100000000000', ['10']] as $importe) {
            $this->post("/pagos/pedidos/$pedido", $this->datos(['importe' => $importe]))->assertSessionHasErrors('importe');
        }
        $this->post("/pagos/pedidos/$pedido", $this->datos(['metodo' => 'transferencia', 'fecha' => now()->addDay()->toDateString(), 'clave_operacion' => 'incorrecta']))
            ->assertSessionHasErrors(['referencia', 'fecha', 'clave_operacion']);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_transferencias_historial_escape_html_y_cliente_de_baja(): void
    {
        $this->actingAs(User::factory()->create());
        $pedido = $this->pedido();
        $this->post("/pagos/pedidos/$pedido", $this->datos(['metodo' => 'transferencia', 'referencia' => 'BANCO-123', 'observaciones' => '<script>alert(1)</script>']))->assertSessionHasNoErrors();
        $this->get("/pagos/pedidos/$pedido")->assertOk()->assertSee('BANCO-123')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $cliente = Cliente::firstOrFail();
        $cliente->delete();
        $this->get("/pagos/pedidos/$pedido")->assertOk()->assertSee($cliente->nombre);
    }

    public function test_busqueda_paginacion_y_referencia_ajena(): void
    {
        $this->actingAs(User::factory()->create());
        for ($numero = 0; $numero < 11; $numero++) {
            $this->pedido();
        }
        $this->get('/pagos?buscar=Cliente')->assertOk()->assertSee('buscar=Cliente', false)->assertViewHas('pedidos', fn ($pedidos) => $pedidos->count() === 10);
        $this->get('/pagos?buscar=Cliente&page=2')->assertOk()->assertViewHas('pedidos', fn ($pedidos) => $pedidos->count() === 1);
        $datos = $this->datos();
        $this->post('/pagos/pedidos/1', $datos)->assertSessionHasNoErrors();
        $this->post('/pagos/pedidos/2', $datos)->assertSessionHasErrors('clave_operacion');
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_deposito_en_borrador_exige_verificacion_y_25_por_ciento_para_confirmar(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $id = $this->pedido('100.01', 'borrador', 'deposito');
        $datos = $this->datos(['importe' => '25.01', 'verificado_en' => now(), 'verificado_por' => 999]);
        $this->post("/pagos/pedidos/$id", $datos)->assertSessionHasNoErrors();
        $pago = Pago::firstOrFail();
        $pedido = Pedido::findOrFail($id);
        $this->assertNull($pago->verificado_en);
        $this->post(route('pedidos.confirmar', $pedido), ['version' => $pedido->version])->assertSessionHasErrors('pedido');
        $this->post(route('pagos.verificar', $pago), [])->assertSessionHasErrors('confirmar_verificacion');
        $this->verificarUltimo();
        $pedido->refresh();
        $this->post(route('pedidos.confirmar', $pedido), ['version' => $pedido->version])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservas', ['pedido_id' => $id, 'tipo' => 'firme']);
        $this->assertDatabaseHas('pagos', ['id' => $pago->id, 'verificado_por' => $usuario->id]);
    }

    public function test_verificar_promueve_temporal_una_sola_vez_y_cancelacion_bloquea_pendientes(): void
    {
        $this->actingAs(User::factory()->create());
        $id = $this->pedido();
        $pedido = Pedido::findOrFail($id);
        $this->post("/pagos/pedidos/$id", $this->datos(['importe' => '25.00']))->assertSessionHasNoErrors();
        $pago = Pago::firstOrFail();
        $this->verificarUltimo();
        $this->verificarUltimo();
        $this->assertDatabaseHas('reservas', ['pedido_id' => $id, 'tipo' => 'firme']);
        $this->assertSame(1, $pedido->eventos()->where('tipo', 'pago_verificado')->count());
        $this->post("/pagos/pedidos/$id", $this->datos(['importe' => '10.00']))->assertSessionHasNoErrors();
        $pendiente = Pago::orderByDesc('id')->firstOrFail();
        $pedido->refresh();
        $this->post(route('pedidos.cancelar', $pedido), ['version' => $pedido->version, 'motivo' => 'Cancelación'])->assertSessionHasNoErrors();
        $this->post(route('pagos.verificar', $pendiente), ['confirmar_verificacion' => 1])->assertSessionHasErrors('importe');
        $this->assertNull($pendiente->refresh()->verificado_en);
        $this->assertDatabaseCount('pagos', 2);
    }

    public function test_contra_entrega_en_borrador_no_recibe_abonos(): void
    {
        $this->actingAs(User::factory()->create());
        $id = $this->pedido('100.00', 'borrador');
        $this->post("/pagos/pedidos/$id", $this->datos())->assertSessionHasErrors('importe');
        $this->assertDatabaseCount('pagos', 0);
    }
}
