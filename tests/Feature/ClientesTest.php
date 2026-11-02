<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_todas_las_operaciones_requieren_sesion(): void
    {
        $cliente = Cliente::create(['nombre' => 'Ana', 'telefono' => '55551234']);
        foreach (['/clientes', '/clientes/create', "/clientes/{$cliente->id}", "/clientes/{$cliente->id}/edit"] as $ruta) {
            $this->get($ruta)->assertRedirect(route('login'));
        }
        $this->post('/clientes', [])->assertRedirect(route('login'));
        $this->put("/clientes/{$cliente->id}", [])->assertRedirect(route('login'));
        $this->delete("/clientes/{$cliente->id}")->assertRedirect(route('login'));
        $this->assertDatabaseCount('clientes', 1);
        $this->assertNotSoftDeleted($cliente);
    }

    public function test_registro_consulta_edicion_y_baja_conservan_el_registro(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/clientes/create')->assertOk();
        $this->post('/clientes', ['nombre' => ' Ana López ', 'telefono' => '+502 5555-1234', 'correo' => '', 'direccion' => ''])
            ->assertSessionHasNoErrors()->assertRedirect();
        $cliente = Cliente::firstOrFail();
        $this->assertSame('Ana López', $cliente->nombre);
        $this->assertNull($cliente->correo);
        $this->get(route('clientes.show', $cliente))->assertOk()->assertSee('Ana López');
        $this->get(route('clientes.edit', $cliente))->assertOk()->assertSee('+502 5555-1234');
        $this->put(route('clientes.update', $cliente), ['nombre' => 'Ana Pérez', 'telefono' => '55559999', 'correo' => 'ana@example.test', 'direccion' => 'Zona 1'])
            ->assertSessionHasNoErrors()->assertRedirect(route('clientes.show', $cliente));
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'nombre' => 'Ana Pérez', 'direccion' => 'Zona 1']);
        $this->delete(route('clientes.destroy', $cliente))->assertRedirect(route('clientes.index'));
        $this->assertSoftDeleted($cliente);
        $this->assertDatabaseCount('clientes', 1);
        $this->get('/clientes')->assertOk()->assertDontSee('Ana Pérez');
        $this->get(route('clientes.show', $cliente))->assertNotFound();
        $this->put(route('clientes.update', $cliente), ['nombre' => 'Otra', 'telefono' => '55551234'])->assertNotFound();
    }

    public function test_validaciones_impiden_registros_y_ediciones_invalidas(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/clientes', ['nombre' => ' ', 'telefono' => 'abc', 'correo' => 'incorrecto', 'direccion' => str_repeat('a', 501)])
            ->assertSessionHasErrors(['nombre', 'telefono', 'correo', 'direccion']);
        $this->assertDatabaseCount('clientes', 0);
        foreach (['123', '----------------', '1234567890123456', '12345678+', ['55551234']] as $telefono) {
            $this->post('/clientes', ['nombre' => 'Ana', 'telefono' => $telefono])->assertSessionHasErrors('telefono');
        }
        $cliente = Cliente::create(['nombre' => 'Ana', 'telefono' => '55551234']);
        $this->put(route('clientes.update', $cliente), ['nombre' => '', 'telefono' => '55551234'])->assertSessionHasErrors('nombre');
        $this->assertSame('Ana', $cliente->fresh()->nombre);
    }

    public function test_busqueda_por_contacto_y_paginacion_conservan_el_filtro(): void
    {
        $this->actingAs(User::factory()->create());
        for ($numero = 1; $numero <= 12; $numero++) {
            Cliente::create(['nombre' => sprintf('Comprador %02d', $numero), 'telefono' => '55551234', 'correo' => 'comprador@example.test']);
        }
        Cliente::create(['nombre' => 'Otra persona', 'telefono' => '44441234']);
        $this->get('/clientes?buscar=Comprador')->assertOk()->assertSee('Comprador 01')->assertDontSee('Comprador 11')
            ->assertDontSee('Otra persona')->assertSee('buscar=Comprador', false);
        $this->get('/clientes?buscar=Comprador&page=2')->assertOk()->assertSee('Comprador 11')->assertDontSee('Comprador 01');
        $this->get('/clientes?buscar=44441234')->assertOk()->assertSee('Otra persona')->assertDontSee('Comprador 01');
        $this->get('/clientes?buscar=comprador%40example.test')->assertOk()->assertSee('Comprador 01')->assertDontSee('Otra persona');
        $this->get('/clientes?buscar=%25')->assertOk()->assertDontSee('Comprador 01');
        $this->get('/clientes?buscar[]=invalido')->assertSessionHasErrors('buscar');
    }

    public function test_texto_del_cliente_se_muestra_escapado(): void
    {
        $this->actingAs(User::factory()->create());
        $cliente = Cliente::create(['nombre' => '<script>alert(1)</script>', 'telefono' => '55551234']);
        $this->get(route('clientes.show', $cliente))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}
