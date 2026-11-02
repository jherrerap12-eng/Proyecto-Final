<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Panel') · Babybu Toys</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#contenido" class="visually-hidden-focusable">Saltar al contenido</a>
    <nav class="navbar navbar-expand bg-white border-bottom" aria-label="Navegación principal">
        <div class="container py-2 gap-2 flex-wrap">
            <a class="navbar-brand fw-bold text-primary" href="{{ url('/') }}">Babybu <span class="text-body">Toys</span></a>
            @auth
                <a class="btn btn-sm {{ request()->routeIs('inventario.*') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('inventario.listar') }}">Inventario</a>
                <div class="d-flex align-items-center gap-3 ms-auto">
                    <span class="small d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    <form action="{{ route('sesion.salir') }}" method="POST">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Cerrar sesión</button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>
    <main id="contenido" class="container py-4 py-lg-5">
        @if (session('estado'))
            <div class="alert alert-success" role="status">{{ session('estado') }}</div>
        @endif
        @yield('contenido')
    </main>
    <footer class="container text-secondary small pb-4">Babybu Toys · Administración del negocio</footer>
</body>
</html>
