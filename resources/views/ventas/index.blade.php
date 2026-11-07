@extends('layouts.app')

@section('title', 'Ventas por facturar')

@section('content')
    <h1 class="mt-4">Ventas por facturar</h1>
    <x-breadcrumb.template>
        <x-breadcrumb.item :href="route('panel')" content="Panel" />
        <x-breadcrumb.item :active="true" content="Ventas por facturar" />
    </x-breadcrumb.template>

    <p class="text-muted">Ventas cobradas en PCLAP que todavía no tienen comprobante electrónico. Se leen de PCLAP sin modificarlo.</p>

    @if ($errorPclap)
        <div class="alert alert-warning" role="alert"><i class="fa-solid fa-plug-circle-xmark me-2"></i>{{ $errorPclap }}</div>
    @endif

    <form method="GET" action="{{ route('ventas.index') }}" class="row g-2 align-items-end mb-3">
        <div class="col-auto">
            <label for="desde" class="form-label small mb-1">Desde</label>
            <input type="date" id="desde" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <label for="hasta" class="form-label small mb-1">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
            @if (array_filter($filtros))
                <a href="{{ route('ventas.index') }}" class="btn btn-sm btn-link">Quitar filtro</a>
            @endif
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-cart-shopping me-1"></i> {{ $ventas->count() }} {{ $ventas->count() === 1 ? 'venta' : 'ventas' }} sin comprobante</div>
        <div class="card-body">
            @if ($ventas->isEmpty())
                <p class="mb-0 text-muted">No hay ventas por facturar{{ array_filter($filtros) ? ' en esas fechas' : '' }}.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Venta en PCLAP</th>
                                <th scope="col">Cliente</th>
                                <th scope="col" class="text-end">Total</th>
                                <th scope="col"><span class="visually-hidden">Acción</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ventas as $venta)
                                <tr>
                                    <td class="text-nowrap">{{ $venta->fecha_hora?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        {{ $venta->numero_comprobante }}
                                        <div class="small text-muted">{{ $venta->tipoComprobante?->nombre }}</div>
                                    </td>
                                    <td>{{ filled($venta->cliente_nuevo) ? $venta->cliente_nuevo : $venta->cliente?->persona?->razon_social }}</td>
                                    <td class="text-end text-nowrap">S/ {{ number_format($venta->total, 2) }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-primary text-nowrap" data-emitir
                                            data-borrador-url="{{ route('ventas.borrador', $venta->id) }}"
                                            data-emitir-url="{{ route('ventas.emitir', $venta->id) }}">
                                            <i class="fa-solid fa-file-circle-plus me-1"></i> Emitir comprobante
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Vista previa: aquí se confirman o corrigen los datos del cliente antes de enviar a SUNAT --}}
    <div class="modal fade" id="modalEmitir" tabindex="-1" aria-labelledby="modalEmitirTitulo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" id="formEmitir" novalidate>
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modalEmitirTitulo">Emitir comprobante de la venta <span data-campo="numero_pclap"></span></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-none" role="alert" data-aviso-cuadre></div>
                    <div class="alert alert-danger d-none" role="alert" data-error-general></div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="tipo_doc" class="form-label">Comprobante</label>
                            <select id="tipo_doc" name="tipo_doc" class="form-select">
                                <option value="03">Boleta de venta</option>
                                <option value="01">Factura</option>
                            </select>
                            <div class="invalid-feedback" data-error="tipo_doc"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="cliente_tipo_doc" class="form-label">Documento del cliente</label>
                            <select id="cliente_tipo_doc" name="cliente_tipo_doc" class="form-select">
                                <option value="-">Sin documento (clientes varios)</option>
                                <option value="1">DNI</option>
                                <option value="6">RUC</option>
                            </select>
                            <div class="invalid-feedback" data-error="cliente_tipo_doc"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="cliente_num_doc" class="form-label">Número</label>
                            <input id="cliente_num_doc" name="cliente_num_doc" class="form-control" inputmode="numeric" autocomplete="off">
                            <div class="invalid-feedback" data-error="cliente_num_doc"></div>
                        </div>
                        <div class="col-12">
                            <label for="cliente_razon_social" class="form-label">Nombre o razón social</label>
                            <input id="cliente_razon_social" name="cliente_razon_social" class="form-control" autocomplete="off">
                            <div class="invalid-feedback" data-error="cliente_razon_social"></div>
                        </div>
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-center">Cant.</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col" class="text-end">P. unit.</th>
                                    <th scope="col" class="text-end">Importe</th>
                                </tr>
                            </thead>
                            <tbody data-lineas></tbody>
                            <tfoot>
                                <tr><td colspan="3" class="text-end text-muted">Op. gravadas</td><td class="text-end" data-campo="op_gravadas"></td></tr>
                                <tr><td colspan="3" class="text-end text-muted">IGV 18 %</td><td class="text-end" data-campo="igv"></td></tr>
                                <tr><td colspan="3" class="text-end fw-semibold">Total</td><td class="text-end fw-semibold" data-campo="total"></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" data-enviar>
                        <span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>
                        Enviar a SUNAT
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(function () {
            const $modal = $('#modalEmitir');
            const modal = new bootstrap.Modal($modal[0]);
            const $form = $('#formEmitir');
            const soles = (monto) => 'S/ ' + Number(monto).toFixed(2);
            let urlEmitir = null;
            let $fila = null;

            function limpiarErrores() {
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('[data-error]').text('');
                $form.find('[data-error-general]').addClass('d-none').text('');
            }

            function ajustarNumero() {
                const sinDocumento = $('#cliente_tipo_doc').val() === '-';
                $('#cliente_num_doc').prop('disabled', sinDocumento).val(sinDocumento ? '-' : $('#cliente_num_doc').val().replace(/^-$/, ''));
            }

            function enviando(activo) {
                $form.find('[data-enviar]').prop('disabled', activo).find('.spinner-border').toggleClass('d-none', !activo);
            }

            $('[data-emitir]').on('click', function () {
                const $boton = $(this);
                $fila = $boton.closest('tr');
                urlEmitir = $boton.data('emitir-url');
                limpiarErrores();

                $.getJSON($boton.data('borrador-url'))
                    .done(function (borrador) {
                        $form.find('[data-campo="numero_pclap"]').text(borrador.numero_pclap);
                        $('#tipo_doc').val(borrador.tipo_doc);
                        $('#cliente_tipo_doc').val(borrador.cliente.tipo_doc);
                        $('#cliente_num_doc').val(borrador.cliente.num_doc);
                        $('#cliente_razon_social').val(borrador.cliente.razon_social);
                        ajustarNumero();

                        const $lineas = $form.find('[data-lineas]').empty();
                        borrador.lineas.forEach(function (linea) {
                            $('<tr>').append(
                                $('<td class="text-center">').text(Number(linea.cantidad)),
                                $('<td>').text(linea.descripcion),
                                $('<td class="text-end text-nowrap">').text(soles(linea.precio_unitario)),
                                $('<td class="text-end text-nowrap">').text(soles(linea.total)),
                            ).appendTo($lineas);
                        });
                        $form.find('[data-campo="op_gravadas"]').text(soles(borrador.totales.op_gravadas));
                        $form.find('[data-campo="igv"]').text(soles(borrador.totales.igv));
                        $form.find('[data-campo="total"]').text(soles(borrador.totales.total));

                        $form.find('[data-aviso-cuadre]')
                            .toggleClass('d-none', borrador.cuadra)
                            .text(borrador.cuadra ? '' : 'El total calculado (' + soles(borrador.totales.total) + ') no coincide con el de PCLAP (' + soles(borrador.total_pclap) + '). Revisa la venta en PCLAP antes de emitir.');

                        modal.show();
                    })
                    .fail(function () {
                        Swal.fire({ icon: 'error', titleText: 'No se pudo leer la venta', text: 'Revisa que MySQL esté encendido en XAMPP y vuelve a intentarlo.' });
                    });
            });

            $('#cliente_tipo_doc').on('change', ajustarNumero);

            $form.on('submit', function (evento) {
                evento.preventDefault();
                limpiarErrores();
                enviando(true);

                const datos = $form.serializeArray();
                if ($('#cliente_num_doc').prop('disabled')) {
                    datos.push({ name: 'cliente_num_doc', value: '-' });
                }

                $.ajax({
                    url: urlEmitir,
                    method: 'POST',
                    data: $.param(datos),
                    dataType: 'json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                })
                    .done(function (respuesta) {
                        modal.hide();
                        const aceptado = ['ACEPTADO', 'OBSERVADO'].includes(respuesta.estado);
                        const ayuda = {
                            ERROR: 'El comprobante no llegó a SUNAT. Espera unos segundos y reenvíalo desde Comprobantes.',
                            RECHAZADO: 'SUNAT lo rechazó. Corrige los datos y vuelve a emitir esta venta.',
                        };

                        Swal.fire({
                            icon: aceptado ? 'success' : (respuesta.estado === 'ERROR' ? 'warning' : 'error'),
                            titleText: respuesta.tipo + ' ' + respuesta.numero + ': ' + respuesta.estado,
                            text: (respuesta.mensaje || '') + (ayuda[respuesta.estado] ? '\n\n' + ayuda[respuesta.estado] : ''),
                            footer: aceptado ? '<a href="' + respuesta.pdf + '" target="_blank" rel="noopener">Ver el PDF del comprobante</a>' : '<a href="{{ route('comprobantes.index') }}">Ir a comprobantes</a>',
                        });

                        // Solo un rechazo devuelve la venta a la lista.
                        if (respuesta.estado !== 'RECHAZADO') {
                            $fila.remove();
                        }
                    })
                    .fail(function (xhr) {
                        const errores = xhr.responseJSON?.errors;
                        if (xhr.status === 422 && errores) {
                            Object.entries(errores).forEach(function ([campo, mensajes]) {
                                $('#' + campo).addClass('is-invalid');
                                $form.find('[data-error="' + campo + '"]').text(mensajes[0]);
                            });
                            return;
                        }
                        $form.find('[data-error-general]').removeClass('d-none')
                            .text(xhr.responseJSON?.message || 'No se pudo emitir el comprobante. Vuelve a intentarlo.');
                    })
                    .always(function () {
                        enviando(false);
                    });
            });
        });
    </script>
@endpush
