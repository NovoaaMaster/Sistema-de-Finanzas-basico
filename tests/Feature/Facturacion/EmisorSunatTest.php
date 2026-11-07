<?php

namespace Tests\Feature\Facturacion;

use App\Models\ComprobanteElectronico;
use App\Services\Facturacion\EmisorSunat;
use App\Services\Facturacion\ServicioSunat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\ServicioSunatFalso;
use Tests\TestCase;

class EmisorSunatTest extends TestCase
{
    use RefreshDatabase;

    private ServicioSunatFalso $sunat;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->sunat = new ServicioSunatFalso;
        $this->app->instance(ServicioSunat::class, $this->sunat);
    }

    private function emitir(ComprobanteElectronico $comprobante): ComprobanteElectronico
    {
        return app(EmisorSunat::class)->emitir($comprobante);
    }

    public function test_comprobante_aceptado_guarda_xml_cdr_y_hash(): void
    {
        $boleta = ComprobanteElectronico::factory()->estado('PENDIENTE')->conDetalle()->create(['serie' => 'B001', 'correlativo' => 3]);
        $this->sunat->aceptar();

        $boleta = $this->emitir($boleta);

        $this->assertSame('ACEPTADO', $boleta->estado);
        $this->assertSame('0', $boleta->sunat_codigo);
        $this->assertSame('El comprobante ha sido aceptado', $boleta->sunat_mensaje);
        $this->assertSame('hAsH+de=Prueba', $boleta->hash);
        $this->assertSame('comprobantes/20000000001-03-B001-3.xml', $boleta->xml_path);
        $this->assertSame('comprobantes/R-20000000001-03-B001-3.zip', $boleta->cdr_path);
        Storage::disk('local')->assertExists($boleta->xml_path);
        $this->assertSame('ZIP-CDR', Storage::disk('local')->get($boleta->cdr_path));
        $this->assertSame('B001', $this->sunat->enviados[0]->getSerie());
        $this->assertSame('ACEPTADO', $boleta->fresh()->estado);
    }

    public function test_aceptado_con_observaciones_queda_observado(): void
    {
        $boleta = ComprobanteElectronico::factory()->estado('PENDIENTE')->conDetalle()->create();
        $this->sunat->aceptar(['4287 - El precio unitario no coincide']);

        $boleta = $this->emitir($boleta);

        $this->assertSame('OBSERVADO', $boleta->estado);
        $this->assertStringContainsString('4287 - El precio unitario no coincide', $boleta->sunat_mensaje);
    }

    public function test_cdr_con_codigo_de_rechazo_queda_rechazado(): void
    {
        $boleta = ComprobanteElectronico::factory()->estado('PENDIENTE')->conDetalle()->create();
        $this->sunat->rechazarConCdr('2800', 'El dato ingresado en el tipo de documento de identidad del receptor no es válido');

        $this->assertSame('RECHAZADO', $this->emitir($boleta)->estado);
    }

    public function test_error_de_sunat_en_el_rango_de_rechazo_queda_rechazado(): void
    {
        $boleta = ComprobanteElectronico::factory()->estado('PENDIENTE')->conDetalle()->create();
        $this->sunat->fallar('2335', 'El documento electrónico ingresado ha sido alterado');

        $boleta = $this->emitir($boleta);

        $this->assertSame('RECHAZADO', $boleta->estado);
        $this->assertSame('2335', $boleta->sunat_codigo);
    }

    public function test_si_no_llego_a_sunat_queda_en_error_para_reenviar(): void
    {
        $boleta = ComprobanteElectronico::factory()->estado('PENDIENTE')->conDetalle()->create();
        $this->sunat->fallar('HTTP', 'Unauthorized');

        $boleta = $this->emitir($boleta);

        $this->assertSame('ERROR', $boleta->estado);
        $this->assertSame('Unauthorized', $boleta->sunat_mensaje);
        $this->assertNull($boleta->cdr_path);
        $this->assertTrue($boleta->puedeReenviarse());
    }

    public function test_sin_conexion_queda_en_error_sin_romper(): void
    {
        $boleta = ComprobanteElectronico::factory()->estado('PENDIENTE')->conDetalle()->create();
        $this->sunat->sinConexion();

        $boleta = $this->emitir($boleta);

        $this->assertSame('ERROR', $boleta->estado);
        $this->assertSame('No se pudo conectar con SUNAT', $boleta->sunat_mensaje);
    }
}
