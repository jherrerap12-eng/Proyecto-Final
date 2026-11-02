@extends('plantillas.principal')
@section('titulo', 'Pedidos')
@section('contenido')
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div><span class="insignia-marca mb-3">PEDIDOS Y RESERVAS</span><h1 class="fw-bold">Un pedido, cada pieza en orden.</h1><p class="text-secondary">Consulta borradores, reservas y cancelaciones.</p></div>
    <div><a class="btn btn-primary" href="{{ route('pedidos.crear') }}">Crear pedido</a></div>
</div>
@include('inventario.errores')
<form method="GET" action="{{ route('pedidos.listar') }}" class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-6"><label for="buscar" class="form-label">Número de pedido o nombre del cliente</label><input id="buscar" name="buscar" class="form-control" maxlength="100" value="{{ $filtros['buscar'] ?? '' }}"></div>
        <div class="col-md-3"><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos</option>@foreach (['borrador', 'confirmado', 'cancelado'] as $estado)<option value="{{ $estado }}" @selected(($filtros['estado'] ?? '') === $estado)>{{ ucfirst($estado) }}</option>@endforeach</select></div>
        <div class="col-md-3 d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit">Filtrar</button><a class="btn btn-outline-secondary" href="{{ route('pedidos.listar') }}">Limpiar</a></div>
    </div>
</form>
<section class="card border-0 shadow-sm rounded-4">
    <div class="card-body"><strong>{{ $pedidos->total() }}</strong> pedidos encontrados</div>
    <p class="d-md-none small text-secondary px-3">Desliza la tabla para ver todas las columnas.</p>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th scope="col">Pedido</th><th scope="col">Cliente</th><th scope="col">Modalidad</th><th scope="col">Estado</th><th scope="col">Total</th><th scope="col">Acción</th></tr></thead><tbody>
    @forelse ($pedidos as $pedido)
        <tr><td>#{{ $pedido->id }}<div class="small text-secondary">{{ $pedido->created_at->format('d/m/Y') }}</div></td><td>{{ $pedido->cliente_nombre }}</td><td>{{ $pedido->modalidad === 'deposito' ? 'Depósito previo' : 'Contra entrega' }}</td><td><span class="badge {{ $pedido->estado === 'confirmado' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($pedido->estado) }}</span></td><td class="text-nowrap">Q {{ number_format((float) $pedido->total, 2) }}</td><td><a class="btn btn-outline-primary btn-sm" href="{{ route('pedidos.mostrar', $pedido) }}">Ver pedido</a></td></tr>
    @empty
        <tr><td colspan="6" class="text-center text-secondary py-5">No hay pedidos para estos filtros.</td></tr>
    @endforelse
    </tbody></table></div>
    @if ($pedidos->hasPages())<div class="card-body">{{ $pedidos->links('pagination::bootstrap-5') }}</div>@endif
</section>
@endsection
