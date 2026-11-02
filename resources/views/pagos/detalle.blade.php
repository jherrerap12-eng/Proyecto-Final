@extends('plantillas.principal')
@section('titulo', 'Pagos del pedido #'.$pedido->id)
@section('contenido')
<a href="{{ route('pagos.index') }}" class="d-inline-block mb-3">Volver a pagos</a>
<h1 class="fw-bold">Pagos del pedido #{{ $pedido->id }}</h1>
<p class="text-secondary">{{ $pedido->nombre }} · {{ $saldo === 0 ? 'Pagado' : ($abonado > 0 ? 'Parcial' : 'Pendiente') }}</p>
<div class="row g-3 my-3">
    <div class="col-sm-4"><div class="card card-body"><span>Total del pedido</span><strong>Q {{ \App\Soporte\Importe::decimal(\App\Soporte\Importe::centavos((string) $pedido->total)) }}</strong></div></div>
    <div class="col-sm-4"><div class="card card-body"><span>Total abonado</span><strong>Q {{ \App\Soporte\Importe::decimal($abonado) }}</strong></div></div>
    <div class="col-sm-4"><div class="card card-body"><span>Saldo pendiente</span><strong>Q {{ \App\Soporte\Importe::decimal($saldo) }}</strong></div></div>
</div>
@if ($errors->any())
    <div class="alert alert-danger" role="alert"><p>Revisa los datos del abono:</p><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if ($pedido->estado === 'cancelado')
    <div class="alert alert-warning">El pedido está cancelado. No admite nuevos abonos.</div>
@elseif ($saldo <= 0)
    <div class="alert alert-success">El pedido está pagado.</div>
@else
<section class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
    <h2 class="h4">Registrar abono recibido</h2>
    <form method="POST" action="{{ route('pagos.store', $pedido->id) }}">
        @csrf
        <input type="hidden" name="clave_operacion" value="{{ old('clave_operacion', $clave) }}">
        <div class="row g-3">
            <div class="col-md-4"><label for="importe" class="form-label">Importe (Q)</label><input id="importe" name="importe" type="number" min="0.01" max="{{ \App\Soporte\Importe::decimal($saldo) }}" step="0.01" required value="{{ old('importe') }}" class="form-control @error('importe') is-invalid @enderror">@error('importe')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label for="fecha" class="form-label">Fecha del pago</label><input id="fecha" name="fecha" type="date" required max="{{ now()->toDateString() }}" value="{{ old('fecha', now()->toDateString()) }}" class="form-control @error('fecha') is-invalid @enderror">@error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label for="metodo" class="form-label">Método</label><select id="metodo" name="metodo" required class="form-select @error('metodo') is-invalid @enderror"><option value="">Selecciona un método</option>@foreach(['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito'] as $valor => $etiqueta)<option value="{{ $valor }}" @selected(old('metodo') === $valor)>{{ $etiqueta }}</option>@endforeach</select>@error('metodo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label for="referencia" class="form-label">Referencia bancaria</label><input id="referencia" name="referencia" maxlength="100" value="{{ old('referencia') }}" class="form-control @error('referencia') is-invalid @enderror" aria-describedby="ayuda-referencia"><div id="ayuda-referencia" class="form-text">Obligatoria para transferencia o depósito.</div>@error('referencia')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label for="observaciones" class="form-label">Observaciones (opcional)</label><textarea id="observaciones" name="observaciones" maxlength="500" rows="2" class="form-control @error('observaciones') is-invalid @enderror">{{ old('observaciones') }}</textarea>@error('observaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">Guardar abono</button>
    </form>
</div></section>
@endif
<h2 class="h4">Historial de abonos</h2>
<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table align-middle mb-0"><thead><tr><th scope="col">Fecha</th><th scope="col">Importe</th><th scope="col">Método</th><th scope="col">Referencia</th><th scope="col">Registrado por</th><th scope="col">Observaciones</th></tr></thead><tbody>
    @forelse($pagos as $pago)
        <tr><td class="text-nowrap">{{ $pago->fecha->format('d/m/Y') }}</td><td class="text-nowrap">Q {{ $pago->importe }}</td><td>{{ ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito'][$pago->metodo] }}</td><td class="text-break">{{ $pago->referencia ?? 'Sin referencia' }}</td><td>{{ $pago->usuario->name }}</td><td class="text-break">{{ $pago->observaciones ?? 'Sin observaciones' }}</td></tr>
    @empty
        <tr><td colspan="6" class="p-4 text-center text-secondary">Aún no se han registrado abonos.</td></tr>
    @endforelse
    </tbody></table>
</div></div>
@endsection
