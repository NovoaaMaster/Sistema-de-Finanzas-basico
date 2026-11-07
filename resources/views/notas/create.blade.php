@extends('layouts.app')

@section('title', 'Nota de crédito')

@section('content')
    <h1 class="mt-4">Nota de crédito</h1>
    <x-breadcrumb.template>
        <x-breadcrumb.item :href="route('panel')" content="Panel" />
        <x-breadcrumb.item :href="route('comprobantes.index')" content="Comprobantes" />
        <x-breadcrumb.item :active="true" content="Nota de crédito" />
    </x-breadcrumb.template>

    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header">{{ $comprobante->nombreTipo() }} {{ $comprobante->numero() }}</div>
                <div class="card-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-4">Cliente</dt>
                        <dd class="col-sm-8">{{ $comprobante->cliente_razon_social }}</dd>
                        <dt class="col-sm-4">Emisión</dt>
                        <dd class="col-sm-8">{{ $comprobante->fecha_emision->format('d/m/Y H:i') }}</dd>
                        <dt class="col-sm-4">Total</dt>
                        <dd class="col-sm-8">S/ {{ number_format($comprobante->total, 2) }}</dd>
                    </dl>
                    <ul class="list-group list-group-flush">
                        @foreach ($comprobante->detalles as $detalle)
                            <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                                <span>{{ rtrim(rtrim($detalle->cantidad, '0'), '.') }} × {{ $detalle->descripcion }}</span>
                                <span class="text-nowrap">S/ {{ number_format($detalle->total, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <form method="POST" action="{{ route('notas.store', $comprobante) }}" class="card mb-4">
                @csrf
                <div class="card-header">Datos de la nota</div>
                <div class="card-body">
                    <p class="text-muted small">La nota de crédito anula el comprobante por su total: S/ {{ number_format($comprobante->total, 2) }}.</p>

                    <div class="mb-3">
                        <label for="motivo_codigo" class="form-label">Motivo</label>
                        <select id="motivo_codigo" name="motivo_codigo" class="form-select @error('motivo_codigo') is-invalid @enderror">
                            @foreach ($motivos as $codigo => $motivo)
                                <option value="{{ $codigo }}" @selected(old('motivo_codigo', '01') === $codigo)>{{ $motivo }}</option>
                            @endforeach
                        </select>
                        @error('motivo_codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="motivo_descripcion" class="form-label">Descripción</label>
                        <textarea id="motivo_descripcion" name="motivo_descripcion" rows="3" maxlength="250" class="form-control @error('motivo_descripcion') is-invalid @enderror">{{ old('motivo_descripcion', 'Anulación de la operación') }}</textarea>
                        @error('motivo_descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('comprobantes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Emitir nota de crédito</button>
                </div>
            </form>
        </div>
    </div>
@endsection
