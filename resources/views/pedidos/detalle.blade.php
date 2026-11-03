@extends('plantillas.principal')
@section('titulo', 'Pedido #'.$pedido->id)
@section('contenido')
<a href="{{ route('pedidos.listar') }}">← Volver a pedidos</a>
<div class="d-flex flex-wrap justify-content-between gap-3 mt-3 mb-4">
    <div><span class="insignia-marca mb-2">{{ strtoupper($pedido->estado) }}</span><h1 class="fw-bold">Pedido #{{ $pedido->id }}</h1><p class="text-secondary mb-0">{{ $pedido->cliente_nombre }} · {{ $pedido->cliente_telefono }}</p></div>
    <div><a class="btn btn-outline-primary" href="{{ route('pagos.show', $pedido->id) }}">Ver pagos y registrar abonos</a></div>
    <div><a class="btn btn-outline-primary" href="{{ route('entregas.mostrar', $pedido) }}">Ver entrega</a></div>
    @if ($pedido->estado === 'borrador' && ! $pedido->pagos()->exists())<div><a class="btn btn-outline-primary" href="{{ route('pedidos.editar', $pedido) }}">Editar borrador</a></div>@endif
</div>
@include('inventario.errores')
<div class="row g-4">
    <section class="col-lg-7"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
        <h2 class="h5">Resumen</h2><dl class="row mb-0">
            <dt class="col-sm-5">Total</dt><dd class="col-sm-7 fw-bold">Q {{ number_format((float) $pedido->total, 2) }}</dd>
            <dt class="col-sm-5">Pago verificado</dt><dd class="col-sm-7">Q {{ number_format($pagado / 100, 2) }}</dd>
            <dt class="col-sm-5">Saldo pendiente</dt><dd class="col-sm-7">Q {{ number_format($saldo / 100, 2) }}</dd>
            <dt class="col-sm-5">Estado de pago</dt><dd class="col-sm-7">{{ ucfirst($pedido->estadoPago()) }}</dd>
            <dt class="col-sm-5">Modalidad</dt><dd class="col-sm-7">{{ $pedido->modalidad === 'deposito' ? 'Depósito previo' : 'Contra entrega' }}</dd>
            <dt class="col-sm-5">Mínimo para reserva firme</dt><dd class="col-sm-7">{{ $pedido->porcentaje_deposito }} % · Q {{ number_format($pedido->depositoMinimoCentavos() / 100, 2) }}</dd>
            <dt class="col-sm-5">Entrega</dt><dd class="col-sm-7">{{ str_replace('_', ' ', ucfirst($pedido->estado_logistico)) }}</dd>
            <dt class="col-sm-5">Dirección registrada</dt><dd class="col-sm-7 text-break">{{ $pedido->cliente_direccion ?: 'Sin dirección.' }}</dd>
            @if ($pedido->observaciones)<dt class="col-sm-5">Observaciones</dt><dd class="col-sm-7 text-break">{{ $pedido->observaciones }}</dd>@endif
        </dl>
    </div></div></section>
    <section class="col-lg-5"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
        @if ($pedido->estado === 'borrador')
            <h2 class="h5">Confirmar reserva</h2><p class="small text-secondary">Las piezas se apartarán únicamente si siguen disponibles. Un pedido con depósito necesita el mínimo verificado.</p>
            <form method="POST" action="{{ route('pedidos.confirmar', $pedido) }}" data-envio-unico>@csrf<input type="hidden" name="version" value="{{ $pedido->version }}"><button class="btn btn-primary" type="submit">Confirmar pedido</button></form>
        @elseif ($pedido->estado === 'confirmado')
            <h2 class="h5">Piezas reservadas</h2><p class="text-secondary mb-0">Este pedido ya apartó sus piezas. Revisa el tipo de reserva en el detalle.</p>
        @else
            <h2 class="h5">Pedido cancelado</h2><p class="text-secondary mb-0">Se conserva el historial del pedido y sus pagos. Los reembolsos deben revisarse por separado.</p>
        @endif
        @if ($pedido->reservas->contains('estado', 'devolucion_pendiente'))
            <hr><h3 class="h6">Confirmar regreso físico</h3><p class="small text-secondary">Las piezas siguen bloqueadas hasta que regresen todas al negocio.</p>
            <form method="POST" action="{{ route('pedidos.devolver', $pedido) }}" data-envio-unico>@csrf<div class="form-check mb-3"><input id="confirmar-regreso" class="form-check-input" type="checkbox" name="confirmar_regreso" value="1" required><label class="form-check-label" for="confirmar-regreso">Confirmo que todas las piezas pendientes ya regresaron físicamente.</label></div><button type="submit" class="btn btn-outline-primary">Confirmar regreso y liberar</button></form>
        @endif
    </div></div></section>
</div>
<section class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-body"><h2 class="h5 mb-0">Piezas del pedido</h2></div><p class="d-md-none small text-secondary px-3">Desliza la tabla para ver todas las columnas.</p>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th scope="col">Pieza</th><th scope="col">Costo histórico</th><th scope="col">Precio histórico</th><th scope="col">Reserva</th></tr></thead><tbody>
    @foreach ($pedido->detalles as $detalle)
        @php($reserva = $pedido->reservas->firstWhere('detalle_pedido_id', $detalle->id))
        <tr><td><a href="{{ route('inventario.mostrar', $detalle->producto_id) }}">{{ $detalle->codigo }}</a><div>{{ $detalle->nombre }}</div></td><td class="text-nowrap">Q {{ number_format((float) $detalle->costo, 2) }}</td><td class="text-nowrap">Q {{ number_format((float) $detalle->precio, 2) }}</td><td>{{ $reserva ? ucfirst($reserva->tipo).' · '.str_replace('_', ' ', $reserva->estado) : 'Sin reserva' }}</td></tr>
    @endforeach
    </tbody></table></div>
</section>
@if (in_array($pedido->estado, ['borrador', 'confirmado'], true))
<section class="card border-0 shadow-sm rounded-4 mt-4"><div class="card-body p-4">
    <h2 class="h5">Cancelar pedido</h2><p class="small text-secondary">Antes del envío, las piezas se liberan. Si ya se enviaron, quedarán pendientes de regreso físico. Los pagos no se borran ni se reembolsan automáticamente.</p>
    <form method="POST" action="{{ route('pedidos.cancelar', $pedido) }}" class="row g-3 align-items-end" data-envio-unico>@csrf<input type="hidden" name="version" value="{{ $pedido->version }}"><div class="col-md-9"><label for="motivo" class="form-label">Motivo de cancelación</label><input id="motivo" name="motivo" class="form-control" required maxlength="255" value="{{ old('motivo') }}"></div><div class="col-md-3"><button type="submit" class="btn btn-outline-danger">Cancelar pedido</button></div></form>
</div></section>
@endif
<section class="card border-0 shadow-sm rounded-4 mt-4"><div class="card-body p-4"><h2 class="h5">Historial del pedido</h2>
    @foreach ($eventos as $evento)<div class="border-top py-3"><div class="small text-secondary">{{ $evento->created_at->format('d/m/Y H:i') }} · {{ $evento->usuario->name }}</div><div class="text-break">{{ $evento->descripcion }}</div></div>@endforeach
    @if ($eventos->hasPages()){{ $eventos->links('pagination::bootstrap-5') }}@endif
</div></section>
@endsection
