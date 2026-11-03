<?php

namespace Tests\Feature;

use App\Acciones\Inventario\GestionarInventario;
use App\Acciones\Pagos\RegistrarPago;
use App\Acciones\Pedidos\GestionarPedidos;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Reembolso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReembolsosTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->administrador = User::factory()->create();
        $this->actingAs($this->administrador);
    }

    /** Pedido contra entrega de Q 100 con abonos verificados por los importes dados. */
    private function pedido(array $abonos = ['60.00']): Pedido
    {
        $cliente = Cliente::create(['nombre' => 'Cliente reembolso', 'telefono' => '55557777']);
        $pieza = app(GestionarInventario::class)->registrar(['codigo' => 'RB-'.(Producto::count() + 1), 'nombre' => 'Pieza', 'costo' => '30.00', 'precio' => '100.00', 'existencias' => 1], $this->administrador);
        $pedido = app(GestionarPedidos::class)->guardar(['cliente_id' => $cliente->id, 'modalidad' => 'contra_entrega', 'productos' => [$pieza->id], 'referencia' => (string) Str::uuid()], $this->administrador);
        app(GestionarPedidos::class)->confirmar($pedido, $pedido->version, $this->administrador);
        foreach ($abonos as $importe) {
            $pago = app(RegistrarPago::class)->registrar($pedido->id, ['importe' => $importe, 'metodo' => 'transferencia', 'referencia' => 'BI-'.Str::random(5), 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()], $this->administrador);
            app(RegistrarPago::class)->verificar($pago, $this->administrador);
        }

        return $pedido->refresh();
    }

    private function cancelar(Pedido $pedido): Pedido
    {
        app(GestionarPedidos::class)->cancelar($pedido->refresh(), $pedido->version, 'Cliente desistió', $this->administrador);

        return $pedido->refresh();
    }

    private function datos(array $cambios = []): array
    {
        return array_replace(['importe' => '60.00', 'metodo' => 'transferencia', 'referencia' => 'DEV-001', 'fecha' => now()->toDateString(), 'motivo' => 'Cliente desistió del pedido', 'clave_operacion' => (string) Str::uuid()], $cambios);
    }

    public function test_reembolso_total_de_pedido_cancelado_queda_en_historial(): void
    {
        $pedido = $this->cancelar($this->pedido());

        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos())->assertRedirect(route('pagos.show', $pedido->id))->assertSessionHasNoErrors();

        $reembolso = Reembolso::sole();
        $this->assertSame('60.00', $reembolso->monto);
        $this->assertSame($this->administrador->id, (int) $reembolso->usuario_id);
        $this->assertSame(1, $pedido->pagos()->whereNull('anulado_en')->count(), 'Los abonos no se tocan.');
        $this->assertDatabaseHas('eventos_pedido', ['pedido_id' => $pedido->id, 'tipo' => 'reembolso_registrado']);
        $this->get(route('pagos.show', $pedido->id))->assertSee('Historial de reembolsos')->assertSee('Cliente desistió del pedido')->assertSee('No queda importe verificado por devolver');
    }

    public function test_reembolso_parcial_retiene_una_parte_y_admite_otro_despues(): void
    {
        $pedido = $this->cancelar($this->pedido(['30.00', '30.00']));

        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['importe' => '50.00', 'motivo' => 'Se retienen Q 10 de envío']))->assertSessionHasNoErrors();
        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['importe' => '10.01']))->assertSessionHasErrors('importe');
        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['importe' => '10.00', 'motivo' => 'Se decidió devolver el envío']))->assertSessionHasNoErrors();

        $this->assertSame(['50.00', '10.00'], Reembolso::orderBy('id')->pluck('monto')->all());
        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['importe' => '0.01']))->assertSessionHasErrors('importe');
    }

    public function test_solo_pedidos_cancelados_y_solo_lo_verificado(): void
    {
        $pedido = $this->pedido(['40.00']);
        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['importe' => '40.00']))->assertSessionHasErrors('reembolso');

        // Un abono pendiente no cuenta como dinero recibido.
        app(RegistrarPago::class)->registrar($pedido->id, ['importe' => '20.00', 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()], $this->administrador);
        $this->cancelar($pedido);
        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['importe' => '60.00']))->assertSessionHasErrors('importe');
        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['importe' => '40.00']))->assertSessionHasNoErrors();
        $this->assertSame(1, Reembolso::count());
    }

    public function test_pedido_enviado_necesita_regreso_fisico_antes_de_reembolsar(): void
    {
        $pedido = $this->pedido(['100.00']);
        $this->post(route('entregas.empacar', $pedido), ['version' => $pedido->refresh()->version])->assertSessionHasNoErrors();
        $this->post(route('entregas.enviar', $pedido), ['version' => $pedido->refresh()->version, 'transportista' => 'forza', 'guia' => 'FORZA-9', 'destinatario' => 'Cliente', 'telefono' => '55557777', 'direccion' => 'Zona 1'])->assertSessionHasNoErrors();
        $pedido = $this->cancelar($pedido);

        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['importe' => '100.00']))->assertSessionHasErrors('reembolso');
        $this->get(route('pagos.show', $pedido->id))->assertSee('Las piezas enviadas aún no regresan');

        app(GestionarPedidos::class)->confirmarDevolucion($pedido, $this->administrador);
        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['importe' => '100.00']))->assertSessionHasNoErrors();
        $this->assertSame(1, Reembolso::count());
    }

    public function test_reenvio_no_duplica_y_valida_datos(): void
    {
        $pedido = $this->cancelar($this->pedido());
        $datos = $this->datos(['importe' => '20.00']);

        $this->post(route('pagos.reembolsar', $pedido->id), $datos)->assertSessionHasNoErrors();
        $this->post(route('pagos.reembolsar', $pedido->id), $datos)->assertSessionHasNoErrors();
        $this->assertSame(1, Reembolso::count());
        $this->post(route('pagos.reembolsar', $pedido->id), array_replace($datos, ['importe' => '25.00']))->assertSessionHasErrors('clave_operacion');

        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['motivo' => '']))->assertSessionHasErrors('motivo');
        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['referencia' => '']))->assertSessionHasErrors('referencia');
        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['fecha' => now()->addDay()->toDateString()]))->assertSessionHasErrors('fecha');
        $this->post(route('pagos.reembolsar', $pedido->id), $this->datos(['importe' => '-5']))->assertSessionHasErrors('importe');
        $this->assertSame(1, Reembolso::count());
    }

    public function test_solo_el_administrador_registra_reembolsos(): void
    {
        $pedido = $this->cancelar($this->pedido());

        foreach ([User::factory()->supervisor()->create(), User::factory()->operador()->create()] as $usuario) {
            $this->actingAs($usuario);
            $this->post(route('pagos.reembolsar', $pedido->id), $this->datos())->assertForbidden();
            $this->get(route('pagos.show', $pedido->id))->assertOk()->assertSee('Solo un administrador puede registrar reembolsos')->assertDontSee('Registrar reembolso</button>', false);
        }
        $this->assertSame(0, Reembolso::count());
    }

    public function test_abono_verificado_de_pedido_cancelado_se_devuelve_con_reembolso_no_con_anulacion(): void
    {
        $pedido = $this->cancelar($this->pedido());
        $pago = $pedido->pagos()->sole();

        $this->post(route('pagos.anular', $pago), ['motivo' => 'Devolver dinero', 'confirmar_anulacion' => 1])->assertSessionHasErrors('pago');
        $this->assertNull($pago->refresh()->anulado_en);
        $this->get(route('pagos.show', $pedido->id))->assertSee('Se devuelve con un reembolso');
    }
}
