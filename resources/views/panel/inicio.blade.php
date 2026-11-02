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
        <h2 class="h4">Tu espacio está listo</h2>
        <p class="text-secondary">Ya puedes registrar piezas y consultar sus movimientos en inventario. El módulo de clientes se integrará por separado.</p>
        <a href="{{ route('inventario.listar') }}" class="btn btn-primary">Abrir inventario</a>
    </div>
</div>
@endsection
