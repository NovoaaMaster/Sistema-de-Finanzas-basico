{{-- Avisos de una sola vez (session success/error) como toast de SweetAlert, igual que en PCLAP --}}
@foreach (['success' => 'success', 'error' => 'error'] as $clave => $icono)
    @if (session($clave))
        <script>
            Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 6000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                },
            }).fire({ icon: @json($icono), titleText: @json(session($clave)) });
        </script>
    @endif
@endforeach
