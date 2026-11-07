<?php

namespace Tests\Feature\Facturacion;

use App\Models\ComprobanteElectronico;
use App\Services\Facturacion\ConstructorGreenter;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConstructorGreenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_arma_la_boleta_con_emisor_cliente_lineas_y_monto_en_letras(): void
    {
        $boleta = ComprobanteElectronico::factory()->conDetalle()->create(['serie' => 'B001', 'correlativo' => 7]);

        $documento = app(ConstructorGreenter::class)->construir($boleta);

        $this->assertInstanceOf(Invoice::class, $documento);
        $this->assertSame('03', $documento->getTipoDoc());
        $this->assertSame('B001', $documento->getSerie());
        $this->assertSame('7', $documento->getCorrelativo());
        $this->assertSame('20000000001', $documento->getCompany()->getRuc());
        $this->assertSame('150101', $documento->getCompany()->getAddress()->getUbigueo());
        $this->assertSame('-', $documento->getClient()->getTipoDoc());
        $this->assertSame('Público General', $documento->getClient()->getRznSocial());
        $this->assertSame(122.03, $documento->getMtoOperGravadas());
        $this->assertSame(21.97, $documento->getMtoIGV());
        $this->assertSame(144.00, $documento->getMtoImpVenta());
        $this->assertSame('SON CIENTO CUARENTA Y CUATRO CON 00/100 SOLES', $documento->getLegends()[0]->getValue());

        $linea = $documento->getDetails()[0];
        $this->assertSame('placa h510 - S/N: 515151', $linea->getDescripcion());
        $this->assertSame('10', $linea->getTipAfeIgv());
        $this->assertSame(1.0, $linea->getCantidad());
        $this->assertSame(122.033898, $linea->getMtoValorUnitario());
        $this->assertSame(144.00, $linea->getMtoPrecioUnitario());
        $this->assertSame(21.97, $linea->getIgv());
    }

    public function test_la_factura_identifica_al_cliente_por_ruc_y_es_al_contado(): void
    {
        $factura = ComprobanteElectronico::factory()->factura()->conDetalle()->create();

        $documento = app(ConstructorGreenter::class)->construir($factura);

        $this->assertSame('01', $documento->getTipoDoc());
        $this->assertSame('6', $documento->getClient()->getTipoDoc());
        $this->assertSame('20512345678', $documento->getClient()->getNumDoc());
        $this->assertSame('Contado', $documento->getFormaPago()->getTipo());
    }

    public function test_la_nota_de_credito_apunta_al_comprobante_que_modifica(): void
    {
        $factura = ComprobanteElectronico::factory()->factura()->conDetalle()->create(['correlativo' => 12]);
        $nota = ComprobanteElectronico::factory()->conDetalle()->create([
            'tipo_doc' => '07',
            'serie' => 'FC01',
            'correlativo' => 1,
            'cliente_tipo_doc' => '6',
            'cliente_num_doc' => '20512345678',
            'cliente_razon_social' => 'Comercial Andina SAC',
            'comprobante_referencia_id' => $factura->id,
            'motivo_codigo' => '07',
            'motivo_descripcion' => 'Devolución del producto',
        ]);

        $documento = app(ConstructorGreenter::class)->construir($nota);

        $this->assertInstanceOf(Note::class, $documento);
        $this->assertSame('07', $documento->getTipoDoc());
        $this->assertSame('FC01', $documento->getSerie());
        $this->assertSame('07', $documento->getCodMotivo());
        $this->assertSame('Devolución del producto', $documento->getDesMotivo());
        $this->assertSame('01', $documento->getTipDocAfectado());
        $this->assertSame('F001-12', $documento->getNumDocfectado());
        $this->assertSame(144.00, $documento->getMtoImpVenta());
    }
}
