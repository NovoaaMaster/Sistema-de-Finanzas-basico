<?php

namespace Tests\Fakes;

use App\Services\Facturacion\RespuestaSunat;
use App\Services\Facturacion\ServicioSunat;
use Greenter\Model\DocumentInterface;
use RuntimeException;

/** SUNAT simulado: devuelve la respuesta preparada y recuerda qué documentos recibió. */
class ServicioSunatFalso implements ServicioSunat
{
    public const XML_FIRMADO = '<?xml version="1.0" encoding="utf-8"?><Invoice xmlns:ds="http://www.w3.org/2000/09/xmldsig#"><ds:Signature><ds:SignedInfo><ds:Reference><ds:DigestValue>hAsH+de=Prueba</ds:DigestValue></ds:Reference></ds:SignedInfo></ds:Signature></Invoice>';

    /** @var DocumentInterface[] */
    public array $enviados = [];

    private ?RespuestaSunat $respuesta = null;

    private bool $sinConexion = false;

    public function aceptar(array $notas = []): static
    {
        $this->respuesta = new RespuestaSunat(true, '0', 'El comprobante ha sido aceptado', $notas, self::XML_FIRMADO, 'ZIP-CDR');

        return $this;
    }

    public function rechazarConCdr(string $codigo, string $mensaje): static
    {
        $this->respuesta = new RespuestaSunat(true, $codigo, $mensaje, [], self::XML_FIRMADO, 'ZIP-CDR');

        return $this;
    }

    public function fallar(string $codigo, string $mensaje): static
    {
        $this->respuesta = new RespuestaSunat(false, $codigo, $mensaje, [], self::XML_FIRMADO, null);

        return $this;
    }

    public function sinConexion(): static
    {
        $this->sinConexion = true;

        return $this;
    }

    public function enviar(DocumentInterface $documento): RespuestaSunat
    {
        $this->enviados[] = $documento;

        if ($this->sinConexion) {
            throw new RuntimeException('No se pudo conectar con SUNAT');
        }

        return $this->respuesta ?? $this->aceptar()->respuesta;
    }
}
