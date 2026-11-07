<?php

namespace Tests\Feature\Pantallas;

use App\Models\ComprobanteElectronico;
use App\Models\User;
use App\Services\Facturacion\ServicioSunat;
use Database\Seeders\SerieElectronicaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\ServicioSunatFalso;
use Tests\TestCase;

class NotaCreditoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SerieElectronicaSeeder::class);
        Storage::fake('local');
        $this->app->instance(ServicioSunat::class, new ServicioSunatFalso);
        $this->actingAs(User::factory()->create());
    }

    public function test_muestra_el_formulario_con_el_comprobante_y_los_motivos(): void
    {
        $factura = ComprobanteElectronico::factory()->factura()->conDetalle()->create(['correlativo' => 12]);

        $this->get("/comprobantes/{$factura->id}/nota-credito")
            ->assertOk()
            ->assertSee('F001-12')
            ->assertSee('Anulación de la operación')
            ->assertSee('Devolución total');
    }

    public function test_emite_la_nota_de_credito(): void
    {
        $factura = ComprobanteElectronico::factory()->factura()->conDetalle()->create();

        $this->post("/comprobantes/{$factura->id}/nota-credito", ['motivo_codigo' => '06', 'motivo_descripcion' => 'Devolución total del producto'])
            ->assertRedirect('/comprobantes')
            ->assertSessionHas('success');

        $nota = ComprobanteElectronico::where('tipo_doc', '07')->firstOrFail();
        $this->assertSame('FC01-1', $nota->numero());
        $this->assertSame('06', $nota->motivo_codigo);
    }

    public function test_valida_el_motivo(): void
    {
        $factura = ComprobanteElectronico::factory()->factura()->conDetalle()->create();

        $this->post("/comprobantes/{$factura->id}/nota-credito", ['motivo_codigo' => '99', 'motivo_descripcion' => ''])
            ->assertSessionHasErrors(['motivo_codigo', 'motivo_descripcion']);
    }

    public function test_no_emite_nota_sobre_un_comprobante_rechazado(): void
    {
        $rechazada = ComprobanteElectronico::factory()->estado('RECHAZADO')->conDetalle()->create();

        $this->get("/comprobantes/{$rechazada->id}/nota-credito")->assertRedirect('/comprobantes');
        $this->post("/comprobantes/{$rechazada->id}/nota-credito", ['motivo_codigo' => '01', 'motivo_descripcion' => 'Anulación'])
            ->assertSessionHas('error');

        $this->assertSame(0, ComprobanteElectronico::where('tipo_doc', '07')->count());
    }
}
