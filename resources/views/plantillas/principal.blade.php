<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fffdf8">
    <meta name="description" content="Babybu Toys · Gestión de pedidos, juguetes, clientes y entregas.">
    <title>@yield('titulo', 'Panel') · Babybu Toys</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ auth()->check() ? 'has-session' : 'guest-page' }}">
    <svg class="icon-library" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <symbol id="ui-sparkle" viewBox="0 0 24 24"><path d="m12 3 1.8 6.2L20 11l-6.2 1.8L12 19l-1.8-6.2L4 11l6.2-1.8L12 3Z"/><path d="m19 18 .5 1.5L21 20l-1.5.5L19 22l-.5-1.5L17 20l1.5-.5L19 18Z"/></symbol>
        <symbol id="ui-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V10Z"/><path d="M9 21v-8h6v8"/></symbol>
        <symbol id="ui-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2H3ZM17 5a3 3 0 0 1 0 6M17 14a5 5 0 0 1 4 5v1"/></symbol>
        <symbol id="ui-box" viewBox="0 0 24 24"><path d="m12 2 9 5-9 5-9-5 9-5ZM3 7v10l9 5 9-5V7M12 12v10"/></symbol>
        <symbol id="ui-bag" viewBox="0 0 24 24"><path d="M5 8h14l1 13H4L5 8ZM9 9V6a3 3 0 0 1 6 0v3"/></symbol>
        <symbol id="ui-wallet" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="15" rx="2"/><path d="M3 9h18M15 14h3"/></symbol>
        <symbol id="ui-truck" viewBox="0 0 24 24"><path d="M3 6h12v12H3zM15 10h4l3 4v4h-7v-8Z"/><circle cx="7.5" cy="19" r="2"/><circle cx="18.5" cy="19" r="2"/></symbol>
        <symbol id="ui-chart" viewBox="0 0 24 24"><path d="M3 3v18h18M7 17v-5M12 17V8M17 17V5"/></symbol>
        <symbol id="ui-shield" viewBox="0 0 24 24"><path d="M12 2 4 5v7c0 5 3 8 8 10 5-2 8-5 8-10V5l-8-3Z"/><path d="m9 12 2 2 4-4"/></symbol>
        <symbol id="ui-key" viewBox="0 0 24 24"><circle cx="8" cy="15" r="4"/><path d="m11 12 10-10v5h-4v4h-4"/></symbol>
        <symbol id="ui-logout" viewBox="0 0 24 24"><path d="M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5M14 7l5 5-5 5M8 12h11"/></symbol>
        <symbol id="ui-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></symbol>
        <symbol id="ui-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
        <symbol id="ui-arrow" viewBox="0 0 24 24"><path d="M4 12h16m-6-6 6 6-6 6"/></symbol>
        <symbol id="ui-check" viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></symbol>
        <symbol id="ui-eye" viewBox="0 0 24 24"><path d="M2 12s3-6 10-6 10 6 10 6-3 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></symbol>
        <symbol id="ui-gift" viewBox="0 0 24 24"><rect x="3" y="10" width="18" height="11" rx="2"/><path d="M2 7h20v4H2zM12 7v14M12 7C7 7 6 2 9 2c3 0 3 5 3 5Zm0 0c5 0 6-5 3-5-3 0-3 5-3 5Z"/></symbol>
        <symbol id="ui-puzzle" viewBox="0 0 24 24"><path d="M3 3h7v3c0 2 4 2 4 0V3h7v7h-3c-2 0-2 4 0 4h3v7h-7v-3c0-2-4-2-4 0v3H3v-7h3c2 0 2-4 0-4H3V3Z"/></symbol>
        <symbol id="ui-heart" viewBox="0 0 24 24"><path d="M20.8 4.8a5.5 5.5 0 0 0-7.8 0L12 5.9l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.4a5.5 5.5 0 0 0 0-7.8Z"/></symbol>
        <symbol id="ui-smile" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M8 14c2 3 6 3 8 0M8.5 9h.01M15.5 9h.01"/></symbol>
    </svg>

    <a class="skip-link" href="#contenido">Saltar al contenido principal</a>
    @auth
    <div class="toy-app">
        <header class="toy-masthead">
            <div class="toy-masthead-inner">
                <a class="brand-lockup toy-brand" href="{{ route('panel') }}" aria-label="Babybu Toys, ir a inicio">
                    <span class="toy-brand-mark" aria-hidden="true"><span class="toy-mark-dot"></span><span class="toy-mark-block"></span><span class="toy-mark-stripe"></span></span>
                    <span><strong>babybu<span class="brand-dot">.</span></strong><small>TOYS · TIENDA Y GESTIÓN</small></span>
                </a>
                <div class="toy-masthead-message d-none d-md-flex"><span class="toy-masthead-signal"></span><span>El corazón de tu juguetería, en un solo lugar.</span></div>
                <div class="toy-masthead-tools">
                    <a href="{{ route('pedidos.crear') }}" class="toy-header-order d-none d-md-inline-flex"><svg class="ui-icon"><use href="#ui-plus" /></svg> Nuevo pedido</a>
                    <a class="toy-profile d-none d-lg-flex" href="{{ route('cuenta.contrasena') }}" title="Mi cuenta">
                        <span class="toy-profile-avatar">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="toy-profile-name"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->nombreRol() }}</small></span>
                    </a>
                    <button type="button" class="mobile-menu-button d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#navegacionBabybu" aria-controls="navegacionBabybu" aria-label="Abrir menú"><svg class="ui-icon"><use href="#ui-menu" /></svg></button>
                </div>
            </div>
        </header>
        <div class="toy-nav-band">
            <nav class="app-sidebar toy-navigation offcanvas-lg offcanvas-start" id="navegacionBabybu" tabindex="-1" aria-label="Secciones de Babybu">
                <div class="toy-nav-mobile-header d-flex d-lg-none"><strong>Menú Babybu</strong><button class="btn-close" type="button" data-bs-dismiss="offcanvas" data-bs-target="#navegacionBabybu" aria-label="Cerrar menú"></button></div>
                <div class="toy-nav-links app-nav">
                    <a class="app-nav-link {{ request()->routeIs('panel') ? 'is-active' : '' }}" href="{{ route('panel') }}" @if(request()->routeIs('panel')) aria-current="page" @endif><svg class="ui-icon"><use href="#ui-home" /></svg><span>Inicio</span></a>
<a class="app-nav-link {{ request()->routeIs('clientes.*') ? 'is-active' : '' }}" href="{{ route('clientes.index') }}" @if(request()->routeIs('clientes.*')) aria-current="page" @endif><svg class="ui-icon"><use href="#ui-users" /></svg><span>Clientes</span></a>
<a class="app-nav-link {{ request()->routeIs('inventario.*') ? 'is-active' : '' }}" href="{{ route('inventario.listar') }}" @if(request()->routeIs('inventario.*')) aria-current="page" @endif><svg class="ui-icon"><use href="#ui-box" /></svg><span>Inventario</span></a>
<a class="app-nav-link {{ request()->routeIs('pedidos.*') ? 'is-active' : '' }}" href="{{ route('pedidos.listar') }}" @if(request()->routeIs('pedidos.*')) aria-current="page" @endif><svg class="ui-icon"><use href="#ui-bag" /></svg><span>Pedidos</span></a>
<a class="app-nav-link {{ request()->routeIs('pagos.*') ? 'is-active' : '' }}" href="{{ route('pagos.index') }}" @if(request()->routeIs('pagos.*')) aria-current="page" @endif><svg class="ui-icon"><use href="#ui-wallet" /></svg><span>Pagos</span></a>
<a class="app-nav-link {{ request()->routeIs('entregas.*') ? 'is-active' : '' }}" href="{{ route('entregas.listar') }}" @if(request()->routeIs('entregas.*')) aria-current="page" @endif><svg class="ui-icon"><use href="#ui-truck" /></svg><span>Entregas</span></a>
<a class="app-nav-link {{ request()->routeIs('reportes.*') ? 'is-active' : '' }}" href="{{ route('reportes.index') }}" @if(request()->routeIs('reportes.*')) aria-current="page" @endif><svg class="ui-icon"><use href="#ui-chart" /></svg><span>Reportes</span></a>
@can('administrar-usuarios')<a class="app-nav-link {{ request()->routeIs('usuarios.*') ? 'is-active' : '' }}" href="{{ route('usuarios.index') }}" @if(request()->routeIs('usuarios.*')) aria-current="page" @endif><svg class="ui-icon"><use href="#ui-shield" /></svg><span>Usuarios</span></a>@endcan
                </div>
                <div class="toy-nav-mobile-extra d-lg-none">
                    <a class="app-nav-link" href="{{ route('cuenta.contrasena') }}"><svg class="ui-icon"><use href="#ui-key" /></svg> Mi cuenta</a>
                    <form action="{{ route('sesion.salir') }}" method="POST">@csrf<button class="app-nav-link" type="submit"><svg class="ui-icon"><use href="#ui-logout" /></svg> Cerrar sesión</button></form>
                </div>
            </nav>
        </div>
        <div class="app-stage toy-app-stage">
            <div class="toy-page-context"><span class="toy-breadcrumb"><svg class="ui-icon"><use href="#ui-home" /></svg> MI JUGUETERÍA <span aria-hidden="true">/</span> <strong>@yield('titulo', 'Panel')</strong></span><div class="toy-account-links d-none d-lg-flex"><a href="{{ route('cuenta.contrasena') }}">Mi cuenta</a><span aria-hidden="true">·</span><form action="{{ route('sesion.salir') }}" method="POST">@csrf <button type="submit">Cerrar sesión</button></form></div></div>
            <main id="contenido" class="app-content toy-page-content" tabindex="-1">
                @if (session('estado'))
                    <div class="alert alert-success app-flash" role="status"><svg class="ui-icon"><use href="#ui-check" /></svg><span>{{ session('estado') }}</span></div>
                @endif
                @yield('contenido')
            </main>
            <footer class="app-footer toy-footer"><span>Babybu Toys © {{ date('Y') }}</span><span>Juguetes, pedidos y sonrisas.</span></footer>
        </div>
    </div>
    @else
    <header class="guest-header">
        <div class="guest-header-inner">
            <a class="brand-lockup" href="{{ url('/') }}" aria-label="Babybu Toys, inicio"><span class="brand-mark" aria-hidden="true"><i class="brand-block brand-block-a"></i><i class="brand-block brand-block-b"></i><i class="brand-block brand-block-c"></i></span><span><strong>babybu<span class="brand-dot">.</span></strong><small>JUGUETES & SONRISAS</small></span></a>
            <span class="guest-private"><svg class="ui-icon"><use href="#ui-shield" /></svg> Tu juguetería, bien organizada</span>
        </div>
    </header>
    <main id="contenido" class="guest-main" tabindex="-1">
        @if (session('estado'))<div class="alert alert-success" role="status">{{ session('estado') }}</div>@endif
        @yield('contenido')
    </main>
    <footer class="guest-footer">© {{ date('Y') }} Babybu Toys · Un mundo de juguetes</footer>
    @endauth
</body>
</html>
