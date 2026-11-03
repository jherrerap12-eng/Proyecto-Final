<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'ClaveSegura2026';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function datos(array $cambios = []): array
    {
        return array_replace(['name' => 'Vendedora Live', 'email' => 'Vendedora@Babybu.test', 'rol' => 'operador', 'password' => self::CLAVE, 'password_confirmation' => self::CLAVE], $cambios);
    }

    public function test_administrador_crea_cuenta_con_rol_y_contrasena_cifrada(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('usuarios.store'), $this->datos())->assertRedirect(route('usuarios.index'))->assertSessionHasNoErrors();

        $nuevo = User::where('email', 'vendedora@babybu.test')->firstOrFail();
        $this->assertSame('operador', $nuevo->rol);
        $this->assertTrue($nuevo->activo);
        $this->assertNotSame(self::CLAVE, $nuevo->password);
        $this->assertTrue(Hash::check(self::CLAVE, $nuevo->password));
        $this->get(route('usuarios.index'))->assertOk()->assertSee('Vendedora Live')->assertSee('Operador');
    }

    public function test_valida_correo_unico_rol_existente_y_contrasena_robusta(): void
    {
        $this->actingAs(User::factory()->create(['email' => 'dueña@babybu.test']));

        $this->post(route('usuarios.store'), $this->datos(['email' => 'DUEÑA@babybu.test']))->assertSessionHasErrors('email');
        $this->post(route('usuarios.store'), $this->datos(['rol' => 'superusuario']))->assertSessionHasErrors('rol');
        $this->post(route('usuarios.store'), $this->datos(['password' => 'corta1', 'password_confirmation' => 'corta1']))->assertSessionHasErrors('password');
        $this->post(route('usuarios.store'), $this->datos(['password' => 'solamenteletrasaqui', 'password_confirmation' => 'solamenteletrasaqui']))->assertSessionHasErrors('password');
        $this->post(route('usuarios.store'), $this->datos(['password_confirmation' => 'OtraClave2026']))->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_no_se_puede_asignar_estado_ni_rol_por_campos_ocultos(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('usuarios.store'), $this->datos(['activo' => 0, 'desactivado_en' => now()->toDateTimeString(), 'id' => 999]));

        $nuevo = User::where('email', 'vendedora@babybu.test')->firstOrFail();
        $this->assertTrue($nuevo->activo);
        $this->assertNull($nuevo->desactivado_en);
        $this->assertNotSame(999, $nuevo->id);
    }

    public function test_edita_datos_y_rol_sin_cambiar_contrasena(): void
    {
        $this->actingAs(User::factory()->create());
        $otro = User::factory()->operador()->create();
        $hash = $otro->password;

        $this->put(route('usuarios.update', $otro), ['name' => 'Nuevo nombre', 'email' => $otro->email, 'rol' => 'supervisor', 'password' => 'Ignorada2026XX'])->assertSessionHasNoErrors();

        $otro->refresh();
        $this->assertSame('Nuevo nombre', $otro->name);
        $this->assertSame('supervisor', $otro->rol);
        $this->assertSame($hash, $otro->password);
    }

    public function test_desactivar_conserva_la_cuenta_e_impide_ingresar(): void
    {
        $this->actingAs(User::factory()->create());
        $otro = User::factory()->operador()->create(['password' => Hash::make(self::CLAVE)]);

        $this->patch(route('usuarios.estado', $otro), ['activo' => 0])->assertSessionHasNoErrors();
        $otro->refresh();
        $this->assertFalse($otro->activo);
        $this->assertNotNull($otro->desactivado_en);
        $this->assertDatabaseHas('users', ['id' => $otro->id]);

        $this->post('/salir');
        $this->post('/ingresar', ['email' => $otro->email, 'password' => self::CLAVE])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_reactivar_permite_ingresar_de_nuevo(): void
    {
        $this->actingAs(User::factory()->create());
        $otro = User::factory()->operador()->inactivo()->create(['password' => Hash::make(self::CLAVE)]);

        $this->patch(route('usuarios.estado', $otro), ['activo' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($otro->refresh()->activo);
        $this->assertNull($otro->desactivado_en);

        $this->post('/salir');
        $this->post('/ingresar', ['email' => $otro->email, 'password' => self::CLAVE])->assertRedirect(route('panel'));
        $this->assertAuthenticatedAs($otro);
    }

    public function test_sesion_abierta_se_cierra_cuando_la_cuenta_se_desactiva(): void
    {
        $otro = User::factory()->operador()->create();
        $this->actingAs($otro)->get('/panel')->assertOk();

        $otro->forceFill(['activo' => false, 'desactivado_en' => now()])->save();

        $this->get('/panel')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_protege_al_ultimo_administrador_y_a_la_propia_cuenta(): void
    {
        $administrador = User::factory()->create();
        $this->actingAs($administrador);

        $this->patch(route('usuarios.estado', $administrador), ['activo' => 0])->assertSessionHasErrors('estado');
        $this->put(route('usuarios.update', $administrador), ['name' => $administrador->name, 'email' => $administrador->email, 'rol' => 'operador'])->assertSessionHasErrors('rol');
        $this->assertTrue($administrador->refresh()->activo);
        $this->assertSame('administrador', $administrador->rol);

        // Con otro administrador activo sí se puede desactivar a uno de los dos.
        $segundo = User::factory()->create();
        $this->patch(route('usuarios.estado', $segundo), ['activo' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($segundo->refresh()->activo);
    }

    public function test_administradores_desactivados_no_cuentan_como_respaldo(): void
    {
        $unico = User::factory()->create();
        User::factory()->inactivo()->create(); // Administrador desactivado: no cuenta.
        $this->assertSame(1, User::where('rol', 'administrador')->where('activo', true)->count());

        // Simula a otro administrador que actúa con una vista anterior.
        $this->actingAs(User::factory()->create());
        $this->patch(route('usuarios.estado', auth()->user()), ['activo' => 0])->assertSessionHasErrors('estado');
        $this->put(route('usuarios.update', $unico), ['name' => $unico->name, 'email' => $unico->email, 'rol' => 'supervisor'])->assertSessionHasNoErrors();
        // Ahora solo queda el actual; no puede quitarse el rol.
        $this->put(route('usuarios.update', auth()->user()), ['name' => 'X', 'email' => auth()->user()->email, 'rol' => 'supervisor'])->assertSessionHasErrors('rol');
    }

    public function test_cambio_de_propia_contrasena_exige_la_actual(): void
    {
        $usuario = User::factory()->operador()->create(['password' => Hash::make(self::CLAVE)]);
        $this->actingAs($usuario);
        $nueva = 'NuevaClave2027';

        $this->put(route('cuenta.contrasena.actualizar'), ['contrasena_actual' => 'Equivocada2026', 'password' => $nueva, 'password_confirmation' => $nueva])->assertSessionHasErrors('contrasena_actual');
        $this->put(route('cuenta.contrasena.actualizar'), ['contrasena_actual' => self::CLAVE, 'password' => self::CLAVE, 'password_confirmation' => self::CLAVE])->assertSessionHasErrors('password');
        $this->put(route('cuenta.contrasena.actualizar'), ['contrasena_actual' => self::CLAVE, 'password' => 'debil', 'password_confirmation' => 'debil'])->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check(self::CLAVE, $usuario->refresh()->password));

        $this->put(route('cuenta.contrasena.actualizar'), ['contrasena_actual' => self::CLAVE, 'password' => $nueva, 'password_confirmation' => $nueva])->assertRedirect(route('panel'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check($nueva, $usuario->refresh()->password));
        // La sesión actual sigue abierta.
        $this->get('/panel')->assertOk();

        $this->post('/salir');
        $this->post('/ingresar', ['email' => $usuario->email, 'password' => self::CLAVE])->assertSessionHasErrors('email');
        $this->post('/ingresar', ['email' => $usuario->email, 'password' => $nueva])->assertRedirect(route('panel'));
    }

    public function test_cambio_de_contrasena_cierra_las_otras_sesiones(): void
    {
        $usuario = User::factory()->operador()->create(['password' => Hash::make(self::CLAVE)]);
        // Sesión en "otro dispositivo": guarda la huella de la contraseña vigente.
        $this->actingAs($usuario)->get('/panel')->assertOk();

        $usuario->forceFill(['password' => 'CambiadaEnOtroLado2026'])->save();

        $this->get('/panel')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_administrador_restablece_contrasena_de_otro_confirmando_la_suya(): void
    {
        $administrador = User::factory()->create(['password' => Hash::make(self::CLAVE)]);
        $otro = User::factory()->operador()->create();
        $this->actingAs($administrador);
        $nueva = 'Restablecida2026';

        $this->put(route('usuarios.contrasena', $otro), ['password' => $nueva, 'password_confirmation' => $nueva, 'contrasena_responsable' => 'Equivocada2026'])->assertSessionHasErrors('contrasena_responsable');
        $this->assertFalse(Hash::check($nueva, $otro->refresh()->password));

        $this->put(route('usuarios.contrasena', $otro), ['password' => $nueva, 'password_confirmation' => $nueva, 'contrasena_responsable' => self::CLAVE])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check($nueva, $otro->refresh()->password));

        // Para la propia cuenta debe usarse el cambio con contraseña actual.
        $this->put(route('usuarios.contrasena', $administrador), ['password' => $nueva, 'password_confirmation' => $nueva, 'contrasena_responsable' => self::CLAVE])->assertRedirect(route('cuenta.contrasena'));
        $this->assertTrue(Hash::check(self::CLAVE, $administrador->refresh()->password));
    }

    public function test_comando_crea_primer_administrador_y_luego_pregunta_rol(): void
    {
        $this->artisan('usuario:crear')
            ->expectsQuestion('Nombre del usuario', 'Dueña')
            ->expectsQuestion('Correo electrónico', 'duena@babybu.test')
            ->expectsOutput('No hay administradores activos: esta cuenta se creará como administrador.')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, letras y números)', self::CLAVE)
            ->expectsQuestion('Confirma la contraseña', self::CLAVE)
            ->assertSuccessful();
        $this->assertSame('administrador', User::where('email', 'duena@babybu.test')->value('rol'));

        $this->artisan('usuario:crear')
            ->expectsQuestion('Nombre del usuario', 'Ayudante')
            ->expectsQuestion('Correo electrónico', 'ayudante@babybu.test')
            ->expectsChoice('Rol', 'operador', ['administrador', 'supervisor', 'operador'])
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, letras y números)', self::CLAVE)
            ->expectsQuestion('Confirma la contraseña', self::CLAVE)
            ->assertSuccessful();
        $this->assertSame('operador', User::where('email', 'ayudante@babybu.test')->value('rol'));
    }
}
