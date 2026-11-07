<?php

namespace App\Services\Facturacion;

use App\Models\ComprobanteDetalle;
use App\Models\ComprobanteElectronico;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use Luecano\NumeroALetras\NumeroALetras;

/** Arma el documento de Greenter (factura, boleta o nota de crédito) a partir del comprobante guardado. */
class ConstructorGreenter
{
    public function construir(ComprobanteElectronico $comprobante): Invoice|Note
    {
        $comprobante->loadMissing(['detalles', 'referencia']);

        $documento = $comprobante->tipo_doc === '07'
            ? $this->notaCredito($comprobante)
            : (new Invoice)->setTipoOperacion('0101')->setFormaPago(new FormaPagoContado);

        $documento
            ->setValorVenta((float) $comprobante->op_gravadas)
            ->setSubTotal((float) $comprobante->total);

        $documento
            ->setUblVersion('2.1')
            ->setTipoDoc($comprobante->tipo_doc)
            ->setSerie($comprobante->serie)
            ->setCorrelativo((string) $comprobante->correlativo)
            ->setFechaEmision($comprobante->fecha_emision->toDateTime())
            ->setTipoMoneda($comprobante->moneda)
            ->setCompany($this->emisor())
            ->setClient((new Client)
                ->setTipoDoc($comprobante->cliente_tipo_doc)
                ->setNumDoc($comprobante->cliente_num_doc)
                ->setRznSocial($comprobante->cliente_razon_social))
            ->setMtoOperGravadas((float) $comprobante->op_gravadas)
            ->setMtoIGV((float) $comprobante->igv)
            ->setTotalImpuestos((float) $comprobante->igv)
            ->setMtoImpVenta((float) $comprobante->total)
            ->setDetails($comprobante->detalles->map(fn (ComprobanteDetalle $detalle) => $this->linea($detalle))->all())
            ->setLegends([(new Legend)->setCode('1000')->setValue($this->montoEnLetras((float) $comprobante->total))]);

        return $documento;
    }

    public function montoEnLetras(float $total): string
    {
        return 'SON '.(new NumeroALetras)->toInvoice($total, 2, 'SOLES');
    }

    private function notaCredito(ComprobanteElectronico $nota): Note
    {
        return (new Note)
            ->setCodMotivo($nota->motivo_codigo)
            ->setDesMotivo($nota->motivo_descripcion)
            ->setTipDocAfectado($nota->referencia->tipo_doc)
            ->setNumDocfectado($nota->referencia->numero());
    }

    private function linea(ComprobanteDetalle $detalle): SaleDetail
    {
        return (new SaleDetail)
            ->setCodProducto($detalle->codigo)
            ->setUnidad($detalle->unidad)
            ->setCantidad((float) $detalle->cantidad)
            ->setDescripcion($detalle->descripcion)
            ->setMtoValorUnitario((float) $detalle->valor_unitario)
            ->setMtoBaseIgv((float) $detalle->valor_venta)
            ->setPorcentajeIgv(round(config('sunat.igv') * 100, 2))
            ->setIgv((float) $detalle->igv)
            ->setTipAfeIgv('10') // gravado - operación onerosa
            ->setTotalImpuestos((float) $detalle->igv)
            ->setMtoValorVenta((float) $detalle->valor_venta)
            ->setMtoPrecioUnitario((float) $detalle->precio_unitario);
    }

    private function emisor(): Company
    {
        $emisor = config('sunat.emisor');

        return (new Company)
            ->setRuc(config('sunat.ruc'))
            ->setRazonSocial($emisor['razon_social'])
            ->setNombreComercial($emisor['nombre_comercial'])
            ->setAddress((new Address)
                ->setUbigueo($emisor['ubigeo'])
                ->setDepartamento($emisor['departamento'])
                ->setProvincia($emisor['provincia'])
                ->setDistrito($emisor['distrito'])
                ->setUrbanizacion('-')
                ->setDireccion($emisor['direccion'])
                ->setCodLocal('0000'));
    }
}
