<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-light" id="sidenavAccordion" aria-label="Menú principal">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <x-nav.heading>Inicio</x-nav.heading>

                <x-nav.nav-link content='Panel'
                    icon='fas fa-tachometer-alt'
                    :href="route('panel')"
                    :active="request()->routeIs('panel')" />

                <x-nav.heading>Facturación electrónica</x-nav.heading>

                <x-nav.nav-link content='Ventas por facturar'
                    icon='fa-solid fa-file-circle-plus'
                    :href="route('ventas.index')"
                    :active="request()->routeIs('ventas.*')" />

                <x-nav.nav-link content='Comprobantes'
                    icon='fa-solid fa-file-invoice'
                    :href="route('comprobantes.index')"
                    :active="request()->routeIs('comprobantes.*', 'notas.*')" />

                <x-nav.heading>Sistema</x-nav.heading>

                <x-nav.nav-link content='Configuración'
                    icon='fa-solid fa-gear'
                    :href="route('configuracion')"
                    :active="request()->routeIs('configuracion')" />

            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small text-muted">Sesión iniciada como</div>
            {{ auth()->user()->name }}
        </div>
    </nav>
</div>
