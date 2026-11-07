<?php

namespace App\Services\Facturacion;

/**
 * Lo que respondió SUNAT a un envío.
 * $recibido es true cuando SUNAT devolvió una constancia (CDR), aunque sea de rechazo.
 */
final class RespuestaSunat
{
    public function __construct(
        public readonly bool $recibido,
        public readonly string $codigo,
        public readonly string $mensaje,
        public readonly array $notas,
        public readonly ?string $xmlFirmado,
        public readonly ?string $cdrZip,
    ) {}
}
