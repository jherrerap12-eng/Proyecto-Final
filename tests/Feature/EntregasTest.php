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

class EntregasTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->usuario = User::factory()->create();
        $this->cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'telefono' => '55551234', 'direccion' => 'Dirección original']);
        $this->actingAs($this->usuario);
    }

    private function pedido(int $cantidad = 1, bool $confirmado = true, string $modalidad = 'contra_entrega'): Pedido
    {
        $ids = [];
        for ($numero = 0; $numero < $cantidad; $numero++) {
            $pieza = app(GestionarInventario::class)->registrar(['codigo' => 'EN-'.(Producto::count() + 1), 'nombre' => 'Pieza de prueba', 'costo' => '30.00', 'precio' => '100.00', 'existencias' => 1], $this->usuario);
            $ids[] = $pieza->id;
        }
        $pedido = app(GestionarPedidos::class)->guardar(['cliente_id' => $this->cliente->id, 'modalidad' => $modalidad, 'productos' => $ids, 'referencia' => (string) Str::uuid()], $this->usuario);
        if ($confirmado) {
            if ($modalidad === 'deposito') {
                Pago::create(['pedido_id' => $pedido->id, 'usuario_id' => $this->usuario->id, 'verificado_por' => $this->usuario->id, 'monto' => '25.00', 'metodo' => 'deposito', 'fecha_pago' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid(), 'verificado_en' => now()]);
            }
            app(GestionarPedidos::class)->confirmar($pedido, $pedido->version, $this->usuario);
        }

        return $pedido->refresh();
    }

    private function empacar(Pedido $pedido): void
    {
        $this->post(route('entregas.empacar', $pedido), ['version' => $pedido->version])->assertSessionHasNoErrors();
        $pedido->refresh();
    }

    private function datos(Pedido $pedido, array $cambios = []): array
    {
        return array_replace(['version' => $pedido->version, 'transportista' => 'forza', 'guia' => 'FORZA-001', 'destinatario' => 'Destinatario de prueba', 'telefono' => '55551234', 'direccion' => 'Dirección confirmada', 'observaciones' => 'Entregar en recepción'], $cambios);
    }

    private function enviar(Pedido $pedido, array $cambios = []): void
    {
        $this->post(route('entregas.enviar', $pedido), $this->datos($pedido, $cambios))->assertSessionHasNoErrors();
        $pedido->refresh();
    }

    public function test_archivar_entregada_conserva_pedido_reserva_y_stock_y_permite_regreso_posterior(): void
    {
        $pedido = $this->pedido();
        $this->empacar($pedido);
        $this->enviar($pedido);
        $this->post(route('entregas.entregar', $pedido), ['version' => $pedido->version, 'confirmar_entrega' => 1])->assertSessionHasNoErrors();
        $pedido->refresh();
        $pieza = Producto::firstOrFail();
        $reserva = $pieza->reservaActual;
        $versionPedido = $pedido->version;
        $movimientos = $pieza->movimientos()->count();
        $this->get(route('inventario.mostrar', $pieza))->assertOk()->assertSee('Archivar pieza entregada');
        $this->get(route('inventario.listar'))->assertOk()->assertSee('Entregada')->assertDontSee('Reserva temporal');
        $this->patch(route('inventario.estado', $pieza), ['activo' => 0, 'version' => $pieza->version])->assertSessionHasNoErrors();
        $this->assertFalse($pieza->refresh()->activo);
        $this->assertSame(0, $pieza->existencias);
        $this->assertSame($pieza->id, $reserva->refresh()->producto_bloqueado_id);
        $this->assertSame($versionPedido, $pedido->refresh()->version);
        $this->assertSame('entregado', $pedido->estado_logistico);
        $this->assertSame($movimientos, $pieza->movimientos()->count());
        $this->get(route('inventario.listar'))->assertDontSee($pieza->codigo);
        $this->get(route('inventario.listar', ['estado' => 'inactivos']))->assertSee($pieza->codigo)->assertSee('Entregada');
        app(GestionarPedidos::class)->cancelar($pedido, $pedido->version, 'Regreso posterior al archivo', $this->usuario);
        $pieza->refresh()->load('reservaActual.pedido');
        $this->assertSame('Pendiente de regreso', $pieza->situacionInventario());
        app(GestionarPedidos::class)->confirmarDevolucion($pedido->refresh(), $this->usuario);
        $this->assertSame(1, $pieza->refresh()->existencias);
        $this->assertFalse($pieza->activo);
        $this->assertNull($pieza->reservaActual()->first());
        $this->patch(route('inventario.estado', $pieza), ['activo' => 1, 'version' => $pieza->version])->assertSessionHasNoErrors();
        $this->assertSame('Disponible', $pieza->refresh()->situacionInventario());
    }

    public function test_pieza_reservada_o_enviada_no_se_puede_archivar(): void
    {
        $pedido = $this->pedido();
        $pieza = Producto::firstOrFail();
        $this->patch(route('inventario.estado', $pieza), ['activo' => 0, 'version' => $pieza->version])->assertSessionHasErrors('version');
        $this->empacar($pedido);
        $this->enviar($pedido);
        $this->get(route('inventario.listar'))->assertOk()->assertSee('Enviada');
        $this->patch(route('inventario.estado', $pieza), ['activo' => 0, 'version' => $pieza->refresh()->version])->assertSessionHasErrors('version');
        $this->assertTrue($pieza->refresh()->activo);
        $this->assertNotNull($pieza->reservaActual);
    }

    public function test_rutas_exigen_sesion(): void
    {
        auth()->logout();
        $this->get('/entregas')->assertRedirect(route('login'));
        $this->get('/entregas/pedidos/1')->assertRedirect(route('login'));
        foreach (['empacar', 'enviar', 'entregar'] as $operacion) {
            $this->post('/entregas/pedidos/1/'.$operacion, [])->assertRedirect(route('login'));
        }
        $this->assertDatabaseCount('envios', 0);
    }

    public function test_empaque_conserva_existencias_y_es_idempotente(): void
    {
        $pedido = $this->pedido(2);
        $version = $pedido->version;
        $this->get(route('entregas.mostrar', $pedido))->assertOk()->assertSee('Marcar como empacado');
        $this->empacar($pedido);
        $this->post(route('entregas.empacar', $pedido), ['version' => $version])->assertSessionHasNoErrors();
        $this->assertSame('empacado', $pedido->refresh()->estado_logistico);
        $this->assertDatabaseCount('envios', 1);
        $this->assertSame(2, Producto::where('existencias', 1)->count());
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->assertSame(1, $pedido->eventos()->where('tipo', 'empacado')->count());
        $this->assertDatabaseHas('envios', ['pedido_id' => $pedido->id, 'empacado_por' => $this->usuario->id, 'destinatario' => 'Cliente de prueba']);
        $this->get(route('entregas.mostrar', $pedido))->assertOk()->assertSee('Registrar el envío');
    }

    public function test_envio_forza_saca_todas_las_piezas_una_sola_vez_y_conserva_reservas(): void
    {
        $pedido = $this->pedido(2);
        $this->empacar($pedido);
        $datos = $this->datos($pedido, ['enviado_por' => 999, 'enviado_en' => '2000-01-01']);
        $this->post(route('entregas.enviar', $pedido), $datos)->assertSessionHasNoErrors();
        $fecha = $pedido->envio()->firstOrFail()->enviado_en;
        $this->post(route('entregas.enviar', $pedido), $datos)->assertSessionHasNoErrors();
        $this->assertSame('enviado', $pedido->refresh()->estado_logistico);
        $this->assertSame(2, Producto::where('existencias', 0)->count());
        $this->assertSame(2, $pedido->reservas()->whereNotNull('producto_bloqueado_id')->count());
        $this->assertDatabaseCount('movimientos_inventario', 4);
        $this->assertSame(1, $pedido->eventos()->where('tipo', 'enviado')->count());
        $this->assertTrue($fecha->equalTo($pedido->envio()->firstOrFail()->enviado_en));
        $this->assertDatabaseHas('envios', ['pedido_id' => $pedido->id, 'transportista' => 'forza', 'guia' => 'FORZA-001', 'enviado_por' => $this->usuario->id]);
        $this->get(route('entregas.mostrar', $pedido))->assertOk()->assertSee('Confirmar entrega');
        $this->post(route('entregas.enviar', $pedido), array_replace($datos, ['guia' => 'OTRA']))->assertSessionHasErrors('entrega');
    }

    public function test_mensajeria_propia_no_requiere_guia_y_usa_contacto_historico(): void
    {
        $pedido = $this->pedido();
        $this->cliente->update(['nombre' => 'Nombre cambiado']);
        $this->cliente->delete();
        $this->empacar($pedido);
        $this->assertDatabaseHas('envios', ['pedido_id' => $pedido->id, 'destinatario' => 'Cliente de prueba']);
        $this->enviar($pedido, ['transportista' => 'mensajeria_propia', 'guia' => null]);
        $this->assertDatabaseHas('envios', ['pedido_id' => $pedido->id, 'transportista' => 'mensajeria_propia', 'guia' => null]);
    }

    public function test_rechaza_pasos_fuera_de_orden_y_borradores(): void
    {
        $borrador = $this->pedido(1, false);
        $this->post(route('entregas.empacar', $borrador), ['version' => $borrador->version])->assertSessionHasErrors('entrega');
        $pedido = $this->pedido();
        $this->post(route('entregas.enviar', $pedido), $this->datos($pedido))->assertSessionHasErrors('entrega');
        $this->post(route('entregas.entregar', $pedido), ['version' => $pedido->version, 'confirmar_entrega' => 1])->assertSessionHasErrors('entrega');
        $this->assertDatabaseCount('envios', 0);
        $this->assertSame('por_preparar', $pedido->refresh()->estado_logistico);
    }

    public function test_valida_guia_direccion_telefono_transportista_y_version(): void
    {
        $pedido = $this->pedido();
        $this->empacar($pedido);
        $this->post(route('entregas.enviar', $pedido), $this->datos($pedido, ['guia' => '', 'direccion' => '', 'telefono' => ['no válido'], 'destinatario' => '']))->assertSessionHasErrors(['guia', 'direccion', 'telefono', 'destinatario']);
        $this->post(route('entregas.enviar', $pedido), $this->datos($pedido, ['transportista' => 'no existe', 'version' => 0]))->assertSessionHasErrors(['transportista', 'version']);
        $this->assertDatabaseHas('productos', ['existencias' => 1]);
        $this->assertNull($pedido->envio()->firstOrFail()->enviado_en);
    }

    public function test_formulario_obsoleto_no_envia_y_un_error_no_deja_salida_parcial(): void
    {
        $pedido = $this->pedido(2);
        $vieja = $pedido->version;
        $this->empacar($pedido);
        $this->post(route('entregas.enviar', $pedido), $this->datos($pedido, ['version' => $vieja]))->assertSessionHasErrors('entrega');
        Producto::orderByDesc('id')->firstOrFail()->forceFill(['existencias' => 0])->save();
        $this->post(route('entregas.enviar', $pedido), $this->datos($pedido))->assertSessionHasErrors('entrega');
        $this->assertSame(1, Producto::orderBy('id')->firstOrFail()->existencias);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->assertNull($pedido->envio()->firstOrFail()->enviado_en);
        $this->assertSame('empacado', $pedido->refresh()->estado_logistico);
    }

    public function test_deposito_debe_conservar_minimo_verificado_antes_de_enviar(): void
    {
        $pedido = $this->pedido(1, true, 'deposito');
        $this->empacar($pedido);
        Pago::firstOrFail()->update(['anulado_en' => now()]);
        $this->post(route('entregas.enviar', $pedido), $this->datos($pedido))->assertSessionHasErrors('entrega');
        $this->assertSame(1, Producto::firstOrFail()->existencias);
        $this->assertDatabaseCount('movimientos_inventario', 1);
    }

    public function test_entrega_no_cobra_ni_duplica_stock_y_permite_pagar_despues(): void
    {
        $pedido = $this->pedido();
        $this->empacar($pedido);
        $this->enviar($pedido);
        $this->post(route('entregas.entregar', $pedido), ['version' => $pedido->version])->assertSessionHasErrors('confirmar_entrega');
        $datos = ['version' => $pedido->version, 'confirmar_entrega' => 1, 'observaciones_entrega' => 'Recibió el destinatario'];
        $this->post(route('entregas.entregar', $pedido), $datos)->assertSessionHasNoErrors();
        $this->post(route('entregas.entregar', $pedido), $datos)->assertSessionHasNoErrors();
        $this->assertSame('entregado', $pedido->refresh()->estado_logistico);
        $this->assertSame('pendiente', $pedido->estadoPago());
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->assertSame(1, $pedido->eventos()->where('tipo', 'entregado')->count());
        $this->assertDatabaseHas('envios', ['pedido_id' => $pedido->id, 'entregado_por' => $this->usuario->id]);
        $pago = app(RegistrarPago::class)->registrar($pedido->id, ['importe' => '100.00', 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()], $this->usuario);
        app(RegistrarPago::class)->verificar($pago, $this->usuario);
        $this->assertSame('pagado', $pedido->estadoPago());
        $this->assertSame('entregado', $pedido->refresh()->estado_logistico);
    }

    public function test_cancelacion_antes_de_envio_no_saca_piezas(): void
    {
        $pedido = $this->pedido();
        $this->empacar($pedido);
        app(GestionarPedidos::class)->cancelar($pedido, $pedido->version, 'Cancelado antes de envío', $this->usuario);
        $this->post(route('entregas.enviar', $pedido), $this->datos($pedido))->assertSessionHasErrors('entrega');
        $this->assertSame(1, Producto::firstOrFail()->existencias);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertNull($pedido->envio()->firstOrFail()->enviado_en);
    }

    public function test_cancelacion_enviada_conserva_bloqueo_y_regreso_deja_huella_una_sola_vez(): void
    {
        $pedido = $this->pedido();
        $this->empacar($pedido);
        $this->enviar($pedido);
        app(GestionarPedidos::class)->cancelar($pedido, $pedido->version, 'Se solicitó regreso', $this->usuario);
        $pedido->refresh();
        $this->post(route('entregas.entregar', $pedido), ['version' => $pedido->version, 'confirmar_entrega' => 1])->assertSessionHasErrors('entrega');
        $this->assertSame(0, Producto::firstOrFail()->existencias);
        $this->assertNotNull(Producto::firstOrFail()->reservaActual);
        $this->get('/entregas?estado=devolucion_pendiente')->assertViewHas('pedidos', fn ($pedidos) => $pedidos->total() === 1);
        $this->post(route('pedidos.devolver', $pedido), ['confirmar_regreso' => 1])->assertSessionHasNoErrors();
        $this->post(route('pedidos.devolver', $pedido), ['confirmar_regreso' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, Producto::firstOrFail()->existencias);
        $this->assertNull(Producto::firstOrFail()->reservaActual);
        $this->assertDatabaseCount('movimientos_inventario', 3);
        $this->assertNotNull($pedido->envio()->firstOrFail()->devuelto_en);
        $this->assertSame($this->usuario->id, (int) $pedido->envio()->firstOrFail()->devuelto_por);
        $this->get('/entregas?estado=devuelto')->assertViewHas('pedidos', fn ($pedidos) => $pedidos->total() === 1);
        $this->get('/entregas?estado=devolucion_pendiente')->assertViewHas('pedidos', fn ($pedidos) => $pedidos->total() === 0);
    }

    public function test_cancelar_despues_de_entregar_conserva_fechas_y_no_reembolsa(): void
    {
        $pedido = $this->pedido();
        $this->empacar($pedido);
        $this->enviar($pedido);
        $this->post(route('entregas.entregar', $pedido), ['version' => $pedido->version, 'confirmar_entrega' => 1])->assertSessionHasNoErrors();
        $pedido->refresh();
        app(GestionarPedidos::class)->cancelar($pedido, $pedido->version, 'Regreso tras entrega', $this->usuario);
        $this->assertNotNull($pedido->envio()->firstOrFail()->entregado_en);
        $this->assertSame(0, Producto::firstOrFail()->existencias);
        $this->assertSame('devolucion_pendiente', $pedido->reservas()->firstOrFail()->estado);
        $this->assertDatabaseCount('pagos', 0);
        app(GestionarPedidos::class)->confirmarDevolucion($pedido, $this->usuario);
        $this->assertSame(1, Producto::firstOrFail()->existencias);
    }

    public function test_listado_filtra_busca_pagina_y_excluye_borradores(): void
    {
        $this->pedido(1, false);
        for ($numero = 0; $numero < 16; $numero++) {
            $this->pedido();
        }
        $this->get('/entregas?buscar=Cliente')->assertOk()->assertViewHas('pedidos', fn ($pedidos) => $pedidos->total() === 16 && $pedidos->count() === 15)->assertSee('buscar=Cliente', false);
        $this->get('/entregas?buscar=Cliente&page=2')->assertViewHas('pedidos', fn ($pedidos) => $pedidos->count() === 1);
        $this->get('/entregas?buscar=%25')->assertViewHas('pedidos', fn ($pedidos) => $pedidos->total() === 0);
        $this->get('/entregas?estado=enviado')->assertViewHas('pedidos', fn ($pedidos) => $pedidos->total() === 0);
        $pedido = Pedido::where('estado', 'confirmado')->orderByDesc('id')->firstOrFail();
        $this->empacar($pedido);
        $this->enviar($pedido);
        $this->get('/entregas?transportista=forza&estado=enviado')->assertViewHas('pedidos', fn ($pedidos) => $pedidos->total() === 1);
        $this->get('/entregas?transportista=mensajeria_propia')->assertViewHas('pedidos', fn ($pedidos) => $pedidos->total() === 0);
        $this->get('/entregas?buscar='.$pedido->id)->assertViewHas('pedidos', fn ($pedidos) => $pedidos->total() === 1);
    }

    public function test_textos_del_envio_y_entrega_se_escapan(): void
    {
        $pedido = $this->pedido();
        $this->empacar($pedido);
        $this->enviar($pedido, ['observaciones' => '<script>alert(1)</script>']);
        $this->get(route('entregas.mostrar', $pedido))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->post(route('entregas.entregar', $pedido), ['version' => $pedido->version, 'confirmar_entrega' => 1, 'observaciones_entrega' => str_repeat('x', 301)])->assertSessionHasErrors('observaciones_entrega');
        $this->assertNull($pedido->envio()->firstOrFail()->entregado_en);
    }
}
