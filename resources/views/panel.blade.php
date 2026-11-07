@extends('layouts.app')

@section('title', 'Panel')

@section('content')
    <h1 class="mt-4">Panel</h1>
    <x-breadcrumb.template>
        <x-breadcrumb.item :active="true" content="Panel" />
    </x-breadcrumb.template>

    @if ($errorPclap)
        <div class="alert alert-warning" role="alert"><i class="fa-solid fa-plug-circle-xmark me-2"></i>{{ $errorPclap }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="indicador" style="--indicador-color: #0f766e">
                <div class="text-muted">Ventas de PCLAP por facturar</div>
                <div class="indicador-valor">{{ $porFacturar ?? '—' }}</div>
                <a class="indicador-enlace stretched-link" href="{{ route('ventas.index') }}">Emitir comprobantes</a>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="indicador" style="--indicador-color: #15803d">
                <div class="text-muted">Aceptados por SUNAT hoy</div>
                <div class="indicador-valor">{{ $aceptadosHoy }}</div>
                <a class="indicador-enlace stretched-link" href="{{ route('comprobantes.index') }}">Ver comprobantes</a>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="indicador" style="--indicador-color: {{ $conProblemas > 0 ? '#b42318' : '#52606d' }}">
                <div class="text-muted">Rechazados o sin enviar</div>
                <div class="indicador-valor">{{ $conProblemas }}</div>
                <a class="indicador-enlace stretched-link" href="{{ route('comprobantes.index') }}">{{ $conProblemas > 0 ? 'Revisar' : 'Ver comprobantes' }}</a>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-file-invoice me-1"></i> Últimos comprobantes</div>
        <div class="card-body">
            @if ($ultimos->isEmpty())
                <p class="mb-0 text-muted">Aún no hay comprobantes emitidos. Empieza desde <a href="{{ route('ventas.index') }}">ventas por facturar</a>.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Número</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Cliente</th>
                                <th scope="col" class="text-end">Total</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ultimos as $comprobante)
                                <tr>
                                    <td class="fw-semibold">{{ $comprobante->numero() }}</td>
                                    <td>{{ $comprobante->nombreTipo() }}</td>
                                    <td>{{ $comprobante->cliente_razon_social }}</td>
                                    <td class="text-end">S/ {{ number_format($comprobante->total, 2) }}</td>
                                    <td><x-estado :estado="$comprobante->estado" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
