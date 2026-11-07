<?php

namespace App\Services\Facturacion;

use Greenter\Model\DocumentInterface;
use Greenter\Model\Response\BillResult;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;

class ServicioSunatGreenter implements ServicioSunat
{
    public function enviar(DocumentInterface $documento): RespuestaSunat
    {
        // Si dos envíos llegan casi juntos, la beta de SUNAT responde "Unauthorized" al segundo:
        // ese comprobante queda en ERROR y se reenvía unos segundos después con el mismo número.
        $see = new See;
        $see->setCertificate(file_get_contents(storage_path(config('sunat.certificado'))));
        $see->setService(config('sunat.modo') === 'produccion' ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);
        $see->setClaveSOL(config('sunat.ruc'), config('sunat.usuario_sol'), config('sunat.clave_sol'));

        /** @var BillResult $resultado facturas, boletas y notas devuelven un BillResult */
        $resultado = $see->send($documento);
        $xml = $see->getFactory()->getLastXml();

        if (! $resultado->isSuccess()) {
            $error = $resultado->getError();

            return new RespuestaSunat(false, (string) $error->getCode(), (string) $error->getMessage(), [], $xml, null);
        }

        $cdr = $resultado->getCdrResponse();

        return new RespuestaSunat(true, (string) $cdr->getCode(), (string) $cdr->getDescription(), $cdr->getNotes() ?? [], $xml, $resultado->getCdrZip());
    }
}
