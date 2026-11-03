<?php

namespace Tests\Feature;

use App\Acciones\Reportes\ConsultarReportes;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Reembolso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas finales de integración: recorridos completos solo por HTTP, con las tres cuentas,
 * incluyendo operaciones rechazadas, cancelaciones, devoluciones físicas y reembolsos.
 */
class FlujoCompletoTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;

    private User $supervisor;

    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 11, 10)->setTime(10, 0));
        $this->administrador = User::factory()->create(['name' => 'Dueña']);
        $this->supervisor = User::factory()->supervisor()->create(['name' => 'Encargado']);
        $this->operador = User::factory()->operador()->create(['name' => 'Vendedora', 'password' => Hash::make('ClaveVendedora2026')]);
    }

    /** Cada persona trabaja en su propio navegador: sesión nueva al cambiar de cuenta. */
    private function como(User $usuario): static
    {
        if (auth()->id() !== $usuario->id) {
            $this->flushSession();
        }

        return $this->actingAs($usuario);
    }

    /** El operador registra cliente y pedido; el administrador registra la pieza. */
    private function pedidoNuevo(string $modalidad, string $precio = '100.00'): Pedido
    {
        $codigo = 'FL-'.(Producto::count() + 1);
        $this->como($this->administrador)->post(route('inventario.guardar'), ['codigo' => $codigo, 'nombre' => 'Muñeca de prueba', 'costo' => '40.00', 'precio' => $precio, 'existencias' => 1])->assertSessionHasNoErrors();
        $pieza = Producto::where('codigo', $codigo)->sole();
        $this->como($this->operador)->post(route('clientes.store'), ['nombre' => 'Cliente '.$codigo, 'telefono' => '5555 1234'])->assertSessionHasNoErrors();
        $cliente = Cliente::latest('id')->first();
        $this->post(route('pedidos.guardar'), ['cliente_id' => $cliente->id, 'modalidad' => $modalidad, 'productos' => [$pieza->id], 'referencia' => (string) Str::uuid()])->assertSessionHasNoErrors();

        return Pedido::latest('id')->first();
    }

    private function abono(Pedido $pedido, string $importe): Pago
    {
        $this->post(route('pagos.store', $pedido->id), ['importe' => $importe, 'metodo' => 'deposito', 'referencia' => 'BI-'.Str::random(6), 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()])->assertSessionHasNoErrors();

        return Pago::latest('id')->first();
    }

    private function verificar(Pago $pago)
    {
        return $this->post(route('pagos.verificar', $pago), ['confirmar_verificacion' => 1]);
    }

    private function enviar(Pedido $pedido): void
    {
        $this->post(route('entregas.empacar', $pedido), ['version' => $pedido->refresh()->version])->assertSessionHasNoErrors();
        $this->post(route('entregas.enviar', $pedido), ['version' => $pedido->refresh()->version, 'transportista' => 'forza', 'guia' => 'FORZA-'.$pedido->id, 'destinatario' => 'Cliente', 'telefono' => '55551234', 'direccion' => 'Zona 1'])->assertSessionHasNoErrors();
    }

    public function test_venta_con_deposito_de_principio_a_fin_con_permisos_y_rechazos(): void
    {
        $pedido = $this->pedidoNuevo('deposito');
        $pieza = $pedido->detalles()->sole()->producto_id;

        // Operador: registra el 25 %, pero no puede verificarlo ni confirmar sin verificación.
        $deposito = $this->como($this->operador)->abono($pedido, '25.00');
        $this->verificar($deposito)->assertForbidden();
        $this->post(route('pedidos.confirmar', $pedido), ['version' => $pedido->refresh()->version])->assertSessionHasErrors('pedido');
        $this->assertSame('borrador', $pedido->refresh()->estado);

        // Supervisor verifica; el operador confirma y la reserva nace firme.
        $this->como($this->supervisor)->verificar($deposito)->assertSessionHasNoErrors();
        $this->como($this->operador)->post(route('pedidos.confirmar', $pedido), ['version' => $pedido->refresh()->version])->assertSessionHasNoErrors();
        $this->assertSame('firme', $pedido->reservas()->value('tipo'));

        // Operaciones sensibles negadas al operador, sin cambios en datos.
        $this->post(route('pedidos.cancelar', $pedido), ['version' => $pedido->refresh()->version, 'motivo' => 'Intento'])->assertForbidden();
        $this->post(route('inventario.ajustar', $pieza), ['tipo' => 'salida', 'cantidad' => 1, 'motivo' => 'Intento', 'referencia' => (string) Str::uuid()])->assertForbidden();
        $this->post(route('pagos.anular', $deposito), ['motivo' => 'Intento sin permiso', 'confirmar_anulacion' => 1])->assertForbidden();
        $this->get(route('usuarios.index'))->assertForbidden();
        $this->assertSame('confirmado', $pedido->refresh()->estado);

        // Supervisor: la anulación que dejaría la reserva firme sin mínimo se rechaza.
        $this->como($this->supervisor)->post(route('pagos.anular', $deposito), ['motivo' => 'Error de digitación', 'confirmar_anulacion' => 1])->assertSessionHasErrors('pago');
        $this->assertNull($deposito->refresh()->anulado_en);

        // Saldo, entrega y venta reconocida igual en reportes y panel.
        $resto = $this->como($this->operador)->abono($pedido, '75.00');
        $this->assertSame('0.00', $this->get('/panel')->viewData('resumenVentas')->total_ventas, 'Sin verificar ni entregar, todavía no es venta.');
        $this->como($this->supervisor)->verificar($resto)->assertSessionHasNoErrors();
        $this->como($this->operador)->enviar($pedido);
        $this->post(route('entregas.entregar', $pedido), ['version' => $pedido->refresh()->version, 'confirmar_entrega' => 1])->assertSessionHasNoErrors();

        $totales = app(ConsultarReportes::class)->resumen(['desde' => '2026-11-01', 'hasta' => '2026-11-30', 'agrupacion' => 'mensual'])['totales'];
        $this->assertSame([1, 10000, 6000, 10000], [$totales['pedidos'], $totales['ventas'], $totales['ganancia'], $totales['cobros']]);
        $this->como($this->administrador)->get('/panel')->assertOk()->assertViewHas('resumenVentas', fn ($resumen) => $resumen->total_ventas === '100.00');
        $this->get(route('pedidos.mostrar', $pedido))->assertSee('Encargado')->assertSee('Vendedora');
    }

    public function test_cancelacion_despues_del_envio_con_regreso_fisico_y_reembolso_parcial(): void
    {
        $pedido = $this->pedidoNuevo('contra_entrega');
        $pieza = Producto::findOrFail($pedido->detalles()->sole()->producto_id);
        $this->como($this->operador)->post(route('pedidos.confirmar', $pedido), ['version' => $pedido->version])->assertSessionHasNoErrors();
        $abono = $this->abono($pedido, '40.00');
        $this->como($this->supervisor)->verificar($abono)->assertSessionHasNoErrors();
        $this->como($this->operador)->enviar($pedido);
        $this->assertSame(0, $pieza->refresh()->existencias);

        // Cancela el supervisor: la pieza queda bloqueada hasta que regrese.
        $this->como($this->supervisor)->post(route('pedidos.cancelar', $pedido), ['version' => $pedido->refresh()->version, 'motivo' => 'Cliente rechazó el paquete'])->assertSessionHasNoErrors();
        $this->assertSame('devolucion_pendiente', $pedido->reservas()->value('estado'));

        // Nadie reembolsa antes del regreso; el supervisor no puede reembolsar nunca.
        $datos = fn (string $importe) => ['importe' => $importe, 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'motivo' => 'Se retienen Q 10 de envío', 'clave_operacion' => (string) Str::uuid()];
        $this->como($this->administrador)->post(route('pagos.reembolsar', $pedido->id), $datos('30.00'))->assertSessionHasErrors('reembolso');
        $this->como($this->supervisor)->post(route('pagos.reembolsar', $pedido->id), $datos('30.00'))->assertForbidden();

        // El operador confirma el regreso físico: la pieza vuelve a estar disponible.
        $this->como($this->operador)->post(route('pedidos.devolver', $pedido), ['confirmar_regreso' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, $pieza->refresh()->existencias);
        $this->get(route('pedidos.opciones', ['tipo' => 'piezas', 'buscar' => $pieza->codigo]))->assertOk()->assertSee($pieza->codigo);

        // Reembolso parcial por el administrador; se refleja en el cobro neto.
        $this->como($this->administrador)->post(route('pagos.reembolsar', $pedido->id), $datos('30.00'))->assertSessionHasNoErrors();
        $this->assertSame('30.00', Reembolso::sole()->monto);
        $this->assertSame(1, $pedido->pagos()->whereNull('anulado_en')->count(), 'El abono original se conserva.');
        $totales = app(ConsultarReportes::class)->resumen(['desde' => '2026-11-01', 'hasta' => '2026-11-30', 'agrupacion' => 'mensual'])['totales'];
        $this->assertSame([0, 4000, 3000, 1000], [$totales['ventas'], $totales['cobros'], $totales['reembolsos'], $totales['cobro_neto']]);
    }

    public function test_correccion_de_abono_y_desactivacion_de_cuenta_a_mitad_del_trabajo(): void
    {
        $pedido = $this->pedidoNuevo('contra_entrega');
        $this->como($this->operador)->post(route('pedidos.confirmar', $pedido), ['version' => $pedido->version])->assertSessionHasNoErrors();
        $mal = $this->abono($pedido, '50.00');

        // El supervisor corrige el importe digitado y lo verifica en la misma operación.
        $this->como($this->supervisor)->post(route('pagos.corregir.guardar', $mal), ['importe' => '45.00', 'metodo' => 'deposito', 'referencia' => 'BI-OK', 'fecha' => now()->toDateString(), 'motivo' => 'El comprobante dice Q 45', 'verificar' => 1, 'clave_operacion' => (string) Str::uuid()])->assertSessionHasNoErrors();
        $this->assertNotNull($mal->refresh()->anulado_en);
        $this->assertSame(4500, $pedido->refresh()->pagadoCentavos());

        // El administrador desactiva al operador: su sesión se cierra y ya no puede ingresar.
        $this->como($this->operador)->get('/panel')->assertOk();
        $this->operador->forceFill(['activo' => false])->save();
        $this->get('/panel')->assertRedirect(route('login'));
        $this->post('/ingresar', ['email' => $this->operador->email, 'password' => 'ClaveVendedora2026'])->assertSessionHasErrors('email');
        $this->assertGuest();

        // Su historial se conserva con su nombre.
        $this->como($this->administrador)->get(route('pagos.show', $pedido->id))->assertOk()->assertSee('Vendedora')->assertSee('Corrige al abono #'.$mal->id);
    }
}
