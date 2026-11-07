<?php

namespace App\Services\Facturacion;

use App\Models\SerieElectronica;
use Illuminate\Support\Facades\DB;

/** Numeración de cada serie sin saltos ni números repetidos. */
class CorrelativoService
{
    /** Reserva y devuelve el siguiente número de la serie. */
    public function siguiente(string $serie): int
    {
        return DB::transaction(function () use ($serie) {
            $registro = SerieElectronica::where('serie', $serie)->lockForUpdate()->firstOrFail();
            $registro->increment('correlativo');

            return $registro->correlativo;
        });
    }

    /**
     * Serie que corresponde a un tipo de comprobante. Las notas de crédito usan la serie
     * que empieza con la misma letra que el comprobante que modifican (F para facturas, B para boletas).
     */
    public function seriePara(string $tipoDoc, ?string $tipoReferencia = null): string
    {
        $letra = ($tipoDoc === '07' ? $tipoReferencia : $tipoDoc) === '01' ? 'F' : 'B';

        return SerieElectronica::where('tipo_doc', $tipoDoc)
            ->where('serie', 'like', $letra.'%')
            ->orderBy('id')
            ->valueOrFail('serie');
    }
}
