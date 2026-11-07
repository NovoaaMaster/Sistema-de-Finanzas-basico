@props(['estado'])

@php
    $clases = [
        'ACEPTADO' => 'text-bg-success',
        'OBSERVADO' => 'text-bg-warning',
        'RECHAZADO' => 'text-bg-danger',
        'ERROR' => 'text-bg-secondary',
        'PENDIENTE' => 'text-bg-light border',
    ];
    $ayuda = [
        'ACEPTADO' => 'SUNAT aceptó el comprobante',
        'OBSERVADO' => 'SUNAT lo aceptó con observaciones',
        'RECHAZADO' => 'SUNAT lo rechazó: la venta se puede volver a emitir',
        'ERROR' => 'No llegó a SUNAT: reenvíalo',
        'PENDIENTE' => 'Aún no se envió',
    ];
@endphp

<span {{ $attributes->class(['badge', $clases[$estado] ?? 'text-bg-light']) }} title="{{ $ayuda[$estado] ?? '' }}">{{ $estado }}</span>
