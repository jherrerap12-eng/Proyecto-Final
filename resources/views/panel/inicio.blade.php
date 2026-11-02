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
        <h2 class="h4">Tu espacio está listo</h2>
        <p class="text-secondary">Ya puedes registrar piezas y consultar sus movimientos en inventario.</p>
        <a href="{{ route('inventario.listar') }}" class="btn btn-primary">Abrir inventario</a>
        <h2 class="h4 mt-4">Pagos y saldos</h2>
        <p class="text-secondary">Consulta los abonos recibidos y el saldo pendiente de cada pedido.</p>
        <a href="{{ route('pagos.index') }}" class="btn btn-primary">Abrir pagos</a>
    </div>
</div>
@endsection
