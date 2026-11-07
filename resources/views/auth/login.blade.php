<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Inicio de sesión de Finanzas: facturación electrónica SUNAT" />
    <title>Finanzas - Iniciar sesión</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/styles.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/finanzas.css') }}" rel="stylesheet" />
</head>

<body class="login-finanzas">
    <main class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
                <div class="login-marca mt-5 mb-4">
                    <x-logo />
                    <div>
                        <h1 class="login-marca-nombre">Finanzas</h1>
                        <p class="login-marca-detalle">Facturación electrónica SUNAT</p>
                    </div>
                </div>

                <div class="card login-tarjeta">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Acceso a Finanzas</h2>

                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form action="{{ route('login.store') }}" method="post">
                            @csrf
                            <div class="mb-3">
                                <label for="inputEmail" class="form-label">Correo electrónico</label>
                                <input autofocus autocomplete="username" class="form-control" name="email" id="inputEmail" type="email" value="{{ old('email') }}" required />
                            </div>
                            <div class="mb-4">
                                <label for="inputPassword" class="form-label">Contraseña</label>
                                <input autocomplete="current-password" class="form-control" name="password" id="inputPassword" type="password" required />
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Iniciar sesión</button>
                        </form>
                    </div>
                </div>

                <p class="mt-4 small text-center text-white-50">© {{ date('Y') }} Finanzas</p>
            </div>
        </div>
    </main>
</body>

</html>
