<?php

namespace Tests\Feature\Pantallas;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_sesion_redirige_al_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/comprobantes')->assertRedirect('/login');
    }

    public function test_muestra_el_formulario_de_login(): void
    {
        $this->get('/login')->assertOk()->assertSee('Acceso a Finanzas');
    }

    public function test_inicia_sesion_con_credenciales_correctas(): void
    {
        $usuario = User::factory()->create(['email' => 'admin@finanzas.test', 'password' => 'secreto123']);

        $this->post('/login', ['email' => 'admin@finanzas.test', 'password' => 'secreto123'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_rechaza_credenciales_incorrectas(): void
    {
        User::factory()->create(['email' => 'admin@finanzas.test', 'password' => 'secreto123']);

        $this->from('/login')->post('/login', ['email' => 'admin@finanzas.test', 'password' => 'otra'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_cierra_sesion(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}
