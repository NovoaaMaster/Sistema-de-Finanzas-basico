<?php

namespace App\Services\Facturacion;

use App\Models\ComprobanteElectronico;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Symfony\Component\HttpFoundation\Response;

/** PDF del comprobante: los datos que SUNAT exige en la representación impresa (QR, hash, monto en letras). */
class RepresentacionImpresa
{
    public function __construct(private ConstructorGreenter $constructor) {}

    public function datos(ComprobanteElectronico $comprobante): array
    {
        $comprobante->loadMissing(['detalles', 'referencia']);

        return [
            'comprobante' => $comprobante,
            'ruc' => config('sunat.ruc'),
            'emisor' => config('sunat.emisor'),
            'modoBeta' => config('sunat.modo') !== 'produccion',
            'montoEnLetras' => $this->constructor->montoEnLetras((float) $comprobante->total),
            'qr' => (new QRCode(new QROptions([
                'outputInterface' => QRGdImagePNG::class,
                'outputBase64' => true,
                'scale' => 4,
            ])))->render($comprobante->contenidoQr()),
        ];
    }

    public function pdf(ComprobanteElectronico $comprobante): Response
    {
        return Pdf::loadView('pdf.comprobante', $this->datos($comprobante))
            ->setPaper('a4')
            ->stream($comprobante->nombreArchivo().'.pdf');
    }
}
