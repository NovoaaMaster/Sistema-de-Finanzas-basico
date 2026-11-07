<?php

namespace Tests\Feature\Pantallas;

use App\Models\ComprobanteElectronico;
use App\Models\User;
use Database\Seeders\SerieElectronicaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

class PanelYConfiguracionTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
        $this->seed(SerieElectronicaSeeder::class);
        $this->actingAs(User::factory()->create());
        Carbon::setTestNow('2026-11-06 15:00:00');
    }

    public function test_el_panel_cuenta_ventas_por_facturar_aceptados_de_hoy_y_problemas(): void
    {
        $this->crearVentaPclap();
        $this->crearVentaPclap();
        ComprobanteElectronico::factory()->count(3)->create(['fecha_emision' => '2026-11-06 09:00:00']);
        ComprobanteElectronico::factory()->create(['fecha_emision' => '2026-11-05 09:00:00']);
        ComprobanteElectronico::factory()->estado('RECHAZADO')->create();
        ComprobanteElectronico::factory()->estado('ERROR')->create();

        $this->get('/')
            ->assertOk()
            ->assertViewHas('porFacturar', 2)
            ->assertViewHas('aceptadosHoy', 3)
            ->assertViewHas('conProblemas', 2);
    }

    public function test_el_panel_avisa_si_no_puede_leer_pclap(): void
    {
        config(['database.connections.pclap.database' => '/ruta/que/no/existe.sqlite']);
        app('db')->purge('pclap');

        $this->get('/')
            ->assertOk()
            ->assertSee('No se pudo leer la base de PCLAP');
    }

    public function test_configuracion_muestra_emisor_modo_y_series(): void
    {
        $this->get('/configuracion')
            ->assertOk()
            ->assertSee('20000000001')
            ->assertSee('Beta')
            ->assertSee('F001')
            ->assertSee('BC01');
    }
}
