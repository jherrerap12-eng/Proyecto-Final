@extends('plantillas.principal')
@section('titulo', 'Inicio')
@section('contenido')

<!-- 1. TU SALUDO ORIGINAL ARRIBA -->
<div class="mb-4">
    <span class="insignia-marca mb-3">ESPACIO DE TRABAJO</span>
    <h1 class="fw-bold">Hola, {{ auth()->user()->name }}.</h1>
    <p class="text-secondary">Bienvenido a Babybu Toys.</p>
</div>

<!-- 2. TUS BOTONES Y ACCESOS ORIGINALES -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4 p-lg-5">
        <h2 class="h4">Gestión de clientes</h2>
        <p class="text-secondary">Registra compradores y consulta sus datos de contacto.</p>
        <a class="btn btn-primary" href="{{ route('clientes.index') }}">Ir a clientes</a>
        
        <h2 class="h4 mt-4">Inventario</h2>
        <p class="text-secondary">Registra piezas individuales y consulta su disponibilidad y movimientos.</p>
        <a href="{{ route('inventario.listar') }}" class="btn btn-primary">Abrir inventario</a>
        
        <h2 class="h4 mt-4">Pedidos y reservas</h2>
        <p class="text-secondary">Selecciona un cliente, reúne sus juguetes y confirma la reserva.</p>
        <a href="{{ route('pedidos.listar') }}" class="btn btn-primary">Abrir pedidos</a>
        
        <h2 class="h4 mt-4">Pagos y saldos</h2>
        <p class="text-secondary">Registra abonos, verifica los recibidos y consulta el saldo de cada pedido.</p>
        <a href="{{ route('pagos.index') }}" class="btn btn-primary">Abrir pagos</a>
        
        <h2 class="h4 mt-4">Entregas</h2>
        <p class="text-secondary">Empaca pedidos, registra envíos y confirma su entrega física.</p>
        <a href="{{ route('entregas.listar') }}" class="btn btn-primary">Abrir entregas</a>
    </div>
</div>

<!-- ========================================== -->
<!-- 3. DASHBOARD Y MÉTRICAS (Ubicado Abajo)     -->
<!-- ========================================== -->

<div class="mb-4">
    <h3 class="fw-bold text-dark">Resumen Operativo y Financiero</h3>
    <p class="text-secondary">Estadísticas generales del sistema.</p>
</div>

<!-- Resumen de Ventas y Ganancia Bruta -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="card-title text-secondary fs-6">Ventas Totales (Entregadas)</h5>
                <p class="display-6 fw-bold text-primary mb-0">
                    Q {{ number_format($resumenVentas->total_ventas ?? 0, 2) }}
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="card-title text-secondary fs-6">Ganancia Bruta</h5>
                <p class="display-6 fw-bold text-success mb-0">
                    Q {{ number_format($resumenVentas->total_ganancia ?? 0, 2) }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Métricas Operativas (Pedidos, Inventario y Saldos) -->
<div class="row g-3 mb-4">
    <!-- Pedidos -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Estado de Pedidos</h2>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        Por preparar <span class="badge bg-warning text-dark rounded-pill">{{ $pedidosPorPreparar }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        Enviados <span class="badge bg-info text-dark rounded-pill">{{ $pedidosEnviados }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        Entregados <span class="badge bg-success rounded-pill">{{ $pedidosEntregados }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Inventario -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Control de Piezas</h2>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        Disponibles <span class="badge bg-primary rounded-pill">{{ $piezasDisponibles }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        Reservadas <span class="badge bg-secondary rounded-pill">{{ $piezasReservadas }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Saldos por cobrar -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                <h2 class="h5 fw-bold mb-2 text-danger">Saldos por Cobrar</h2>
                <p class="text-secondary small mb-2">Pendiente de pago en pedidos</p>
                <h3 class="fw-bold text-danger mb-0">Q {{ number_format($saldosPorCobrar ?? 0, 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Estado Vacío condicional -->
@if(($pedidosPorPreparar + $pedidosEnviados + $pedidosEntregados) === 0)
    <div class="alert alert-secondary text-center py-4 rounded-4 mb-4" role="alert">
        <h5 class="alert-heading">No hay actividad reciente en el sistema</h5>
        <p class="mb-0">Comienza registrando clientes, piezas o pedidos para visualizar las estadísticas completas.</p>
    </div>
@endif

@endsection