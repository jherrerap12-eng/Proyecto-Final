@extends('plantillas.principal')
@section('titulo', 'Detalle del cliente')
@section('contenido')
<a href="{{ route('clientes.index') }}" class="d-inline-block mb-3">Volver a clientes</a>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <h1 class="fw-bold mb-0">{{ $cliente->nombre }}</h1>
    <a class="btn btn-primary" href="{{ route('clientes.edit', $cliente) }}">Editar cliente</a>
</div>
<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
    <dl class="row mb-0">
        <dt class="col-sm-3">Teléfono / WhatsApp</dt><dd class="col-sm-9">{{ $cliente->telefono }}</dd>
        <dt class="col-sm-3">Correo electrónico</dt><dd class="col-sm-9 text-break">{{ $cliente->correo ?? 'No registrado' }}</dd>
        <dt class="col-sm-3">Dirección</dt><dd class="col-sm-9 text-break">{{ $cliente->direccion ?? 'No registrada' }}</dd>
    </dl>
</div></div>
<details class="card border-0 shadow-sm p-4">
    <summary class="text-danger">Dar de baja al cliente</summary>
    <p class="mt-3">El cliente dejará de aparecer en el listado. Sus datos se conservarán para mantener el historial.</p>
    <form action="{{ route('clientes.destroy', $cliente) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">Confirmar baja del cliente</button>
    </form>
</details>
@endsection
