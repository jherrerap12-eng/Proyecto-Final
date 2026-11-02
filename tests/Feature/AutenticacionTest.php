<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_visitante_no_puede_ver_el_panel(): void
    {
        $this->get('/panel')->assertRedirect(route('login'));
        $this->get('/ingresar')->assertOk()->assertSee('Iniciar sesión');
    }

    public function test_usuario_puede_ingresar_y_salir(): void
    {
        $usuario = User::factory()->create(['password' => Hash::make('PruebaSegura123!')]);

        $this->post('/ingresar', ['email' => $usuario->email, 'password' => 'PruebaSegura123!'])
            ->assertRedirect(route('panel'));
        $this->assertAuthenticatedAs($usuario);
        $this->get('/panel')->assertOk()->assertSee($usuario->name);
        $this->get('/ingresar')->assertRedirect(route('panel'));

        $this->post('/salir')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/panel')->assertRedirect(route('login'));
    }

    public function test_contrasena_incorrecta_no_autentica(): void
    {
        $usuario = User::factory()->create();
        $this->post('/ingresar', ['email' => $usuario->email, 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_campos_invalidos_se_rechazan(): void
    {
        $this->post('/ingresar', ['email' => 'sin-correo', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_se_limita_el_numero_de_intentos(): void
    {
        $usuario = User::factory()->create(['password' => Hash::make('PruebaSegura123!')]);
        for ($intento = 0; $intento < 5; $intento++) {
            $this->post('/ingresar', ['email' => $usuario->email, 'password' => 'incorrecta']);
        }

        $this->post('/ingresar', ['email' => $usuario->email, 'password' => 'PruebaSegura123!'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_creacion_de_cuenta_interna_cifra_la_contrasena(): void
    {
        $this->artisan('usuario:crear')
            ->expectsQuestion('Nombre del usuario', 'Operador de prueba')
            ->expectsQuestion('Correo electrónico', 'OPERADOR@example.test')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, letras y números)', 'PruebaSegura123!')
            ->expectsQuestion('Confirma la contraseña', 'PruebaSegura123!')
            ->assertSuccessful();

        $usuario = User::where('email', 'operador@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('PruebaSegura123!', $usuario->password));
    }
}
