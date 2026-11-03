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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PermisosTest extends TestCase
{
    use RefreshDatabase;

    private User $preparador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparador = User::factory()->create();
    }

    private function pieza(): Producto
    {
        return app(GestionarInventario::class)->registrar(['codigo' => 'PM-'.(Producto::count() + 1), 'nombre' => 'Pieza permisos', 'costo' => '10.00', 'precio' => '100.00', 'existencias' => 1], $this->preparador);
    }

    private function pedidoConfirmado(): Pedido
    {
        $cliente = Cliente::create(['nombre' => 'Cliente permisos', 'telefono' => '55550000']);
        $pedido = app(GestionarPedidos::class)->guardar(['cliente_id' => $cliente->id, 'modalidad' => 'contra_entrega', 'productos' => [$this->pieza()->id], 'referencia' => (string) Str::uuid()], $this->preparador);
        app(GestionarPedidos::class)->confirmar($pedido, $pedido->version, $this->preparador);

        return $pedido->refresh();
    }

    private function pagoPendiente(): Pago
    {
        $pedido = $this->pedidoConfirmado();

        return app(RegistrarPago::class)->registrar($pedido->id, ['importe' => '30.00', 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()], $this->preparador);
    }

    public static function roles(): array
    {
        return [
            'administrador' => ['administrador', true, true],
            'supervisor' => ['supervisor', true, false],
            'operador' => ['operador', false, false],
        ];
    }

    #[DataProvider('roles')]
    public function test_verificar_pagos_segun_rol(string $rol, bool $permitido): void
    {
        $pago = $this->pagoPendiente();
        $this->actingAs(User::factory()->create(['rol' => $rol]));

        $respuesta = $this->post(route('pagos.verificar', $pago), ['confirmar_verificacion' => 1]);

        if ($permitido) {
            $respuesta->assertRedirect(route('pagos.show', $pago->pedido_id));
            $this->assertNotNull($pago->refresh()->verificado_en);
        } else {
            $respuesta->assertForbidden();
            $this->assertNull($pago->refresh()->verificado_en);
        }
    }

    #[DataProvider('roles')]
    public function test_ajustar_inventario_segun_rol(string $rol, bool $permitido): void
    {
        $pieza = $this->pieza();
        $this->actingAs(User::factory()->create(['rol' => $rol]));

        $respuesta = $this->post(route('inventario.ajustar', $pieza), ['tipo' => 'salida', 'cantidad' => 1, 'motivo' => 'Pieza dañada', 'referencia' => (string) Str::uuid()]);

        if ($permitido) {
            $respuesta->assertSessionHasNoErrors();
            $this->assertSame(0, $pieza->refresh()->existencias);
        } else {
            $respuesta->assertForbidden();
            $this->assertSame(1, $pieza->refresh()->existencias);
            $this->assertSame(1, $pieza->movimientos()->count());
        }
    }

    #[DataProvider('roles')]
    public function test_cancelar_pedidos_segun_rol(string $rol, bool $permitido): void
    {
        $pedido = $this->pedidoConfirmado();
        $this->actingAs(User::factory()->create(['rol' => $rol]));

        $respuesta = $this->post(route('pedidos.cancelar', $pedido), ['version' => $pedido->version, 'motivo' => 'Cliente desistió']);

        if ($permitido) {
            $respuesta->assertSessionHasNoErrors();
            $this->assertSame('cancelado', $pedido->refresh()->estado);
        } else {
            $respuesta->assertForbidden();
            $this->assertSame('confirmado', $pedido->refresh()->estado);
        }
    }

    #[DataProvider('roles')]
    public function test_administrar_usuarios_segun_rol(string $rol, bool $_, bool $permitido): void
    {
        $objetivo = User::factory()->operador()->create();
        $this->actingAs(User::factory()->create(['rol' => $rol]));
        $estado = $permitido ? 200 : 403;

        $this->get(route('usuarios.index'))->assertStatus($estado);
        $this->get(route('usuarios.create'))->assertStatus($estado);
        $this->get(route('usuarios.edit', $objetivo))->assertStatus($estado);
        $alta = $this->post(route('usuarios.store'), ['name' => 'Intruso', 'email' => 'intruso@babybu.test', 'rol' => 'administrador', 'password' => 'ClaveIntruso2026', 'password_confirmation' => 'ClaveIntruso2026']);
        $cambio = $this->put(route('usuarios.update', $objetivo), ['name' => $objetivo->name, 'email' => $objetivo->email, 'rol' => 'administrador']);
        $baja = $this->patch(route('usuarios.estado', $objetivo), ['activo' => 0]);

        if ($permitido) {
            $this->assertDatabaseHas('users', ['email' => 'intruso@babybu.test']);
            $this->assertSame('administrador', $objetivo->refresh()->rol);
            $this->assertFalse($objetivo->activo);
        } else {
            $alta->assertForbidden();
            $cambio->assertForbidden();
            $baja->assertForbidden();
            $this->assertDatabaseMissing('users', ['email' => 'intruso@babybu.test']);
            $this->assertSame('operador', $objetivo->refresh()->rol);
            $this->assertTrue($objetivo->activo);
        }
    }

    public function test_operaciones_comunes_siguen_disponibles_para_operador(): void
    {
        $pedido = $this->pedidoConfirmado();
        $this->actingAs(User::factory()->operador()->create());

        foreach (['/panel', '/clientes', '/inventario', '/pedidos', '/pagos', '/entregas', route('pagos.show', $pedido->id), route('cuenta.contrasena')] as $ruta) {
            $this->get($ruta)->assertOk();
        }
        $this->post(route('pagos.store', $pedido->id), ['importe' => '10.00', 'metodo' => 'efectivo', 'fecha' => now()->toDateString(), 'clave_operacion' => (string) Str::uuid()])->assertSessionHasNoErrors();
        $this->assertSame(1, $pedido->pagos()->whereNull('verificado_en')->count());
    }

    public function test_la_interfaz_oculta_acciones_no_permitidas(): void
    {
        $pago = $this->pagoPendiente();
        $this->actingAs(User::factory()->operador()->create());

        $this->get(route('pagos.show', $pago->pedido_id))->assertOk()->assertDontSee('Verificar abono');
        $this->get(route('pedidos.mostrar', $pago->pedido_id))->assertOk()->assertDontSee('Cancelar pedido');
        $this->get('/panel')->assertOk()->assertDontSee(route('usuarios.index'));

        $this->actingAs(User::factory()->supervisor()->create());
        $this->get(route('pagos.show', $pago->pedido_id))->assertSee('Verificar abono');
        $this->get(route('pedidos.mostrar', $pago->pedido_id))->assertSee('Cancelar pedido');
        $this->get('/panel')->assertDontSee(route('usuarios.index'));
    }

    public function test_cuenta_desactivada_pierde_todos_sus_permisos(): void
    {
        $pago = $this->pagoPendiente();
        $supervisor = User::factory()->supervisor()->create();
        $this->actingAs($supervisor)->get('/panel')->assertOk();
        $supervisor->forceFill(['activo' => false])->save();

        $this->post(route('pagos.verificar', $pago), ['confirmar_verificacion' => 1])->assertRedirect(route('login'));
        $this->assertNull($pago->refresh()->verificado_en);
    }
}
