<?php

namespace Tests\Feature\Facturacion;

use App\Models\ComprobanteElectronico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprobanteElectronicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_numero_y_nombre_de_archivo_siguen_el_formato_de_sunat(): void
    {
        $boleta = ComprobanteElectronico::factory()->create(['serie' => 'B001', 'correlativo' => 7]);

        $this->assertSame('B001-7', $boleta->numero());
        $this->assertSame('20000000001-03-B001-7', $boleta->nombreArchivo());
    }

    public function test_el_qr_lleva_los_datos_que_pide_sunat(): void
    {
        $boleta = ComprobanteElectronico::factory()->create([
            'serie' => 'B001',
            'correlativo' => 7,
            'fecha_emision' => '2026-11-06 10:30:00',
            'hash' => 'aBc123=',
        ]);

        $this->assertSame('20000000001|03|B001|7|21.97|144.00|2026-11-06|-|-|aBc123=|', $boleta->contenidoQr());
    }

    public function test_solo_se_reenvia_lo_que_no_llego_a_sunat(): void
    {
        $this->assertTrue(ComprobanteElectronico::factory()->estado('ERROR')->make()->puedeReenviarse());
        $this->assertFalse(ComprobanteElectronico::factory()->estado('ACEPTADO')->make()->puedeReenviarse());
        $this->assertFalse(ComprobanteElectronico::factory()->estado('RECHAZADO')->make()->puedeReenviarse());
    }

    public function test_nota_de_credito_solo_sobre_un_comprobante_aceptado_sin_otra_nota(): void
    {
        $aceptada = ComprobanteElectronico::factory()->create();
        $rechazada = ComprobanteElectronico::factory()->estado('RECHAZADO')->create();
        $conNota = ComprobanteElectronico::factory()->create();
        ComprobanteElectronico::factory()->create([
            'tipo_doc' => '07',
            'serie' => 'BC01',
            'pclap_venta_id' => null,
            'comprobante_referencia_id' => $conNota->id,
        ]);

        $this->assertTrue($aceptada->admiteNotaCredito());
        $this->assertTrue(ComprobanteElectronico::factory()->estado('OBSERVADO')->create()->admiteNotaCredito());
        $this->assertFalse($rechazada->admiteNotaCredito());
        $this->assertFalse($conNota->admiteNotaCredito());
    }
}
