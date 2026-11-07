<?php

namespace Tests\Feature\Pantallas;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_menu_marca_la_pagina_actual(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/configuracion')
            ->assertOk()
            ->assertSee('href="'.route('configuracion').'" aria-current="page"', false)
            ->assertDontSee('href="'.route('panel').'" aria-current="page"', false);
    }

    public function test_la_marca_de_finanzas_lleva_su_logo_y_no_el_nombre_de_pclap(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/configuracion')->getContent();

        $this->assertStringContainsString('class="logo-finanzas"', $html);
        $this->assertStringNotContainsString('PCLAP Finanzas</a>', $html);
    }
}
