<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $comprobante->tituloImpreso() }} {{ $comprobante->numero() }}</title>
</head>
{{-- Estilo copiado de la boleta en PDF de PCLAP (resources/views/pdf/comprobante-venta.blade.php),
     con los datos que SUNAT exige en la representación impresa: QR, valor resumen (hash) y monto en letras. --}}
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 12px; margin: 0; padding: 16px; }
    table { border-collapse: collapse; width: 100%; }
    .boleta { border: 1px solid #000; padding: 12px; }

    .head td { vertical-align: top; }
    .empresa-nombre { font-size: 20px; font-weight: bold; letter-spacing: 1px; }
    .empresa-cont { font-size: 9.5px; margin-top: 5px; line-height: 1.4; }

    .doc-box { border: 1px solid #000; text-align: center; padding: 6px 8px; }
    .doc-box .ruc { font-weight: bold; font-size: 12px; }
    .doc-box .tipo { font-weight: bold; font-size: 13px; letter-spacing: 0.5px; margin: 3px 0; }
    .doc-box .num { font-weight: bold; color: #0f4c5c; font-size: 13px; }
    .doc-box { border-color: #0f4c5c; }

    .cliente { margin-top: 12px; font-size: 11.5px; }
    .cliente td { padding: 2px 0; vertical-align: top; }
    .cliente .etiqueta { width: 22%; font-weight: bold; }

    .referencia { margin-top: 8px; border: 1px dashed #555; padding: 6px; font-size: 11px; }

    .items { margin-top: 12px; }
    .items th { border: 1px solid #000; background: #f0f0f0; font-size: 10.5px; padding: 4px; }
    .items td { border: 1px solid #000; font-size: 10.5px; padding: 5px; }
    .c-cant { width: 9%; text-align: center; }
    .c-desc { width: 49%; }
    .c-num { width: 14%; text-align: right; }

    .totales { margin-top: 8px; }
    .totales td { padding: 3px 6px; font-size: 11px; }
    .totales .etiqueta { text-align: right; }
    .totales .monto { width: 18%; text-align: right; border: 1px solid #000; }
    .totales .final td { font-weight: bold; font-size: 13px; }

    .letras { margin-top: 8px; font-size: 11px; font-weight: bold; }

    .pie { margin-top: 14px; }
    .pie td { vertical-align: middle; }
    .qr { width: 110px; }
    .legal { font-size: 9.5px; line-height: 1.45; }
    .prueba { margin-top: 10px; text-align: center; font-size: 10px; font-weight: bold; color: #c0111b; border: 1px solid #c0111b; padding: 4px; }
</style>

<body>
    <div class="boleta">

        <table class="head">
            <tr>
                <td style="width: 60%;">
                    <div class="empresa-nombre">{{ $emisor['razon_social'] }}</div>
                    <div class="empresa-cont">
                        @if ($emisor['nombre_comercial'] && $emisor['nombre_comercial'] !== $emisor['razon_social'])
                            {{ $emisor['nombre_comercial'] }}<br>
                        @endif
                        {{ $emisor['direccion'] }}<br>
                        {{ $emisor['distrito'] }} - {{ $emisor['provincia'] }} - {{ $emisor['departamento'] }}
                    </div>
                </td>
                <td style="width: 40%;">
                    <div class="doc-box">
                        <div class="ruc">R.U.C. {{ $ruc }}</div>
                        <div class="tipo">{{ $comprobante->tituloImpreso() }}</div>
                        <div class="num">{{ $comprobante->numero() }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="cliente">
            <tr>
                <td class="etiqueta">Señor(es):</td>
                <td>{{ $comprobante->cliente_razon_social }}</td>
            </tr>
            @if ($comprobante->cliente_tipo_doc !== '-')
                <tr>
                    <td class="etiqueta">{{ $comprobante->cliente_tipo_doc === '6' ? 'RUC' : 'DNI' }}:</td>
                    <td>{{ $comprobante->cliente_num_doc }}</td>
                </tr>
            @endif
            <tr>
                <td class="etiqueta">Fecha de emisión:</td>
                <td>{{ $comprobante->fecha_emision->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="etiqueta">Moneda:</td>
                <td>{{ $comprobante->moneda === 'PEN' ? 'SOLES' : $comprobante->moneda }}</td>
            </tr>
        </table>

        @if ($comprobante->referencia)
            <div class="referencia">
                <strong>Documento que modifica:</strong> {{ $comprobante->referencia->nombreTipo() }} {{ $comprobante->referencia->numero() }}
                &nbsp;&nbsp; <strong>Motivo:</strong> {{ $comprobante->motivo_descripcion }}
            </div>
        @endif

        <table class="items">
            <thead>
                <tr>
                    <th class="c-cant">CANT.</th>
                    <th class="c-desc">DESCRIPCIÓN</th>
                    <th class="c-num">V. UNIT.</th>
                    <th class="c-num">P. UNIT.</th>
                    <th class="c-num">IMPORTE</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($comprobante->detalles as $detalle)
                    <tr>
                        <td class="c-cant">{{ rtrim(rtrim($detalle->cantidad, '0'), '.') }}</td>
                        <td class="c-desc">{{ $detalle->descripcion }}</td>
                        <td class="c-num">{{ number_format($detalle->valor_unitario, 2) }}</td>
                        <td class="c-num">{{ number_format($detalle->precio_unitario, 2) }}</td>
                        <td class="c-num">{{ number_format($detalle->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totales">
            <tr>
                <td class="etiqueta">Op. gravadas S/</td>
                <td class="monto">{{ number_format($comprobante->op_gravadas, 2) }}</td>
            </tr>
            <tr>
                <td class="etiqueta">IGV 18 % S/</td>
                <td class="monto">{{ number_format($comprobante->igv, 2) }}</td>
            </tr>
            <tr class="final">
                <td class="etiqueta">Importe total S/</td>
                <td class="monto">{{ number_format($comprobante->total, 2) }}</td>
            </tr>
        </table>

        <div class="letras">{{ $montoEnLetras }}</div>

        <table class="pie">
            <tr>
                <td style="width: 120px;"><img class="qr" src="{{ $qr }}" alt="Código QR"></td>
                <td class="legal">
                    Representación impresa de la {{ mb_strtolower($comprobante->tituloImpreso()) }}.<br>
                    Consulte su validez en www.sunat.gob.pe<br>
                    @if ($comprobante->hash)
                        Valor resumen: {{ $comprobante->hash }}
                    @endif
                </td>
            </tr>
        </table>

        @if ($modoBeta)
            <div class="prueba">COMPROBANTE DE PRUEBA (SUNAT BETA): SIN VALIDEZ TRIBUTARIA</div>
        @endif

    </div>
</body>

</html>
