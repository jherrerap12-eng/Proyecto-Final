<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RestablecerContrasenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_restablece_solo_la_cuenta_indicada_y_permite_ingresar_con_la_nueva(): void
    {
        $usuario = User::factory()->create(['email' => 'operador@example.test', 'password' => Hash::make('AnteriorSegura123!'), 'remember_token' => 'token-anterior']);
        $otro = User::factory()->create();
        $hashOtro = $otro->password;
        DB::table('password_reset_tokens')->insert(['email' => $usuario->email, 'token' => 'token-de-prueba', 'created_at' => now()]);
        $clave = 'ingreso:'.hash('sha256', $usuario->email.'|127.0.0.1');
        for ($intento = 0; $intento < 5; $intento++) {
            RateLimiter::hit($clave, 60);
        }

        $this->artisan('usuario:restablecer-contrasena')
            ->expectsQuestion('Correo electrónico de la cuenta', ' OPERADOR@example.test ')
            ->expectsQuestion('Nueva contraseña (mínimo 12 caracteres, letras y números)', 'NuevaSegura123!')
            ->expectsQuestion('Confirma la nueva contraseña', 'NuevaSegura123!')
            ->expectsOutput('Contraseña restablecida. Inicia sesión con tu correo y la nueva contraseña.')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('NuevaSegura123!', $usuario->refresh()->password));
        $this->assertFalse(Hash::check('AnteriorSegura123!', $usuario->password));
        $this->assertNotSame('token-anterior', $usuario->remember_token);
        $this->assertSame($hashOtro, $otro->refresh()->password);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertFalse(RateLimiter::tooManyAttempts($clave, 5));
        $this->post('/ingresar', ['email' => $usuario->email, 'password' => 'AnteriorSegura123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/ingresar', ['email' => $usuario->email, 'password' => 'NuevaSegura123!'])->assertRedirect(route('panel'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_confirmacion_distinta_no_modifica_la_cuenta(): void
    {
        $usuario = User::factory()->create();
        $hashOriginal = $usuario->password;
        $this->artisan('usuario:restablecer-contrasena')
            ->expectsQuestion('Correo electrónico de la cuenta', $usuario->email)
            ->expectsQuestion('Nueva contraseña (mínimo 12 caracteres, letras y números)', 'NuevaSegura123!')
            ->expectsQuestion('Confirma la nueva contraseña', 'DistintaSegura123!')
            ->expectsOutput('No se cambió la contraseña.')->assertFailed();
        $this->assertSame($hashOriginal, $usuario->refresh()->password);
    }

    public function test_contrasena_debil_no_se_guarda(): void
    {
        $usuario = User::factory()->create();
        $hashOriginal = $usuario->password;
        $this->artisan('usuario:restablecer-contrasena')
            ->expectsQuestion('Correo electrónico de la cuenta', $usuario->email)
            ->expectsQuestion('Nueva contraseña (mínimo 12 caracteres, letras y números)', 'corta')
            ->expectsQuestion('Confirma la nueva contraseña', 'corta')
            ->assertFailed();
        $this->assertSame($hashOriginal, $usuario->refresh()->password);
    }

    public function test_correo_inexistente_no_crea_otra_cuenta(): void
    {
        $this->artisan('usuario:restablecer-contrasena')
            ->expectsQuestion('Correo electrónico de la cuenta', 'inexistente@example.test')
            ->expectsOutput('No existe una cuenta con ese correo en la base configurada. No se creó ni modificó ninguna cuenta.')
            ->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }
}
