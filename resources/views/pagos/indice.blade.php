@extends('plantillas.principal')
@section('titulo', 'Pagos')
@section('contenido')
<h1 class="fw-bold">Pagos y saldos</h1>
<p class="text-secondary">Consulta el saldo de cada pedido y registra los abonos recibidos.</p>
@if (! $disponible)
    <div class="alert alert-info" role="status">
        <h2 class="h5">Integración con pedidos pendiente</h2>
        <p class="mb-0">Podrás registrar abonos cuando esté disponible el módulo de pedidos. Primero debe existir un pedido con cliente y total confirmado.</p>
    </div>
@else
    <form action="{{ route('pagos.index') }}" method="GET" class="card card-body border-0 shadow-sm mb-4">
        <label for="buscar" class="form-label">Buscar por cliente o número de pedido</label>
        <div class="d-flex flex-wrap gap-2">
            <input class="form-control w-auto flex-grow-1" id="buscar" name="buscar" value="{{ $buscar }}" maxlength="150" type="search">
            <button class="btn btn-primary">Buscar</button>
            <a href="{{ route('pagos.index') }}" class="btn btn-outline-secondary">Limpiar</a>
        </div>
        @error('buscar')<p class="text-danger mt-2" role="alert">{{ $message }}</p>@enderror
    </form>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table align-middle mb-0">
            <caption class="px-3">{{ $pedidos->total() }} pedido(s)</caption>
            <thead><tr><th scope="col">Pedido</th><th scope="col">Cliente</th><th scope="col">Total</th><th scope="col">Abonado</th><th scope="col">Saldo</th><th scope="col">Estado de pago</th><th scope="col">Acción</th></tr></thead>
            <tbody>
            @forelse ($pedidos as $pedido)
                <tr>
                    <th scope="row">#{{ $pedido->id }}</th><td>{{ $pedido->nombre }}</td>
                    <td class="text-nowrap">Q {{ \App\Soporte\Importe::decimal(\App\Soporte\Importe::centavos((string) $pedido->total)) }}</td>
                    <td class="text-nowrap">Q {{ \App\Soporte\Importe::decimal($pedido->abonado) }}</td>
                    <td class="text-nowrap">Q {{ \App\Soporte\Importe::decimal($pedido->saldo) }}</td>
                    <td>{{ $pedido->saldo === 0 ? 'Pagado' : ($pedido->abonado > 0 ? 'Parcial' : 'Pendiente') }}@if($pedido->estado === 'cancelado') <span class="badge text-bg-secondary">Pedido cancelado</span>@endif</td>
                    <td><a class="btn btn-outline-primary btn-sm" href="{{ route('pagos.show', $pedido->id) }}">Ver pagos</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secondary p-4">No hay pedidos que mostrar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-4">{{ $pedidos->links('pagination::bootstrap-5') }}</div>
@endif
@endsection
