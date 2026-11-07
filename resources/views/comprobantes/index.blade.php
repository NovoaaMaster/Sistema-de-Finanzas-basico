@extends('layouts.app')

@section('title', 'Comprobantes')

@section('content')
    <h1 class="mt-4">Comprobantes</h1>
    <x-breadcrumb.template>
        <x-breadcrumb.item :href="route('panel')" content="Panel" />
        <x-breadcrumb.item :active="true" content="Comprobantes" />
    </x-breadcrumb.template>

    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-file-invoice me-1"></i> Comprobantes electrónicos emitidos</div>
        <div class="card-body">
            @if ($comprobantes->isEmpty())
                <p class="mb-0 text-muted">Aún no hay comprobantes. Emite el primero desde <a href="{{ route('ventas.index') }}">ventas por facturar</a>.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Número</th>
                                <th scope="col">Emisión</th>
                                <th scope="col">Cliente</th>
                                <th scope="col" class="text-end">Total</th>
                                <th scope="col">Respuesta de SUNAT</th>
                                <th scope="col">Archivos y acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comprobantes as $comprobante)
                                <tr>
                                    <td class="text-nowrap">
                                        <span class="fw-semibold">{{ $comprobante->numero() }}</span>
                                        <div class="small text-muted">{{ $comprobante->nombreTipo() }}</div>
                                        @if ($comprobante->referencia)
                                            <div class="small text-muted">Modifica {{ $comprobante->referencia->numero() }}</div>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $comprobante->fecha_emision->format('d/m/Y H:i') }}</td>
                                    <td>
                                        {{ $comprobante->cliente_razon_social }}
                                        @if ($comprobante->cliente_tipo_doc !== '-')
                                            <div class="small text-muted">{{ $comprobante->cliente_tipo_doc === '6' ? 'RUC' : 'DNI' }} {{ $comprobante->cliente_num_doc }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">S/ {{ number_format($comprobante->total, 2) }}</td>
                                    <td style="max-width: 22rem">
                                        <x-estado :estado="$comprobante->estado" />
                                        @if ($comprobante->sunat_mensaje)
                                            <div class="small text-muted text-break">{{ $comprobante->sunat_codigo !== null ? $comprobante->sunat_codigo.' · ' : '' }}{{ Str::limit($comprobante->sunat_mensaje, 160) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <a href="{{ route('comprobantes.pdf', $comprobante) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger" title="Representación impresa">
                                                <i class="fa-solid fa-file-pdf"></i> PDF
                                            </a>
                                            @if ($comprobante->xml_path)
                                                <a href="{{ route('comprobantes.xml', $comprobante) }}" class="btn btn-sm btn-outline-secondary" title="XML firmado">XML</a>
                                            @endif
                                            @if ($comprobante->cdr_path)
                                                <a href="{{ route('comprobantes.cdr', $comprobante) }}" class="btn btn-sm btn-outline-secondary" title="Constancia de recepción de SUNAT">CDR</a>
                                            @endif
                                            @if ($comprobante->puedeReenviarse())
                                                <form method="POST" action="{{ route('comprobantes.reenviar', $comprobante) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning"><i class="fa-solid fa-rotate-right me-1"></i>Reenviar</button>
                                                </form>
                                            @endif
                                            @if ($comprobante->admiteNotaCredito())
                                                <a href="{{ route('notas.create', $comprobante) }}" class="btn btn-sm {{ in_array((int) $comprobante->pclap_venta_id, $devueltasEnPclap, true) ? 'btn-info' : 'btn-outline-primary' }}">
                                                    Nota de crédito
                                                </a>
                                            @endif
                                        </div>
                                        @if (in_array((int) $comprobante->pclap_venta_id, $devueltasEnPclap, true) && $comprobante->admiteNotaCredito())
                                            <div class="small text-info-emphasis mt-1"><i class="fa-solid fa-rotate-left me-1"></i>Devuelta en PCLAP: corresponde emitir nota de crédito</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $comprobantes->links() }}
            @endif
        </div>
    </div>
@endsection
