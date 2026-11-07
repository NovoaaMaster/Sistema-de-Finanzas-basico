<?php

namespace Tests\Feature\Pantallas;

use App\Models\ComprobanteElectronico;
use App\Models\User;
use App\Services\Facturacion\RepresentacionImpresa;
use App\Services\Facturacion\ServicioSunat;
use Database\Seeders\SerieElectronicaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ConEsquemaPclap;
use Tests\Fakes\ServicioSunatFalso;
use Tests\TestCase;

class ComprobantesTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    private ServicioSunatFalso $sunat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaPclap();
        $this->seed(SerieElectronicaSeeder::class);
        Storage::fake('local');
        $this->sunat = new ServicioSunatFalso;
        $this->app->instance(ServicioSunat::class, $this->sunat);
        $this->actingAs(User::factory()->create());
    }

    public function test_lista_los_comprobantes_con_su_estado(): void
    {
        ComprobanteElectronico::factory()->create(['serie' => 'B001', 'correlativo' => 5, 'sunat_mensaje' => 'La Boleta numero B001-5, ha sido aceptada']);
        ComprobanteElectronico::factory()->estado('ERROR')->create(['serie' => 'B001', 'correlativo' => 6]);

        $this->get('/comprobantes')
            ->assertOk()
            ->assertSee('B001-5')
            ->assertSee('ACEPTADO')
            ->assertSee('B001-6')
            ->assertSee('ERROR')
            ->assertSee('Reenviar');
    }

    public function test_descarga_el_pdf_con_la_representacion_impresa(): void
    {
        $boleta = ComprobanteElectronico::factory()->conDetalle()->create(['hash' => 'hAsH+de=Prueba']);

        $respuesta = $this->get("/comprobantes/{$boleta->id}/pdf")->assertOk();

        $this->assertSame('application/pdf', $respuesta->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }

    public function test_la_vista_del_pdf_lleva_qr_hash_y_monto_en_letras(): void
    {
        $boleta = ComprobanteElectronico::factory()->conDetalle()->create(['hash' => 'hAsH+de=Prueba', 'serie' => 'B001', 'correlativo' => 9]);

        $html = view('pdf.comprobante', app(RepresentacionImpresa::class)->datos($boleta))->render();

        $this->assertStringContainsString('BOLETA DE VENTA ELECTRÓNICA', $html);
        $this->assertStringContainsString('B001-9', $html);
        $this->assertStringContainsString('SON CIENTO CUARENTA Y CUATRO CON 00/100 SOLES', $html);
        $this->assertStringContainsString('hAsH+de=Prueba', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('placa h510 - S/N: 515151', $html);
    }

    public function test_descarga_el_xml_y_el_cdr(): void
    {
        Storage::disk('local')->put('comprobantes/20000000001-03-B001-3.xml', '<Invoice/>');
        Storage::disk('local')->put('comprobantes/R-20000000001-03-B001-3.zip', 'ZIP');
        $boleta = ComprobanteElectronico::factory()->create([
            'serie' => 'B001',
            'correlativo' => 3,
            'xml_path' => 'comprobantes/20000000001-03-B001-3.xml',
            'cdr_path' => 'comprobantes/R-20000000001-03-B001-3.zip',
        ]);

        $this->get("/comprobantes/{$boleta->id}/xml")->assertOk()->assertDownload('20000000001-03-B001-3.xml');
        $this->get("/comprobantes/{$boleta->id}/cdr")->assertOk()->assertDownload('R-20000000001-03-B001-3.zip');
    }

    public function test_sin_archivo_la_descarga_da_404(): void
    {
        $boleta = ComprobanteElectronico::factory()->estado('ERROR')->create(['cdr_path' => null]);

        $this->get("/comprobantes/{$boleta->id}/cdr")->assertNotFound();
    }

    public function test_reenvia_un_comprobante_en_error(): void
    {
        $boleta = ComprobanteElectronico::factory()->estado('ERROR')->conDetalle()->create();

        $this->post("/comprobantes/{$boleta->id}/reenviar")
            ->assertRedirect('/comprobantes')
            ->assertSessionHas('success');

        $this->assertSame('ACEPTADO', $boleta->fresh()->estado);
    }

    public function test_no_reenvia_un_comprobante_aceptado(): void
    {
        $boleta = ComprobanteElectronico::factory()->conDetalle()->create();

        $this->post("/comprobantes/{$boleta->id}/reenviar")->assertSessionHas('error');
        $this->assertCount(0, $this->sunat->enviados);
    }

    public function test_marca_cuando_la_venta_figura_devuelta_en_pclap(): void
    {
        $venta = $this->crearVentaPclap(['estado_pago' => 'DEVUELTO']);
        ComprobanteElectronico::factory()->create(['pclap_venta_id' => $venta]);

        $this->get('/comprobantes')->assertSee('Devuelta en PCLAP');
    }
}
