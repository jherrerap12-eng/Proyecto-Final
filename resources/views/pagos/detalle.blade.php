@extends('plantillas.principal')
@section('titulo', 'Pagos del pedido #'.$pedido->id)
@section('contenido')
<a href="{{ route('pedidos.mostrar', $pedido) }}" class="d-inline-block mb-3">← Ver pedido #{{ $pedido->id }}</a>
<h1 class="fw-bold">Pagos del pedido #{{ $pedido->id }}</h1>
<p class="text-secondary">{{ $pedido->cliente_nombre }} · {{ $resumen['saldo'] === 0 ? 'Pagado' : ($resumen['abonado'] > 0 ? 'Parcial' : 'Pendiente') }}</p>
<div class="row g-3 my-3">
    @foreach (['Total' => \App\Soporte\Importe::centavos($pedido->total), 'Verificado' => $resumen['abonado'], 'Por verificar' => $resumen['pendienteVerificacion'], 'Saldo pendiente' => $resumen['saldo']] as $etiqueta => $valor)
    <div class="col-sm-6 col-lg-3"><div class="card card-body border-0 shadow-sm rounded-4"><span>{{ $etiqueta }}</span><strong>Q {{ \App\Soporte\Importe::decimal($valor) }}</strong></div></div>
    @endforeach
</div>
@include('inventario.errores')
@if ($pedido->estado === 'cancelado')
    <div class="alert alert-warning">El pedido está cancelado. No admite nuevos abonos ni verificaciones; se conserva el historial.</div>
@elseif ($pedido->estado === 'borrador' && $pedido->modalidad !== 'deposito')
    <div class="alert alert-info">Confirma el pedido contra entrega antes de registrar abonos.</div>
@elseif ($resumen['saldo'] === 0)
    <div class="alert alert-success">El pedido está pagado.</div>
@elseif ($resumen['saldoRegistrable'] === 0)
    <div class="alert alert-info">Los abonos registrados cubren el total. Verifica los pendientes antes de registrar más.</div>
@else
<section class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4">
    <h2 class="h4">Registrar abono recibido</h2>
    <p class="small text-secondary">Guardar el abono no lo verifica ni confirma la recepción del dinero.</p>
    <form method="POST" action="{{ route('pagos.store', $pedido->id) }}" data-envio-unico>
        @csrf
        <input type="hidden" name="clave_operacion" value="{{ old('clave_operacion', $clave) }}">
        <div class="row g-3">
            <div class="col-md-4"><label for="importe" class="form-label">Importe (Q)</label><input id="importe" name="importe" type="number" min="0.01" max="{{ \App\Soporte\Importe::decimal($resumen['saldoRegistrable']) }}" step="0.01" required value="{{ old('importe') }}" class="form-control"></div>
            <div class="col-md-4"><label for="fecha" class="form-label">Fecha del pago</label><input id="fecha" name="fecha" type="date" required max="{{ now()->toDateString() }}" value="{{ old('fecha', now()->toDateString()) }}" class="form-control"></div>
            <div class="col-md-4"><label for="metodo" class="form-label">Método</label><select id="metodo" name="metodo" required class="form-select"><option value="">Selecciona un método</option>@foreach(['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito'] as $valor => $etiqueta)<option value="{{ $valor }}" @selected(old('metodo') === $valor)>{{ $etiqueta }}</option>@endforeach</select></div>
            <div class="col-md-6"><label for="referencia" class="form-label">Referencia bancaria</label><input id="referencia" name="referencia" maxlength="100" value="{{ old('referencia') }}" class="form-control" aria-describedby="ayuda-referencia"><div id="ayuda-referencia" class="form-text">Obligatoria para transferencia o depósito.</div></div>
            <div class="col-12"><label for="observaciones" class="form-label">Observaciones (opcional)</label><textarea id="observaciones" name="observaciones" maxlength="500" rows="2" class="form-control">{{ old('observaciones') }}</textarea></div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">Guardar abono pendiente</button>
    </form>
</div></section>
@endif
<section class="card border-0 shadow-sm rounded-4">
    <div class="card-body"><h2 class="h4 mb-0">Historial de abonos</h2></div>
    <p class="small text-secondary d-md-none px-3">Desliza la tabla para consultar el historial completo.</p>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th scope="col">Fecha</th><th scope="col">Importe</th><th scope="col">Método / referencia</th><th scope="col">Responsable</th><th scope="col">Estado / verificación</th><th scope="col">Observaciones</th></tr></thead><tbody>
        @forelse ($pagos as $pago)
            <tr><td class="text-nowrap">{{ $pago->fecha_pago->format('d/m/Y') }}</td><td class="text-nowrap">Q {{ $pago->monto }}</td>
                <td>{{ ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito'][$pago->metodo] ?? $pago->metodo }}<div class="small">{{ $pago->referencia ?? 'Sin referencia' }}</div></td>
                <td>{{ $pago->usuario->name }}</td><td>
                @if ($pago->anulado_en)<span class="badge text-bg-secondary">Anulado</span>
                @elseif ($pago->verificado_en && $pago->verificado_por)<span class="badge text-bg-success">Verificado</span><div class="small">{{ $pago->verificador?->name }} · {{ $pago->verificado_en->format('d/m/Y H:i') }}</div>
                @else
                    <span class="badge text-bg-warning">Pendiente</span>
                    @if ($pedido->estado !== 'cancelado' && ($pedido->estado !== 'borrador' || $pedido->modalidad === 'deposito') && auth()->user()->can('verificar-pagos'))
                    <form method="POST" action="{{ route('pagos.verificar', $pago) }}" class="mt-2" data-envio-unico>@csrf<div class="form-check small mb-2"><input class="form-check-input" id="verificar-{{ $pago->id }}" name="confirmar_verificacion" type="checkbox" value="1" required><label class="form-check-label" for="verificar-{{ $pago->id }}">He comprobado la recepción de este abono.</label></div><button type="submit" class="btn btn-outline-primary btn-sm">Verificar abono</button></form>
                    @endif
                @endif
                </td><td class="text-break">{{ $pago->observaciones ?? 'Sin observaciones' }}</td></tr>
        @empty <tr><td colspan="6" class="p-4 text-center text-secondary">Aún no se han registrado abonos.</td></tr>
        @endforelse
        </tbody></table></div>
    @if ($pagos->hasPages())<div class="card-body">{{ $pagos->links('pagination::bootstrap-5') }}</div>@endif
</section>
@endsection
