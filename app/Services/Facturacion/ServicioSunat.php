<?php

namespace App\Services\Facturacion;

use Greenter\Model\DocumentInterface;

/** Firma y envía un comprobante a SUNAT. En las pruebas se reemplaza por uno simulado. */
interface ServicioSunat
{
    public function enviar(DocumentInterface $documento): RespuestaSunat;
}
