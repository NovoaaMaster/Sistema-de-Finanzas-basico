@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
    <h1 class="mt-4">Configuración</h1>
    <x-breadcrumb.template>
        <x-breadcrumb.item :href="route('panel')" content="Panel" />
        <x-breadcrumb.item :active="true" content="Configuración" />
    </x-breadcrumb.template>

    <p class="text-muted">Solo lectura. Los datos del emisor y de SUNAT se cambian en el archivo <code>.env</code> del módulo.</p>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><i class="fa-solid fa-building me-1"></i> Emisor</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">RUC</dt>
                        <dd class="col-sm-8">{{ $ruc }}</dd>
                        <dt class="col-sm-4">Razón social</dt>
                        <dd class="col-sm-8">{{ $emisor['razon_social'] }}</dd>
                        <dt class="col-sm-4">Nombre comercial</dt>
                        <dd class="col-sm-8">{{ $emisor['nombre_comercial'] }}</dd>
                        <dt class="col-sm-4">Dirección</dt>
                        <dd class="col-sm-8">{{ $emisor['direccion'] ?: 'Sin dirección' }}</dd>
                        <dt class="col-sm-4">Ubigeo</dt>
                        <dd class="col-sm-8 mb-0">{{ $emisor['ubigeo'] }} ({{ $emisor['distrito'] }}, {{ $emisor['provincia'] }}, {{ $emisor['departamento'] }})</dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="fa-solid fa-landmark me-1"></i> SUNAT</div>
                <div class="card-body">
                    @if ($modo === 'produccion')
                        <p class="mb-0"><span class="badge text-bg-success">Producción</span> Los comprobantes tienen validez tributaria.</p>
                    @else
                        <p class="mb-0"><span class="badge text-bg-warning">Beta</span> Entorno de pruebas de SUNAT: gratis y sin validez tributaria.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><i class="fa-solid fa-list-ol me-1"></i> Series</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Serie</th>
                                <th scope="col">Comprobante</th>
                                <th scope="col" class="text-end">Último número</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($series as $serie)
                                <tr>
                                    <td class="fw-semibold">{{ $serie->serie }}</td>
                                    <td>{{ \App\Models\ComprobanteElectronico::TIPOS[$serie->tipo_doc] ?? $serie->tipo_doc }}</td>
                                    <td class="text-end">{{ $serie->correlativo }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="fa-solid fa-database me-1"></i> Conexión con PCLAP</div>
                <div class="card-body">
                    @if ($estadoPclap)
                        <p class="mb-1"><i class="fa-solid fa-circle-check text-success me-1"></i>{{ $estadoPclap }}</p>
                    @else
                        <p class="mb-1"><i class="fa-solid fa-circle-xmark text-danger me-1"></i>No se pudo leer la base de PCLAP. Revisa que MySQL esté encendido en XAMPP.</p>
                    @endif
                    <p class="small text-muted mb-0">Solo lectura: el usuario de base de datos de Finanzas no tiene permiso para modificar PCLAP.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
