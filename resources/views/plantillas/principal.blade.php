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
                <a class="btn btn-sm {{ request()->routeIs('reportes.*') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('reportes.index') }}" @if(request()->routeIs('reportes.*')) aria-current="page" @endif>Reportes</a>
                <a class="btn btn-sm {{ request()->routeIs('entregas.*') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('entregas.listar') }}">Entregas</a>
                <a class="btn btn-sm {{ request()->routeIs('pagos.*') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('pagos.index') }}">Pagos</a>
                <a class="btn btn-sm {{ request()->routeIs('pedidos.*') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('pedidos.listar') }}">Pedidos</a>
                <a class="nav-link {{ request()->routeIs('clientes.*') ? 'fw-bold text-primary' : '' }}" href="{{ route('clientes.index') }}" @if(request()->routeIs('clientes.*')) aria-current="page" @endif>Clientes</a>
                <a class="btn btn-sm {{ request()->routeIs('inventario.*') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('inventario.listar') }}">Inventario</a>
                @can('administrar-usuarios')
                <a class="btn btn-sm {{ request()->routeIs('usuarios.*') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('usuarios.index') }}">Usuarios</a>
                @endcan
                <div class="d-flex align-items-center gap-3 ms-auto">
                    <a class="small text-decoration-none d-none d-sm-inline" href="{{ route('cuenta.contrasena') }}" title="Cambiar mi contraseña">{{ auth()->user()->name }} · {{ auth()->user()->nombreRol() }}</a>
                    <a class="small d-sm-none" href="{{ route('cuenta.contrasena') }}">Mi cuenta</a>
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
