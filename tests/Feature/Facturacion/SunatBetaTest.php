<?php

namespace Tests\Feature\Facturacion;

use App\Pclap\VentaPclap;
use App\Services\Facturacion\FacturacionService;
use Database\Seeders\SerieElectronicaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\ConEsquemaPclap;
use Tests\TestCase;

/**
 * Envío real a la beta de SUNAT (necesita internet y storage/app/sunat/certificado.pem).
 * Está excluida por defecto: php artisan test --group=sunat
 */
#[Group('sunat')]
class SunatBetaTest extends TestCase
{
    use ConEsquemaPclap, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_file(storage_path(config('sunat.certificado')))) {
            $this->markTestSkipped('Falta el certificado de prueba en storage/'.config('sunat.certificado'));
        }

        config(['sunat.modo' => 'beta']);
        $this->crearEsquemaPclap();
        $this->seed(SerieElectronicaSeeder::class);
        Storage::fake('local');
    }

    public function test_la_beta_de_sunat_acepta_boleta_factura_y_nota_de_credito(): void
    {
        $servicio = app(FacturacionService::class);

        $boleta = $servicio->emitirDesdeVenta(VentaPclap::findOrFail($this->crearVentaPclap()), [
            'tipo_doc' => '03', 'cliente_tipo_doc' => '-', 'cliente_num_doc' => '-', 'cliente_razon_social' => 'Público General',
        ]);
        $this->assertSame('ACEPTADO', $boleta->estado, (string) $boleta->sunat_mensaje);
        $this->assertNotEmpty($boleta->hash);
        Storage::disk('local')->assertExists($boleta->cdr_path);

        sleep(5); // la beta rechaza ("Unauthorized") los envíos que llegan casi juntos
        $factura = $servicio->emitirDesdeVenta(VentaPclap::findOrFail($this->crearVentaPclap([
            'comprobante' => 'Factura',
            'lineas' => [['producto' => 'ssd de 1tb kingston', 'codigo' => '006673057148', 'cantidad' => 4, 'precio' => 306.00, 'series' => ['0003924387', 'rrrr', 'yyrrrr', 'yytytyt']]],
        ])), [
            'tipo_doc' => '01', 'cliente_tipo_doc' => '6', 'cliente_num_doc' => '20000000001', 'cliente_razon_social' => 'Empresa de Prueba SAC',
        ]);
        $this->assertSame('ACEPTADO', $factura->estado, (string) $factura->sunat_mensaje);

        sleep(5);
        $nota = $servicio->emitirNotaCredito($factura, '07', 'Devolución del producto');
        $this->assertSame('ACEPTADO', $nota->estado, (string) $nota->sunat_mensaje);
    }
}
