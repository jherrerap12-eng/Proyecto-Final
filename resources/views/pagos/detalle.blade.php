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
    <section class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4">
        <h2 class="h4">Reembolso</h2>
        <p class="small text-secondary">Verificado sin devolver: <strong>Q {{ \App\Soporte\Importe::decimal($porReembolsar) }}</strong>. Se puede devolver todo o una parte, en uno o varios registros. El reembolso se guarda aparte: los abonos no se modifican.</p>
        @if ($porReembolsar === 0)
            <p class="mb-0 text-secondary">No queda importe verificado por devolver.</p>
        @elseif ($esperaRegreso)
            <div class="alert alert-info mb-0">Las piezas enviadas aún no regresan. Confirma su regreso físico en la <a href="{{ route('pedidos.mostrar', $pedido) }}">ficha del pedido</a> antes de reembolsar.</div>
        @elseif (auth()->user()->cannot('registrar-reembolsos'))
            <p class="mb-0 text-secondary">Solo un administrador puede registrar reembolsos.</p>
        @else
        <form method="POST" action="{{ route('pagos.reembolsar', $pedido->id) }}" data-envio-unico>
            @csrf
            <input type="hidden" name="clave_operacion" value="{{ old('clave_operacion', $clave) }}">
            <div class="row g-3">
                <div class="col-md-4"><label for="reembolso-importe" class="form-label">Importe a devolver (Q)</label><input id="reembolso-importe" name="importe" type="number" min="0.01" max="{{ \App\Soporte\Importe::decimal($porReembolsar) }}" step="0.01" required value="{{ old('importe', \App\Soporte\Importe::decimal($porReembolsar)) }}" class="form-control"></div>
                <div class="col-md-4"><label for="reembolso-fecha" class="form-label">Fecha de la devolución</label><input id="reembolso-fecha" name="fecha" type="date" required max="{{ now()->toDateString() }}" value="{{ old('fecha', now()->toDateString()) }}" class="form-control"></div>
                <div class="col-md-4"><label for="reembolso-metodo" class="form-label">Método</label><select id="reembolso-metodo" name="metodo" required class="form-select"><option value="">Selecciona un método</option>@foreach(['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito'] as $valor => $etiqueta)<option value="{{ $valor }}" @selected(old('metodo') === $valor)>{{ $etiqueta }}</option>@endforeach</select></div>
                <div class="col-md-6"><label for="reembolso-referencia" class="form-label">Referencia del comprobante</label><input id="reembolso-referencia" name="referencia" maxlength="100" value="{{ old('referencia') }}" class="form-control"><div class="form-text">Obligatoria para transferencia o depósito.</div></div>
                <div class="col-md-6"><label for="reembolso-motivo" class="form-label">Motivo</label><input id="reembolso-motivo" name="motivo" required minlength="5" maxlength="300" value="{{ old('motivo') }}" class="form-control" placeholder="Ej.: cliente desistió; se retiene Q 10 de envío"></div>
            </div>
            <button class="btn btn-outline-danger mt-3" type="submit">Registrar reembolso</button>
        </form>
        @endif
    </div></section>
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
        <thead class="table-light"><tr><th scope="col">Fecha</th><th scope="col">Importe</th><th scope="col">Método / referencia</th><th scope="col">Responsable</th><th scope="col">Estado / verificación</th><th scope="col">Observaciones</th>@can('corregir-pagos')<th scope="col">Correcciones</th>@endcan</tr></thead><tbody>
        @forelse ($pagos as $pago)
            <tr><td class="text-nowrap">{{ $pago->fecha_pago->format('d/m/Y') }}</td><td class="text-nowrap">Q {{ $pago->monto }}</td>
                <td>{{ ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito'][$pago->metodo] ?? $pago->metodo }}<div class="small">{{ $pago->referencia ?? 'Sin referencia' }}</div></td>
                <td>{{ $pago->usuario->name }}</td><td>
                @if ($pago->anulado_en)<span class="badge text-bg-secondary">Anulado</span>
                    <div class="small">{{ $pago->anulador?->name ?? 'Sin responsable registrado' }} · {{ $pago->anulado_en->format('d/m/Y H:i') }}</div>
                    @if ($pago->motivo_anulacion)<div class="small text-break text-secondary">{{ $pago->motivo_anulacion }}</div>@endif
                @elseif ($pago->verificado_en && $pago->verificado_por)<span class="badge text-bg-success">Verificado</span><div class="small">{{ $pago->verificador?->name }} · {{ $pago->verificado_en->format('d/m/Y H:i') }}</div>
                @else
                    <span class="badge text-bg-warning">Pendiente</span>
                    @if ($pedido->estado !== 'cancelado' && ($pedido->estado !== 'borrador' || $pedido->modalidad === 'deposito') && auth()->user()->can('verificar-pagos'))
                    <form method="POST" action="{{ route('pagos.verificar', $pago) }}" class="mt-2" data-envio-unico>@csrf<div class="form-check small mb-2"><input class="form-check-input" id="verificar-{{ $pago->id }}" name="confirmar_verificacion" type="checkbox" value="1" required><label class="form-check-label" for="verificar-{{ $pago->id }}">He comprobado la recepción de este abono.</label></div><button type="submit" class="btn btn-outline-primary btn-sm">Verificar abono</button></form>
                    @endif
                @endif
                @if ($pago->corrige_pago_id)<div class="small text-secondary">Corrige al abono #{{ $pago->corrige_pago_id }}</div>@endif
                </td><td class="text-break">{{ $pago->observaciones ?? 'Sin observaciones' }}</td>
                @can('corregir-pagos')
                <td>
                    @if (! $pago->anulado_en)
                        @if (in_array($pedido->estado, ['borrador', 'confirmado'], true) && ($pedido->estado !== 'borrador' || $pedido->modalidad === 'deposito'))
                        <a class="btn btn-sm btn-outline-primary mb-2" href="{{ route('pagos.corregir', $pago) }}">Corregir</a>
                        @endif
                        @if ($pedido->estado !== 'cancelado' || ! $pago->verificado_en)
                        <details><summary class="small text-danger">Anular</summary>
                            <form method="POST" action="{{ route('pagos.anular', $pago) }}" class="mt-2" data-envio-unico>@csrf
                                <label for="motivo-{{ $pago->id }}" class="form-label small">Motivo</label>
                                <input id="motivo-{{ $pago->id }}" name="motivo" class="form-control form-control-sm mb-2" required minlength="5" maxlength="300">
                                <div class="form-check small mb-2"><input class="form-check-input" id="anular-{{ $pago->id }}" name="confirmar_anulacion" type="checkbox" value="1" required><label class="form-check-label" for="anular-{{ $pago->id }}">Anular este abono; seguirá visible en el historial.</label></div>
                                <button type="submit" class="btn btn-outline-danger btn-sm">Confirmar anulación</button>
                            </form>
                        </details>
                        @else
                        <span class="small text-secondary">Se devuelve con un reembolso</span>
                        @endif
                    @elseif ($pago->correccion)
                        <span class="small text-secondary">Reemplazado por #{{ $pago->correccion->id }}</span>
                    @endif
                </td>
                @endcan
                </tr>
        @empty <tr><td colspan="7" class="p-4 text-center text-secondary">Aún no se han registrado abonos.</td></tr>
        @endforelse
        </tbody></table></div>
    @if ($pagos->hasPages())<div class="card-body">{{ $pagos->links('pagination::bootstrap-5') }}</div>@endif
</section>
@if ($reembolsos->isNotEmpty())
<section class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-body"><h2 class="h4 mb-0">Historial de reembolsos</h2></div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th scope="col">Fecha</th><th scope="col">Importe</th><th scope="col">Método / referencia</th><th scope="col">Responsable</th><th scope="col">Motivo</th></tr></thead><tbody>
        @foreach ($reembolsos as $reembolso)
            <tr><td class="text-nowrap">{{ $reembolso->fecha_reembolso->format('d/m/Y') }}</td><td class="text-nowrap">Q {{ $reembolso->monto }}</td><td>{{ ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito'][$reembolso->metodo] ?? $reembolso->metodo }}<div class="small">{{ $reembolso->referencia ?? 'Sin referencia' }}</div></td><td>{{ $reembolso->usuario->name }}</td><td class="text-break">{{ $reembolso->motivo }}</td></tr>
        @endforeach
        </tbody></table></div>
</section>
@endif
@endsection
