@extends('plantillas.principal')
@section('titulo', 'Inventario')
@section('contenido')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <span class="insignia-marca mb-3">INVENTARIO</span>
        <h1 class="fw-bold">Cada juguete, en su lugar.</h1>
        <p class="text-secondary mb-0">Un código por pieza física. Consulta sus datos y movimientos.</p>
    </div>
    <a href="{{ route('inventario.crear') }}" class="btn btn-primary">Registrar juguete</a>
</div>
@include('inventario.errores')
<form method="GET" action="{{ route('inventario.listar') }}" class="card border-0 shadow-sm rounded-4 mb-4" role="search">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-5">
            <label for="buscar" class="form-label">Buscar por código o nombre</label>
            <input id="buscar" name="buscar" class="form-control" maxlength="100" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Ej. BB-001 o muñeca">
        </div>
        <div class="col-sm-6 col-md-2">
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="activos" @selected(($filtros['estado'] ?? 'activos') === 'activos')>Activos</option>
                <option value="inactivos" @selected(($filtros['estado'] ?? '') === 'inactivos')>Inactivos</option>
                <option value="todos" @selected(($filtros['estado'] ?? '') === 'todos')>Todos</option>
            </select>
        </div>
        <div class="col-sm-6 col-md-3">
            <label for="existencias" class="form-label">Existencias</label>
            <select id="existencias" name="existencias" class="form-select">
                <option value="todas">Todas</option>
                <option value="con" @selected(($filtros['existencias'] ?? '') === 'con')>Disponibles para venta</option>
                <option value="sin" @selected(($filtros['existencias'] ?? '') === 'sin')>Sin existencia</option>
            </select>
        </div>
        <div class="col-md-2 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('inventario.listar') }}" class="btn btn-outline-secondary" aria-label="Limpiar filtros">Limpiar</a>
        </div>
    </div>
</form>
<section class="card border-0 shadow-sm rounded-4" aria-label="Juguetes registrados">
    <div class="card-body border-bottom"><strong>{{ $productos->total() }}</strong> registros encontrados</div>
    <p class="small text-secondary d-md-none px-3 pt-3 mb-0">Desliza la tabla para ver todas las columnas.</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <caption class="visually-hidden">Inventario de piezas individuales</caption>
            <thead class="table-light"><tr><th scope="col">Código / juguete</th><th scope="col">Costo</th><th scope="col">Precio</th><th scope="col">Situación / existencia física</th><th scope="col">Estado del registro</th><th scope="col">Acciones</th></tr></thead>
            <tbody>
            @forelse ($productos as $producto)
                <tr>
                    <td><a class="fw-bold" href="{{ route('inventario.mostrar', $producto) }}">{{ $producto->codigo }}</a><div>{{ $producto->nombre }}</div></td>
                    <td class="text-nowrap">Q {{ number_format((float) $producto->costo, 2) }}</td>
                    <td class="text-nowrap">Q {{ number_format((float) $producto->precio, 2) }}</td>
                    <td><span class="badge {{ $producto->reservaActual ? ($producto->situacionInventario() === 'Entregada' ? 'text-bg-secondary' : 'text-bg-warning') : ($producto->activo && $producto->existencias ? 'text-bg-success' : 'text-bg-secondary') }}">{{ $producto->situacionInventario() }}</span><div class="small text-secondary mt-1">{{ $producto->existencias }} pieza(s) en bodega</div></td>
                    <td>{{ $producto->activo ? 'Activo' : 'Inactivo' }}</td>
                    <td class="text-nowrap"><a class="btn btn-outline-primary btn-sm" href="{{ route('inventario.mostrar', $producto) }}">Ver pieza</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-secondary py-5">No hay juguetes para estos filtros. Puedes registrar una pieza o cambiar la búsqueda.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($productos->hasPages()) <div class="card-body">{{ $productos->links('pagination::bootstrap-5') }}</div> @endif
</section>
@endsection
