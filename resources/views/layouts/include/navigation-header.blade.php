<nav class="sb-topnav navbar navbar-expand navbar-dark barra-superior">
    <a class="navbar-brand ps-3" href="{{ route('panel') }}"><x-logo /> Finanzas</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button" aria-label="Mostrar u ocultar el menú">
        <i class="fas fa-bars"></i>
    </button>

    <div class="ms-auto me-3 me-lg-4 d-flex align-items-center gap-3">
        @if (config('sunat.modo') !== 'produccion')
            <span class="badge rounded-pill text-bg-warning" title="Los comprobantes van al entorno de pruebas de SUNAT y no tienen validez tributaria">
                <i class="fa-solid fa-flask me-1"></i> SUNAT beta
            </span>
        @endif

        <ul class="navbar-nav">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="menuUsuario" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i><span class="visually-hidden">Menú de usuario</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuUsuario">
                    <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->email }}</span></li>
                    <li><a class="dropdown-item" href="{{ route('configuracion') }}">Configuración</a></li>
                    <li><hr class="dropdown-divider" /></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
