@extends('plantillas.principal')
@section('titulo', 'Corregir abono #'.$pago->id)
@section('contenido')
<a href="{{ route('pagos.show', $pedido->id) }}" class="d-inline-block mb-3">← Volver a pagos del pedido #{{ $pedido->id }}</a>
<h1 class="fw-bold">Corregir abono #{{ $pago->id }}</h1>
<p class="text-secondary">El abono original no se edita ni se borra: queda <strong>anulado</strong> y enlazado al nuevo registro con los datos correctos, para conservar el historial.</p>
@include('inventario.errores')
<div class="row g-4">
<section class="col-lg-4"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
    <h2 class="h5">Registro original</h2>
    <dl class="row mb-0 small">
        <dt class="col-5">Importe</dt><dd class="col-7">Q {{ $pago->monto }}</dd>
        <dt class="col-5">Fecha</dt><dd class="col-7">{{ $pago->fecha_pago->format('d/m/Y') }}</dd>
        <dt class="col-5">Método</dt><dd class="col-7">{{ ucfirst($pago->metodo) }}</dd>
        <dt class="col-5">Referencia</dt><dd class="col-7 text-break">{{ $pago->referencia ?? 'Sin referencia' }}</dd>
        <dt class="col-5">Estado</dt><dd class="col-7">{{ $pago->verificado_en ? 'Verificado' : 'Pendiente' }}</dd>
        <dt class="col-5">Registró</dt><dd class="col-7">{{ $pago->usuario->name }}</dd>
    </dl>
</div></div></section>
<section class="col-lg-8"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
    <h2 class="h5">Datos correctos</h2>
    <form method="POST" action="{{ route('pagos.corregir.guardar', $pago) }}" data-envio-unico>
        @csrf
        <input type="hidden" name="clave_operacion" value="{{ old('clave_operacion', $clave) }}">
        <div class="row g-3">
            <div class="col-md-4"><label for="importe" class="form-label">Importe (Q)</label><input id="importe" name="importe" type="number" min="0.01" max="{{ \App\Soporte\Importe::decimal($maximo) }}" step="0.01" required value="{{ old('importe', $pago->monto) }}" class="form-control" aria-describedby="ayuda-importe"><div id="ayuda-importe" class="form-text">Máximo Q {{ \App\Soporte\Importe::decimal($maximo) }}.</div></div>
            <div class="col-md-4"><label for="fecha" class="form-label">Fecha del pago</label><input id="fecha" name="fecha" type="date" required max="{{ now()->toDateString() }}" value="{{ old('fecha', $pago->fecha_pago->format('Y-m-d')) }}" class="form-control"></div>
            <div class="col-md-4"><label for="metodo" class="form-label">Método</label><select id="metodo" name="metodo" required class="form-select">@foreach(['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito'] as $valor => $etiqueta)<option value="{{ $valor }}" @selected(old('metodo', $pago->metodo) === $valor)>{{ $etiqueta }}</option>@endforeach</select></div>
            <div class="col-md-6"><label for="referencia" class="form-label">Referencia bancaria</label><input id="referencia" name="referencia" maxlength="100" value="{{ old('referencia', $pago->referencia) }}" class="form-control"><div class="form-text">Obligatoria para transferencia o depósito.</div></div>
            <div class="col-md-6"><label for="observaciones" class="form-label">Observaciones (opcional)</label><input id="observaciones" name="observaciones" maxlength="500" value="{{ old('observaciones', $pago->observaciones) }}" class="form-control"></div>
            <div class="col-12"><label for="motivo" class="form-label">Motivo de la corrección</label><input id="motivo" name="motivo" required minlength="5" maxlength="300" value="{{ old('motivo') }}" class="form-control" placeholder="Ej.: se digitó Q 50 y el comprobante dice Q 45"></div>
            @can('verificar-pagos')
            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="verificar" name="verificar" value="1" @checked(old('verificar'))><label class="form-check-label" for="verificar">Ya comprobé la recepción real de este importe: registrar la corrección como verificada.</label></div><div class="form-text">Sin marcar, el abono corregido queda pendiente y debe verificarse aparte.</div></div>
            @endcan
        </div>
        <button type="submit" class="btn btn-primary mt-3">Guardar corrección</button>
    </form>
</div></div></section>
</div>
@endsection
