<?php

namespace Tests\Feature;

use App\Acciones\Inventario\GestionarInventario;
use App\Acciones\Pedidos\GestionarPedidos;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Soporte\Importes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PedidosTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->usuario = User::factory()->create();
        $this->cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'telefono' => '55551234', 'direccion' => 'Dirección de prueba']);
        $this->actingAs($this->usuario);
    }

    private function pieza(string $precio = '100.00'): Producto
    {
        return app(GestionarInventario::class)->registrar(['codigo' => 'BB-'.(Producto::count() + 1), 'nombre' => 'Juguete de prueba', 'costo' => '30.25', 'precio' => $precio, 'existencias' => 1], $this->usuario);
    }

    private function datos(array $piezas, array $cambios = []): array
    {
        return array_replace(['cliente_id' => $this->cliente->id, 'modalidad' => 'contra_entrega', 'productos' => array_map(fn ($pieza) => $pieza->id, $piezas), 'referencia' => (string) Str::uuid()], $cambios);
    }

    private function borrador(array $piezas, array $cambios = []): Pedido
    {
        $datos = $this->datos($piezas, $cambios);
        $this->post(route('pedidos.guardar'), $datos)->assertSessionHasNoErrors()->assertRedirect();

        return Pedido::where('referencia', $datos['referencia'])->firstOrFail();
    }

    private function confirmar(Pedido $pedido): void
    {
        $this->post(route('pedidos.confirmar', $pedido), ['version' => $pedido->version])->assertSessionHasNoErrors();
    }

    private function abono(Pedido $pedido, string $monto, bool $verificado = true): Pago
    {
        return Pago::create(['pedido_id' => $pedido->id, 'usuario_id' => $this->usuario->id, 'verificado_por' => $verificado ? $this->usuario->id : null, 'clave_operacion' => (string) Str::uuid(), 'monto' => $monto, 'metodo' => 'deposito', 'referencia' => 'Referencia de prueba', 'fecha_pago' => now()->toDateString(), 'verificado_en' => $verificado ? now() : null]);
    }

    public function test_rutas_exigen_sesion(): void
    {
        auth()->logout();
        foreach (['/pedidos', '/pedidos/crear', '/pedidos/1', '/pedidos/1/editar', '/pedidos/opciones/clientes'] as $ruta) {
            $this->get($ruta)->assertRedirect(route('login'));
        }
        $this->post('/pedidos', [])->assertRedirect(route('login'));
        $this->put('/pedidos/1', [])->assertRedirect(route('login'));
        foreach (['confirmar', 'cancelar', 'devolucion'] as $operacion) {
            $this->post('/pedidos/1/'.$operacion, [])->assertRedirect(route('login'));
        }
    }

    public function test_guarda_borrador_con_precio_servidor_y_no_reserva(): void
    {
        $pieza = $this->pieza('100.01');
        $pedido = $this->borrador([$pieza], ['total' => '0.01', 'porcentaje_deposito' => 0]);
        $this->assertSame('100.01', $pedido->total);
        $this->assertSame(25, $pedido->porcentaje_deposito);
        $this->assertSame('30.25', $pedido->detalles->first()->costo);
        $this->assertSame('borrador', $pedido->estado);
        $this->assertDatabaseCount('reservas', 0);
        $this->get(route('pedidos.mostrar', $pedido))->assertOk();
        $this->get(route('pedidos.crear'))->assertOk();
        $this->get(route('pedidos.editar', $pedido))->assertOk();
    }

    public function test_borrador_reenviado_no_se_duplica_y_no_admite_reutilizar_referencia(): void
    {
        $pieza = $this->pieza();
        $datos = $this->datos([$pieza]);
        $this->post(route('pedidos.guardar'), $datos)->assertSessionHasNoErrors();
        $this->post(route('pedidos.guardar'), $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pedidos', 1);
        $this->post(route('pedidos.guardar'), array_replace($datos, ['modalidad' => 'deposito']))->assertSessionHasErrors('pedido');
    }

    public function test_confirmar_varias_piezas_es_idempotente_y_bloquea_inventario(): void
    {
        $pieza = $this->pieza();
        $otra = $this->pieza();
        $pedido = $this->borrador([$otra, $pieza]);
        $this->confirmar($pedido);
        $this->confirmar($pedido);
        $this->assertDatabaseCount('reservas', 2);
        $this->assertSame('confirmado', $pedido->refresh()->estado);
        $this->assertDatabaseHas('reservas', ['producto_bloqueado_id' => $pieza->id, 'tipo' => 'temporal']);
        $this->assertSame(1, $pieza->refresh()->existencias);
        $this->get('/inventario?existencias=con')->assertViewHas('productos', fn ($productos) => $productos->total() === 0);
        $this->post(route('inventario.ajustar', $pieza), ['tipo' => 'salida', 'cantidad' => 1, 'motivo' => 'Intento manual', 'referencia' => (string) Str::uuid()])->assertSessionHasErrors('cantidad');
        $this->patch(route('inventario.estado', $pieza), ['activo' => 0, 'version' => $pieza->version])->assertSessionHasErrors('version');
    }

    public function test_dos_pedidos_no_pueden_reservar_la_misma_pieza_y_no_hay_cambios_parciales(): void
    {
        $pieza = $this->pieza();
        $otra = $this->pieza();
        $primero = $this->borrador([$pieza]);
        $segundo = $this->borrador([$otra, $pieza]);
        $this->confirmar($primero);
        $this->post(route('pedidos.confirmar', $segundo), ['version' => $segundo->version])->assertSessionHasErrors('pedido');
        $this->assertSame('borrador', $segundo->refresh()->estado);
        $this->assertNull($otra->reservaActual);
        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_deposito_necesita_25_por_ciento_verificado_redondeado_hacia_arriba(): void
    {
        $pedido = $this->borrador([$this->pieza('100.01')], ['modalidad' => 'deposito']);
        $this->assertSame(2501, $pedido->depositoMinimoCentavos());
        $pago = $this->abono($pedido, '100.01', false);
        $pago->update(['verificado_en' => now()]);
        $this->post(route('pedidos.confirmar', $pedido), ['version' => $pedido->version])->assertSessionHasErrors('pedido');
        $pago->update(['monto' => '25.00', 'verificado_en' => now(), 'verificado_por' => $this->usuario->id]);
        $this->post(route('pedidos.confirmar', $pedido), ['version' => $pedido->version])->assertSessionHasErrors('pedido');
        $pago->update(['monto' => '25.01']);
        $this->confirmar($pedido);
        $this->assertDatabaseHas('reservas', ['pedido_id' => $pedido->id, 'tipo' => 'firme']);
    }

    public function test_pagos_anulados_no_cuentan_y_abono_verificado_promueve_temporal(): void
    {
        $pedido = $this->borrador([$this->pieza()]);
        $this->confirmar($pedido);
        $pago = $this->abono($pedido, '25.00');
        $pago->update(['anulado_en' => now()]);
        app(GestionarPedidos::class)->promoverReserva($pedido, $this->usuario);
        $this->assertSame(0, $pedido->pagadoCentavos());
        $this->assertSame('temporal', $pedido->reservas()->first()->tipo);
        $pago->update(['anulado_en' => null]);
        app(GestionarPedidos::class)->promoverReserva($pedido, $this->usuario);
        app(GestionarPedidos::class)->promoverReserva($pedido, $this->usuario);
        $this->assertSame('firme', $pedido->reservas()->first()->tipo);
        $this->assertSame('parcial', $pedido->estadoPago());
        $this->assertSame(1, $pedido->eventos()->where('tipo', 'reserva_firme')->count());
    }

    public function test_cancelar_antes_de_envio_libera_una_sola_vez_y_conserva_pagos(): void
    {
        $pieza = $this->pieza();
        $pedido = $this->borrador([$pieza]);
        $this->confirmar($pedido);
        $this->abono($pedido, '10.00');
        $pedido->refresh();
        $datos = ['version' => $pedido->version, 'motivo' => 'Cliente canceló'];
        $this->post(route('pedidos.cancelar', $pedido), $datos)->assertSessionHasNoErrors();
        $this->post(route('pedidos.cancelar', $pedido), $datos)->assertSessionHasNoErrors();
        $this->assertSame('cancelado', $pedido->refresh()->estado);
        $this->assertNull($pieza->refresh()->reservaActual);
        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertSame(1, $pedido->eventos()->where('tipo', 'cancelado')->count());
        $this->confirmar($this->borrador([$pieza]));
    }

    public function test_cancelacion_enviada_no_libera_hasta_regreso_fisico(): void
    {
        $pieza = $this->pieza();
        $pedido = $this->borrador([$pieza]);
        $this->confirmar($pedido);
        // Representa el estado que gestionará el futuro módulo de entregas.
        $pedido->refresh()->update(['estado_logistico' => 'enviado']);
        $pieza->refresh()->forceFill(['existencias' => 0])->save();
        $this->post(route('pedidos.cancelar', $pedido), ['version' => $pedido->version, 'motivo' => 'Regreso solicitado'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservas', ['producto_bloqueado_id' => $pieza->id, 'estado' => 'devolucion_pendiente']);
        $this->post(route('pedidos.devolver', $pedido), [])->assertSessionHasErrors('confirmar_regreso');
        $this->post(route('pedidos.devolver', $pedido), ['confirmar_regreso' => 1])->assertSessionHasNoErrors();
        $this->post(route('pedidos.devolver', $pedido), ['confirmar_regreso' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, $pieza->refresh()->existencias);
        $this->assertNull($pieza->reservaActual);
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $pieza->id, 'tipo' => 'devolucion']);
        $this->assertSame(1, $pedido->eventos()->where('tipo', 'devolucion_confirmada')->count());
    }

    public function test_edicion_obsoleta_y_cambio_de_pieza_exigen_revision_del_borrador(): void
    {
        $pieza = $this->pieza();
        $pedido = $this->borrador([$pieza]);
        app(GestionarInventario::class)->actualizar($pieza, ['codigo' => $pieza->codigo, 'nombre' => $pieza->nombre, 'costo' => '31.00', 'precio' => '110.00', 'version' => $pieza->version]);
        $this->post(route('pedidos.confirmar', $pedido), ['version' => $pedido->version])->assertSessionHasErrors('pedido');
        $datos = $this->datos([$pieza], ['version' => $pedido->version]);
        $this->put(route('pedidos.actualizar', $pedido), $datos)->assertSessionHasNoErrors();
        $this->put(route('pedidos.actualizar', $pedido), $datos)->assertSessionHasErrors('pedido');
        $this->assertSame('110.00', $pedido->refresh()->total);
        $this->confirmar($pedido);
        $this->get(route('pedidos.editar', $pedido))->assertForbidden();
    }

    public function test_historial_conserva_precio_y_contacto_y_no_se_edita_borrador_con_pago(): void
    {
        $pieza = $this->pieza();
        $pedido = $this->borrador([$pieza]);
        $this->abono($pedido, '10.00');
        $this->put(route('pedidos.actualizar', $pedido), $this->datos([$pieza], ['version' => $pedido->version]))->assertSessionHasErrors('pedido');
        app(GestionarInventario::class)->actualizar($pieza, ['codigo' => $pieza->codigo, 'nombre' => $pieza->nombre, 'costo' => $pieza->costo, 'precio' => '200.00', 'version' => $pieza->version]);
        $this->confirmar($pedido);
        $pieza->update(['precio' => '200.00']);
        $this->cliente->update(['nombre' => 'Nombre nuevo']);
        $this->cliente->delete();
        $this->get(route('pedidos.mostrar', $pedido))->assertOk()->assertSee('Cliente de prueba');
        $this->assertSame('100.00', $pedido->detalles()->first()->precio);
    }

    public function test_validaciones_busquedas_y_fetch_solo_muestran_piezas_disponibles(): void
    {
        $pieza = $this->pieza();
        $this->post(route('pedidos.guardar'), $this->datos([$pieza, $pieza]))->assertSessionHasErrors('productos.0');
        $this->post(route('pedidos.guardar'), $this->datos([]))->assertSessionHasErrors('productos');
        $pedido = $this->borrador([$pieza]);
        $this->get('/pedidos?buscar=Cliente&estado=borrador')->assertOk()->assertSee('Cliente de prueba');
        $this->get('/pedidos/opciones/piezas?buscar=BB')->assertOk()->assertJsonCount(1, 'resultados');
        $this->confirmar($pedido);
        $this->get('/pedidos/opciones/piezas?buscar=BB')->assertJsonCount(0, 'resultados');
        $this->get('/pedidos/opciones/clientes?buscar=5555')->assertJsonCount(1, 'resultados');
        $this->cliente->delete();
        $this->get('/pedidos/opciones/clientes')->assertJsonCount(0, 'resultados');
    }

    public function test_importes_no_acumulan_errores_de_flotantes(): void
    {
        $pedido = $this->borrador([$this->pieza('0.10'), $this->pieza('0.20')]);
        $this->assertSame('0.30', $pedido->total);
        $this->assertSame(8, $pedido->depositoMinimoCentavos());
        $this->assertSame('0.30', Importes::aDecimal(30));
    }
}
