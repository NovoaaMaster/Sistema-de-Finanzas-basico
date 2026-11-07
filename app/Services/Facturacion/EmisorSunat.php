<?php

namespace App\Services\Facturacion;

use App\Models\ComprobanteElectronico;
use DOMDocument;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Envía un comprobante guardado a SUNAT y registra la respuesta, el XML firmado y la constancia (CDR). */
class EmisorSunat
{
    public function __construct(
        private ConstructorGreenter $constructor,
        private ServicioSunat $sunat,
    ) {}

    public function emitir(ComprobanteElectronico $comprobante): ComprobanteElectronico
    {
        $documento = $this->constructor->construir($comprobante);

        try {
            $respuesta = $this->sunat->enviar($documento);
        } catch (Throwable $error) {
            report($error);
            $comprobante->update(['estado' => 'ERROR', 'sunat_codigo' => null, 'sunat_mensaje' => $error->getMessage()]);

            return $comprobante;
        }

        $datos = [
            'estado' => $this->estado($respuesta),
            'sunat_codigo' => $respuesta->codigo,
            'sunat_mensaje' => $respuesta->notas
                ? $respuesta->mensaje."\nObservaciones: ".implode('; ', $respuesta->notas)
                : $respuesta->mensaje,
        ];

        if ($respuesta->xmlFirmado) {
            $datos['xml_path'] = 'comprobantes/'.$comprobante->nombreArchivo().'.xml';
            $datos['hash'] = $this->hash($respuesta->xmlFirmado);
            Storage::disk('local')->put($datos['xml_path'], $respuesta->xmlFirmado);
        }

        if ($respuesta->cdrZip) {
            $datos['cdr_path'] = 'comprobantes/R-'.$comprobante->nombreArchivo().'.zip';
            Storage::disk('local')->put($datos['cdr_path'], $respuesta->cdrZip);
        }

        $comprobante->update($datos);

        return $comprobante;
    }

    /**
     * Códigos de SUNAT: 0 aceptado; 0100-1999 no se procesó (se puede reenviar);
     * 2000-3999 rechazado; 4000 en adelante aceptado con observaciones.
     */
    private function estado(RespuestaSunat $respuesta): string
    {
        $codigo = ctype_digit($respuesta->codigo) ? (int) $respuesta->codigo : null;

        if (! $respuesta->recibido) {
            return $codigo !== null && $codigo >= 2000 && $codigo < 4000 ? 'RECHAZADO' : 'ERROR';
        }

        return match (true) {
            $codigo === 0 => $respuesta->notas ? 'OBSERVADO' : 'ACEPTADO',
            $codigo >= 4000 => 'OBSERVADO',
            default => 'RECHAZADO',
        };
    }

    /** Valor resumen (DigestValue de la firma) que va en el PDF y en el QR. */
    private function hash(string $xml): ?string
    {
        $dom = new DOMDocument;
        $dom->loadXML($xml);

        return $dom->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'DigestValue')->item(0)?->nodeValue;
    }
}
