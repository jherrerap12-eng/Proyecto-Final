@extends('plantillas.principal')
@section('titulo', 'Pagos')
@section('contenido')
<span class="insignia-marca mb-3">PAGOS Y SALDOS</span>
<h1 class="fw-bold">Cada abono, con su respaldo.</h1>
<p class="text-secondary">Consulta abonos registrados, verifica los recibidos y revisa el saldo de cada pedido.</p>
@include('inventario.errores')
<form action="{{ route('pagos.index') }}" method="GET" class="card card-body border-0 shadow-sm rounded-4 mb-4">
    <label for="buscar" class="form-label">Cliente o número de pedido</label>
    <div class="d-flex flex-wrap gap-2"><input class="form-control w-auto flex-grow-1" id="buscar" name="buscar" value="{{ $buscar }}" maxlength="150" type="search"><button class="btn btn-primary">Buscar</button><a href="{{ route('pagos.index') }}" class="btn btn-outline-secondary">Limpiar</a></div>
</form>
<section class="card border-0 shadow-sm rounded-4">
    <div class="card-body">{{ $pedidos->total() }} pedido(s)</div>
    <p class="small text-secondary d-md-none px-3">Desliza la tabla para ver todas las columnas.</p>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th scope="col">Pedido / cliente</th><th scope="col">Total</th><th scope="col">Verificado</th><th scope="col">Por verificar</th><th scope="col">Saldo pendiente</th><th scope="col">Estado de pago</th><th scope="col">Acción</th></tr></thead><tbody>
        @forelse ($pedidos as $pedido)
            @php($resumen = $resumenes[$pedido->id])
            <tr><td><a href="{{ route('pedidos.mostrar', $pedido) }}">#{{ $pedido->id }}</a><div>{{ $pedido->cliente_nombre }}</div><span class="small text-secondary">{{ ucfirst($pedido->estado) }}</span></td>
                <td class="text-nowrap">Q {{ $pedido->total }}</td><td class="text-nowrap">Q {{ \App\Soporte\Importe::decimal($resumen['abonado']) }}</td>
                <td class="text-nowrap">Q {{ \App\Soporte\Importe::decimal($resumen['pendienteVerificacion']) }}</td><td class="text-nowrap">Q {{ \App\Soporte\Importe::decimal($resumen['saldo']) }}</td>
                <td>{{ $resumen['saldo'] === 0 ? 'Pagado' : ($resumen['abonado'] > 0 ? 'Parcial' : 'Pendiente') }}</td>
                <td><a class="btn btn-outline-primary btn-sm text-nowrap" href="{{ route('pagos.show', $pedido->id) }}">Ver pagos</a></td></tr>
        @empty <tr><td colspan="7" class="text-center text-secondary p-4">No hay pedidos que mostrar.</td></tr>
        @endforelse
        </tbody></table></div>
    @if ($pedidos->hasPages())<div class="card-body">{{ $pedidos->links('pagination::bootstrap-5') }}</div>@endif
</section>
@endsection
