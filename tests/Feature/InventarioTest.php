<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function datos(array $cambios = []): array
    {
        return array_replace(['codigo' => 'BB-001', 'nombre' => 'Muñeca', 'descripcion' => 'Pieza individual', 'costo' => '30.25', 'precio' => '50.50', 'existencias' => 1], $cambios);
    }

    private function registrar(array $cambios = []): Producto
    {
        $this->post(route('inventario.guardar'), $this->datos($cambios))->assertSessionHasNoErrors()->assertRedirect();

        return Producto::where('codigo', strtoupper(trim($cambios['codigo'] ?? 'BB-001')))->firstOrFail();
    }

    private function movimiento(array $cambios = []): array
    {
        return array_replace(['tipo' => 'salida', 'cantidad' => 1, 'motivo' => 'Corrección de inventario', 'referencia' => (string) Str::uuid()], $cambios);
    }

    public function test_todas_las_operaciones_exigen_sesion(): void
    {
        $this->get('/inventario')->assertRedirect(route('login'));
        $this->get('/inventario/crear')->assertRedirect(route('login'));
        $this->post('/inventario', $this->datos())->assertRedirect(route('login'));
        $this->get('/inventario/1')->assertRedirect(route('login'));
        $this->get('/inventario/1/editar')->assertRedirect(route('login'));
        $this->put('/inventario/1', $this->datos())->assertRedirect(route('login'));
        $this->post('/inventario/1/movimientos', $this->movimiento())->assertRedirect(route('login'));
        $this->patch('/inventario/1/estado', ['activo' => 0, 'version' => 1])->assertRedirect(route('login'));
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_registra_pieza_normaliza_codigo_y_guarda_apertura_con_responsable(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $producto = $this->registrar(['codigo' => ' bb-001 ']);
        $this->assertSame('BB-001', $producto->codigo);
        $this->assertSame('30.25', $producto->costo);
        $this->assertSame(1, $producto->existencias);
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $producto->id, 'usuario_id' => $usuario->id, 'tipo' => 'apertura', 'cantidad' => 1, 'existencias_anteriores' => 0, 'existencias_posteriores' => 1]);
        $this->get(route('inventario.mostrar', $producto))->assertOk()->assertSee('Historial de movimientos');
    }

    public function test_rechaza_codigo_duplicado_pero_permite_juguetes_iguales_con_otro_codigo(): void
    {
        $this->actingAs(User::factory()->create());
        $this->registrar();
        $this->post(route('inventario.guardar'), $this->datos(['codigo' => 'bb-001']))->assertSessionHasErrors('codigo');
        $this->registrar(['codigo' => 'BB-002']);
        $this->assertDatabaseCount('productos', 2);
    }

    public function test_rechaza_costos_negativos_decimales_excesivos_y_multiples_piezas(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('inventario.guardar'), $this->datos(['codigo' => 'mal codigo', 'nombre' => '', 'costo' => -1, 'precio' => '5.123', 'existencias' => 2]))
            ->assertSessionHasErrors(['codigo', 'nombre', 'costo', 'precio', 'existencias']);
        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_editar_datos_no_modifica_existencias_y_rechaza_edicion_obsoleta(): void
    {
        $this->actingAs(User::factory()->create());
        $producto = $this->registrar();
        $datos = $this->datos(['nombre' => 'Muñeca actualizada', 'version' => $producto->version, 'existencias' => 0]);
        $this->put(route('inventario.actualizar', $producto), $datos)->assertSessionHasNoErrors();
        $this->assertSame(1, $producto->refresh()->existencias);
        $this->assertSame('Muñeca actualizada', $producto->nombre);
        $this->put(route('inventario.actualizar', $producto), array_replace($datos, ['nombre' => 'Cambio viejo']))->assertSessionHasErrors('version');
        $this->assertSame('Muñeca actualizada', $producto->refresh()->nombre);
        $this->assertDatabaseCount('movimientos_inventario', 1);
    }

    public function test_salida_no_se_duplica_por_reenvio_y_no_admite_stock_negativo(): void
    {
        $this->actingAs(User::factory()->create());
        $producto = $this->registrar();
        $datos = $this->movimiento();
        $this->post(route('inventario.ajustar', $producto), $datos)->assertSessionHasNoErrors();
        $this->post(route('inventario.ajustar', $producto), $datos)->assertSessionHasNoErrors();
        $this->assertSame(0, $producto->refresh()->existencias);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->post(route('inventario.ajustar', $producto), $this->movimiento())->assertSessionHasErrors('cantidad');
        $this->assertDatabaseCount('movimientos_inventario', 2);
    }

    public function test_entrada_y_salida_respetan_una_sola_pieza(): void
    {
        $this->actingAs(User::factory()->create());
        $producto = $this->registrar(['existencias' => 0]);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->post(route('inventario.ajustar', $producto), $this->movimiento(['tipo' => 'entrada']))->assertSessionHasNoErrors();
        $this->post(route('inventario.ajustar', $producto), $this->movimiento(['tipo' => 'entrada']))->assertSessionHasErrors('cantidad');
        $this->post(route('inventario.ajustar', $producto), $this->movimiento(['cantidad' => 2]))->assertSessionHasErrors('cantidad');
        $this->assertSame(1, $producto->refresh()->existencias);
        $this->assertDatabaseCount('movimientos_inventario', 1);
    }

    public function test_referencia_no_se_puede_reutilizar_con_otros_datos(): void
    {
        $this->actingAs(User::factory()->create());
        $producto = $this->registrar();
        $datos = $this->movimiento();
        $this->post(route('inventario.ajustar', $producto), $datos)->assertSessionHasNoErrors();
        $this->post(route('inventario.ajustar', $producto), array_replace($datos, ['tipo' => 'entrada']))->assertSessionHasErrors('referencia');
        $this->assertSame(0, $producto->refresh()->existencias);
    }

    public function test_desactiva_sin_borrar_historial_ni_reutilizar_codigo_y_permite_reactivar(): void
    {
        $this->actingAs(User::factory()->create());
        $producto = $this->registrar();
        $this->patch(route('inventario.estado', $producto), ['activo' => 0, 'version' => $producto->version])->assertSessionHasNoErrors();
        $this->assertFalse($producto->refresh()->activo);
        $this->assertSame(1, $producto->existencias);
        $this->post(route('inventario.ajustar', $producto), $this->movimiento())->assertSessionHasErrors('cantidad');
        $this->post(route('inventario.guardar'), $this->datos())->assertSessionHasErrors('codigo');
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->patch(route('inventario.estado', $producto), ['activo' => 1, 'version' => $producto->version])->assertSessionHasNoErrors();
        $this->assertTrue($producto->refresh()->activo);
    }

    public function test_busqueda_filtros_y_paginacion(): void
    {
        $this->actingAs(User::factory()->create());
        for ($numero = 1; $numero <= 17; $numero++) {
            $this->registrar(['codigo' => sprintf('BB-%03d', $numero)]);
        }
        $this->get('/inventario?buscar=Muñeca')->assertOk()->assertViewHas('productos', fn ($productos) => $productos->total() === 17 && $productos->count() === 15);
        $this->get('/inventario?buscar=Muñeca&page=2')->assertOk()->assertViewHas('productos', fn ($productos) => $productos->count() === 2)->assertSee('buscar=Mu%C3%B1eca', false);
        $this->get('/inventario?buscar=BB-017')->assertOk()->assertSee('BB-017')->assertDontSee('BB-016');
        $this->get('/inventario?existencias=sin')->assertOk()->assertViewHas('productos', fn ($productos) => $productos->total() === 0);
    }

    public function test_salida_protege_edicion_abierta_y_texto_no_se_ejecuta_como_html(): void
    {
        $this->actingAs(User::factory()->create());
        $producto = $this->registrar(['nombre' => '<script>alert(1)</script>']);
        $version = $producto->version;
        $this->get(route('inventario.mostrar', $producto))->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
        $this->post(route('inventario.ajustar', $producto), $this->movimiento())->assertSessionHasNoErrors();
        $this->put(route('inventario.actualizar', $producto), $this->datos(['version' => $version]))->assertSessionHasErrors('version');
    }

    public function test_codigo_cero_se_puede_buscar_y_paginacion_esta_en_espanol(): void
    {
        $this->actingAs(User::factory()->create());
        $this->registrar(['codigo' => '0']);
        $this->registrar(['codigo' => 'ABC']);
        $this->get('/inventario?buscar=0')->assertOk()->assertViewHas('productos', fn ($productos) => $productos->total() === 1);
        $this->assertSame('« Anterior', __('pagination.previous'));
        $this->assertSame('Mostrando', __('Showing'));
    }
}
