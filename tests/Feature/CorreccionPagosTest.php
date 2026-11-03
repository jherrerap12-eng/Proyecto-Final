<?php

namespace Tests\Feature;

use App\Acciones\Inventario\GestionarInventario;
use App\Acciones\Pagos\RegistrarPago;
use App\Acciones\Pedidos\GestionarPedidos;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CorreccionPagosTest extends TestCase
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

    private function pedido(string $modalidad = 'contra_entrega', bool $confirmar = true): Pedido
    {
        $cliente = Cliente::create(['nombre' => 'Cliente corrección', 'telefono' => '55559999']);
        $pieza = app(GestionarInventario::class)->registrar(['codigo' => 'CP-'.(Producto::count() + 1), 'nombre' => 'Pieza', 'costo' => '10.00', 'precio' => '100.00', 'existencias' => 1], $this->administrador);
        $pedido = app(GestionarPedidos::class)->guardar(['cliente_id' => $cliente->id, 'modalidad' => $modalidad, 'productos' => [$pieza->id], 'referencia' => (string) Str::uuid()], $this->administrador);
        if ($confirmar && $modalidad === 'contra_entrega') {
            app(GestionarPedidos::class)->confirmar($pedido, $pedido->version, $this->administrador);
        }

        return $pedido->refresh();
    }

    private function abono(Pedido $pedido, string $importe, bool $verificar = false): Pago
    {
        $pago = app(RegistrarPago::class)->registrar($pedido->id, ['importe' => $importe, 'metodo' => 'transferencia', 'referencia' => 'BI-'.Str::random(6), 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()], $this->administrador);
        if ($verificar) {
            app(RegistrarPago::class)->verificar($pago, $this->administrador);
        }

        return $pago->refresh();
    }

    /** Pedido con depósito previo confirmado como reserva firme con exactamente el 25 % verificado. */
    private function pedidoFirme(string $deposito = '25.00'): array
    {
        $pedido = $this->pedido('deposito');
        $pago = $this->abono($pedido, $deposito, verificar: true);
        app(GestionarPedidos::class)->confirmar($pedido->refresh(), $pedido->version, $this->administrador);
        $this->assertSame('firme', $pedido->reservas()->value('tipo'));

        return [$pedido->refresh(), $pago->refresh()];
    }

    private function correccion(array $cambios = []): array
    {
        return array_replace(['importe' => '40.00', 'metodo' => 'transferencia', 'referencia' => 'BI-CORREGIDA', 'fecha' => now()->toDateString(), 'motivo' => 'Se digitó mal el importe', 'clave_operacion' => (string) Str::uuid()], $cambios);
    }

    public function test_anular_abono_pendiente_lo_conserva_en_el_historial(): void
    {
        $pedido = $this->pedido();
        $pago = $this->abono($pedido, '30.00');

        $this->post(route('pagos.anular', $pago), ['motivo' => 'Abono duplicado', 'confirmar_anulacion' => 1])->assertSessionHasNoErrors();

        $pago->refresh();
        $this->assertNotNull($pago->anulado_en);
        $this->assertSame($this->administrador->id, (int) $pago->anulado_por);
        $this->assertSame('Abono duplicado', $pago->motivo_anulacion);
        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseHas('eventos_pedido', ['pedido_id' => $pedido->id, 'tipo' => 'pago_anulado']);
        $this->get(route('pagos.show', $pedido->id))->assertSee('Anulado')->assertSee('Abono duplicado');
        // El importe anulado vuelve a estar disponible para registrar.
        $this->abono($pedido->refresh(), '100.00');
    }

    public function test_anulacion_exige_motivo_y_confirmacion_y_no_se_repite(): void
    {
        $pago = $this->abono($this->pedido(), '30.00');

        $this->post(route('pagos.anular', $pago), ['motivo' => '', 'confirmar_anulacion' => 1])->assertSessionHasErrors('motivo');
        $this->post(route('pagos.anular', $pago), ['motivo' => 'Abono duplicado'])->assertSessionHasErrors('confirmar_anulacion');
        $this->assertNull($pago->refresh()->anulado_en);

        $this->post(route('pagos.anular', $pago), ['motivo' => 'Abono duplicado', 'confirmar_anulacion' => 1]);
        $primera = $pago->refresh()->anulado_en;
        $this->post(route('pagos.anular', $pago), ['motivo' => 'Otro motivo', 'confirmar_anulacion' => 1])->assertSessionHasNoErrors();
        $this->assertEquals($primera, $pago->refresh()->anulado_en);
        $this->assertSame('Abono duplicado', $pago->motivo_anulacion);
        $this->assertSame(1, $pago->pedido->eventos()->where('tipo', 'pago_anulado')->count());
    }

    public function test_anular_verificado_sin_reserva_firme_recalcula_el_saldo(): void
    {
        // Borrador con depósito: todavía no aparta piezas, así que no hay reserva firme que proteger.
        $pedido = $this->pedido('deposito');
        $pago = $this->abono($pedido, '60.00', verificar: true);
        $this->assertSame('parcial', $pedido->refresh()->estadoPago());
        $this->assertSame(0, $pedido->reservas()->count());

        $this->post(route('pagos.anular', $pago), ['motivo' => 'Comprobante rechazado por el banco', 'confirmar_anulacion' => 1])->assertSessionHasNoErrors();

        $this->assertSame('pendiente', $pedido->refresh()->estadoPago());
        $this->assertSame(0, $pedido->pagadoCentavos());
    }

    public function test_no_se_anula_si_la_reserva_firme_queda_sin_el_minimo(): void
    {
        [$pedido, $pago] = $this->pedidoFirme();

        $this->post(route('pagos.anular', $pago), ['motivo' => 'Error de registro', 'confirmar_anulacion' => 1])->assertSessionHasErrors('pago');

        $this->assertNull($pago->refresh()->anulado_en);
        $this->assertSame('firme', $pedido->reservas()->value('tipo'));
        $this->assertSame(2500, $pedido->refresh()->pagadoCentavos());
    }

    public function test_se_anula_si_otro_abono_verificado_mantiene_el_minimo(): void
    {
        [$pedido, $pago] = $this->pedidoFirme();
        $this->abono($pedido, '30.00', verificar: true);

        $this->post(route('pagos.anular', $pago), ['motivo' => 'Abono registrado en el pedido equivocado', 'confirmar_anulacion' => 1])->assertSessionHasNoErrors();

        $this->assertNotNull($pago->refresh()->anulado_en);
        $this->assertSame(3000, $pedido->refresh()->pagadoCentavos());
        $this->assertSame('firme', $pedido->reservas()->value('tipo'));
    }

    public function test_cancelar_el_pedido_antes_permite_anular_pendientes_pero_no_verificados(): void
    {
        [$pedido, $verificado] = $this->pedidoFirme();
        $pendiente = $this->abono($pedido, '10.00');
        app(GestionarPedidos::class)->cancelar($pedido->refresh(), $pedido->version, 'Cliente desistió', $this->administrador);

        $this->post(route('pagos.anular', $pendiente), ['motivo' => 'Nunca llegó el dinero', 'confirmar_anulacion' => 1])->assertSessionHasNoErrors();
        $this->assertNotNull($pendiente->refresh()->anulado_en);

        // Devolver un abono verificado es un reembolso: requiere regla acordada.
        $this->post(route('pagos.anular', $verificado), ['motivo' => 'Devolver dinero', 'confirmar_anulacion' => 1])->assertSessionHasErrors('pago');
        $this->assertNull($verificado->refresh()->anulado_en);
    }

    public function test_corregir_pendiente_anula_el_original_y_crea_reemplazo_pendiente(): void
    {
        $pedido = $this->pedido();
        $original = $this->abono($pedido, '50.00');

        $this->post(route('pagos.corregir.guardar', $original), $this->correccion())->assertSessionHasNoErrors();

        $original->refresh();
        $nuevo = Pago::where('corrige_pago_id', $original->id)->firstOrFail();
        $this->assertNotNull($original->anulado_en);
        $this->assertStringContainsString('Corregido por el abono #'.$nuevo->id, $original->motivo_anulacion);
        $this->assertSame('40.00', $nuevo->monto);
        $this->assertSame('BI-CORREGIDA', $nuevo->referencia);
        $this->assertNull($nuevo->verificado_en);
        $this->assertSame('50.00', $original->monto, 'El registro original no se modifica.');
        $this->assertDatabaseHas('eventos_pedido', ['pedido_id' => $pedido->id, 'tipo' => 'pago_corregido']);
        $this->get(route('pagos.show', $pedido->id))->assertSee('Corrige al abono #'.$original->id)->assertSee('Reemplazado por #'.$nuevo->id);
    }

    public function test_corregir_verificado_con_verificacion_mantiene_la_reserva_firme(): void
    {
        [$pedido, $pago] = $this->pedidoFirme('30.00');

        $this->post(route('pagos.corregir.guardar', $pago), $this->correccion(['importe' => '28.00', 'verificar' => 1]))->assertSessionHasNoErrors();

        $nuevo = Pago::where('corrige_pago_id', $pago->id)->firstOrFail();
        $this->assertNotNull($nuevo->verificado_en);
        $this->assertSame($this->administrador->id, (int) $nuevo->verificado_por);
        $this->assertSame(2800, $pedido->refresh()->pagadoCentavos());
        $this->assertSame('firme', $pedido->reservas()->value('tipo'));
    }

    public function test_correccion_no_puede_dejar_la_reserva_firme_sin_minimo(): void
    {
        [$pedido, $pago] = $this->pedidoFirme('30.00');

        // Sin verificar el reemplazo, lo verificado bajaría a cero.
        $this->post(route('pagos.corregir.guardar', $pago), $this->correccion(['importe' => '28.00']))->assertSessionHasErrors('pago');
        // Verificado pero por debajo del 25 %.
        $this->post(route('pagos.corregir.guardar', $pago), $this->correccion(['importe' => '20.00', 'verificar' => 1]))->assertSessionHasErrors('pago');

        $this->assertNull($pago->refresh()->anulado_en);
        $this->assertSame(1, Pago::count());
        $this->assertSame(3000, $pedido->refresh()->pagadoCentavos());
    }

    public function test_corregir_a_temporal_con_verificacion_promueve_la_reserva(): void
    {
        $pedido = $this->pedido();
        $pago = $this->abono($pedido, '10.00', verificar: true);
        $this->assertSame('temporal', $pedido->reservas()->value('tipo'));

        $this->post(route('pagos.corregir.guardar', $pago), $this->correccion(['importe' => '25.00', 'verificar' => 1]))->assertSessionHasNoErrors();

        $this->assertSame('firme', $pedido->reservas()->value('tipo'));
    }

    public function test_correccion_respeta_el_total_y_cuenta_el_original_como_disponible(): void
    {
        $pedido = $this->pedido();
        $original = $this->abono($pedido, '60.00');
        $this->abono($pedido, '30.00');

        $this->post(route('pagos.corregir.guardar', $original), $this->correccion(['importe' => '70.01']))->assertSessionHasErrors('importe');
        $this->post(route('pagos.corregir.guardar', $original), $this->correccion(['importe' => '70.00']))->assertSessionHasNoErrors();
        $this->assertSame(10000, Pago::whereNull('anulado_en')->get()->sum(fn ($pago) => (int) round($pago->monto * 100)));
    }

    public function test_correccion_es_idempotente_y_no_se_corrige_dos_veces(): void
    {
        $original = $this->abono($this->pedido(), '50.00');
        $datos = $this->correccion();

        $this->post(route('pagos.corregir.guardar', $original), $datos)->assertSessionHasNoErrors();
        $this->post(route('pagos.corregir.guardar', $original), $datos)->assertSessionHasNoErrors();
        $this->assertSame(2, Pago::count());

        $this->post(route('pagos.corregir.guardar', $original), $this->correccion())->assertSessionHasErrors('pago');
        $this->assertSame(2, Pago::count());
    }

    public function test_operador_no_corrige_ni_anula_y_supervisor_si(): void
    {
        $pedido = $this->pedido();
        $pago = $this->abono($pedido, '30.00');

        $this->actingAs(User::factory()->operador()->create());
        $this->get(route('pagos.corregir', $pago))->assertForbidden();
        $this->post(route('pagos.corregir.guardar', $pago), $this->correccion())->assertForbidden();
        $this->post(route('pagos.anular', $pago), ['motivo' => 'Intento sin permiso', 'confirmar_anulacion' => 1])->assertForbidden();
        $this->get(route('pagos.show', $pedido->id))->assertDontSee('Confirmar anulación')->assertDontSee(route('pagos.corregir', $pago));
        $this->assertNull($pago->refresh()->anulado_en);
        $this->assertSame(1, Pago::count());

        $this->actingAs(User::factory()->supervisor()->create());
        $this->get(route('pagos.corregir', $pago))->assertOk()->assertSee('Corregir abono #'.$pago->id);
        $this->post(route('pagos.anular', $pago), ['motivo' => 'Supervisor anula', 'confirmar_anulacion' => 1])->assertSessionHasNoErrors();
        $this->assertNotNull($pago->refresh()->anulado_en);
    }

    public function test_un_abono_anulado_no_se_puede_verificar_ni_corregir(): void
    {
        $pago = $this->abono($this->pedido(), '30.00');
        $this->post(route('pagos.anular', $pago), ['motivo' => 'Abono duplicado', 'confirmar_anulacion' => 1]);

        $this->post(route('pagos.verificar', $pago), ['confirmar_verificacion' => 1])->assertSessionHasErrors('pago');
        $this->post(route('pagos.corregir.guardar', $pago), $this->correccion())->assertSessionHasErrors('pago');
        $this->get(route('pagos.corregir', $pago))->assertRedirect(route('pagos.show', $pago->pedido_id));
        $this->assertNull($pago->refresh()->verificado_en);
    }
}
