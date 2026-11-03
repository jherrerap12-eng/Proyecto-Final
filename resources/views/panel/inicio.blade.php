@extends('plantillas.principal')
@section('titulo', 'Inicio')
@section('contenido')
<div class="mb-4">
    <span class="insignia-marca mb-3">ESPACIO DE TRABAJO</span>
    <h1 class="fw-bold">Hola, {{ auth()->user()->name }}.</h1>
    <p class="text-secondary">Bienvenido a Babybu Toys.</p>
</div>
<div class="card border-0 shadow-sm rounded-4">
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
@endsection
