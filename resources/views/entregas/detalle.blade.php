@extends('plantillas.principal')
@section('titulo', 'Entrega del pedido #'.$pedido->id)
@section('contenido')
<a href="{{ route('entregas.listar') }}">← Volver a entregas</a>
<div class="d-flex flex-wrap justify-content-between gap-3 mt-3 mb-4"><div><span class="insignia-marca mb-2">{{ strtoupper(str_replace('_', ' ', $pedido->estado_logistico)) }}</span><h1 class="fw-bold">Entrega del pedido #{{ $pedido->id }}</h1><p class="text-secondary mb-0">{{ $pedido->cliente_nombre }} · {{ $pedido->cliente_telefono }}</p></div><div class="d-flex flex-wrap gap-2 align-items-start"><a class="btn btn-outline-primary" href="{{ route('pedidos.mostrar', $pedido) }}">Ver pedido</a><a class="btn btn-outline-primary" href="{{ route('pagos.show', $pedido->id) }}">Ver pagos</a></div></div>
@include('inventario.errores')
@if ($pedido->estado === 'cancelado')
    <div class="alert alert-warning">Pedido cancelado. No admite empaque, envío ni entrega. @if ($pedido->reservas->contains('estado', 'devolucion_pendiente'))Las piezas siguen bloqueadas hasta regresar físicamente. <a href="{{ route('pedidos.mostrar', $pedido) }}">Confirmar regreso desde el pedido</a>.@endif</div>
@elseif ($pedido->estado !== 'confirmado')
    <div class="alert alert-info">Confirma el pedido antes de preparar su entrega.</div>
@endif
<div class="row g-4">
    <section class="col-lg-5"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><h2 class="h5">Recorrido del pedido</h2><dl class="mb-0">
        <dt class="mt-3">Empacado</dt><dd>{{ $envio?->empacado_en?->format('d/m/Y H:i') ?? 'Pendiente' }} @if ($envio?->empacador)<div class="small text-secondary">{{ $envio->empacador->name }}</div>@endif</dd>
        <dt class="mt-3">Enviado</dt><dd>{{ $envio?->enviado_en?->format('d/m/Y H:i') ?? 'Pendiente' }} @if ($envio?->remitente)<div class="small text-secondary">{{ $envio->remitente->name }}</div>@endif</dd>
        <dt class="mt-3">Entregado</dt><dd>{{ $envio?->entregado_en?->format('d/m/Y H:i') ?? 'Pendiente' }} @if ($envio?->confirmador)<div class="small text-secondary">{{ $envio->confirmador->name }}</div>@endif</dd>
        @if ($envio?->devuelto_en)<dt class="mt-3">Regreso físico confirmado</dt><dd>{{ $envio->devuelto_en->format('d/m/Y H:i') }}<div class="small text-secondary">{{ $envio->receptorDevolucion?->name }}</div></dd>@endif
    </dl></div></div></section>
    <section class="col-lg-7"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
        @if ($pedido->estado === 'confirmado' && $pedido->estado_logistico === 'por_preparar')
            <h2 class="h5">Preparar el paquete</h2><p class="text-secondary">Reúne todas las piezas y confirma el empaque. Las existencias físicas cambiarán cuando registres el envío.</p>
            <form method="POST" action="{{ route('entregas.empacar', $pedido) }}" data-envio-unico>@csrf<input type="hidden" name="version" value="{{ $pedido->version }}"><button class="btn btn-primary" type="submit">Marcar como empacado</button></form>
        @elseif ($pedido->estado === 'confirmado' && $pedido->estado_logistico === 'empacado' && $envio)
            <h2 class="h5">Registrar el envío</h2><p class="small text-secondary">Al confirmar, todas las piezas saldrán físicamente del inventario. Revisa el destino y el transporte.</p>
            <form method="POST" action="{{ route('entregas.enviar', $pedido) }}" data-envio-unico>@csrf<input type="hidden" name="version" value="{{ $pedido->version }}"><div class="row g-3">
                <div class="col-md-6"><label for="transportista" class="form-label">Transportista *</label><select id="transportista" name="transportista" class="form-select" required><option value="">Selecciona</option><option value="forza" @selected(old('transportista', $envio->transportista) === 'forza')>Forza</option><option value="mensajeria_propia" @selected(old('transportista', $envio->transportista) === 'mensajeria_propia')>Mensajería propia</option></select></div>
                <div class="col-md-6"><label for="guia" class="form-label">Guía o referencia</label><input id="guia" name="guia" class="form-control" maxlength="100" value="{{ old('guia', $envio->guia) }}" aria-describedby="ayuda-guia"><div class="form-text" id="ayuda-guia">Obligatoria para Forza.</div></div>
                <div class="col-md-7"><label for="destinatario" class="form-label">Destinatario *</label><input id="destinatario" name="destinatario" class="form-control" maxlength="150" required value="{{ old('destinatario', $envio->destinatario) }}"></div>
                <div class="col-md-5"><label for="telefono" class="form-label">Teléfono *</label><input id="telefono" name="telefono" type="tel" class="form-control" maxlength="50" required value="{{ old('telefono', $envio->telefono) }}"></div>
                <div class="col-12"><label for="direccion" class="form-label">Dirección de entrega *</label><textarea id="direccion" name="direccion" class="form-control" rows="2" maxlength="500" required>{{ old('direccion', $envio->direccion) }}</textarea></div>
                <div class="col-12"><label for="observaciones" class="form-label">Indicaciones de envío</label><textarea id="observaciones" name="observaciones" class="form-control" rows="2" maxlength="500">{{ old('observaciones', $envio->observaciones) }}</textarea></div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Confirmar envío y salida de piezas</button></div>
            </div></form>
        @elseif ($envio?->enviado_en)
            <h2 class="h5">Datos del envío</h2><dl class="row mb-0"><dt class="col-sm-4">Transportista</dt><dd class="col-sm-8">{{ $envio->transportista === 'forza' ? 'Forza' : 'Mensajería propia' }}</dd><dt class="col-sm-4">Guía</dt><dd class="col-sm-8 text-break">{{ $envio->guia ?: 'Sin guía' }}</dd><dt class="col-sm-4">Destinatario</dt><dd class="col-sm-8">{{ $envio->destinatario }}</dd><dt class="col-sm-4">Teléfono</dt><dd class="col-sm-8">{{ $envio->telefono }}</dd><dt class="col-sm-4">Dirección</dt><dd class="col-sm-8 text-break">{{ $envio->direccion }}</dd>@if ($envio->observaciones)<dt class="col-sm-4">Indicaciones</dt><dd class="col-sm-8 text-break">{{ $envio->observaciones }}</dd>@endif @if ($envio->observaciones_entrega)<dt class="col-sm-4">Notas de entrega</dt><dd class="col-sm-8 text-break">{{ $envio->observaciones_entrega }}</dd>@endif</dl>
        @else
            <h2 class="h5">Seguimiento</h2><p class="text-secondary mb-0">Se conserva el estado del pedido. Consulta su historial para revisar las operaciones realizadas.</p>
        @endif
    </div></div></section>
</div>
@if ($pedido->estado === 'confirmado' && $pedido->estado_logistico === 'enviado' && $envio?->enviado_en)
<section class="card border-0 shadow-sm rounded-4 mt-4"><div class="card-body p-4"><h2 class="h5">Confirmar entrega</h2><p class="small text-secondary">Confirma únicamente cuando el destinatario recibió el pedido. Esta acción no registra ni verifica cobros.</p>
    <form method="POST" action="{{ route('entregas.entregar', $pedido) }}" data-envio-unico>@csrf<input type="hidden" name="version" value="{{ $pedido->version }}"><div class="mb-3"><label for="observaciones_entrega" class="form-label">Notas de recepción (opcional)</label><textarea id="observaciones_entrega" name="observaciones_entrega" class="form-control" maxlength="300" rows="2">{{ old('observaciones_entrega') }}</textarea></div><div class="form-check mb-3"><input id="confirmar_entrega" name="confirmar_entrega" value="1" type="checkbox" class="form-check-input" required><label for="confirmar_entrega" class="form-check-label">Confirmo que todas las piezas fueron recibidas físicamente.</label></div><button class="btn btn-primary" type="submit">Marcar como entregado</button></form>
</div></section>
@endif
<section class="card border-0 shadow-sm rounded-4 mt-4"><div class="card-body"><h2 class="h5 mb-0">Piezas del paquete</h2></div><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th scope="col">Código</th><th scope="col">Juguete</th></tr></thead><tbody>@foreach ($pedido->detalles as $detalle)<tr><td><a href="{{ route('inventario.mostrar', $detalle->producto_id) }}">{{ $detalle->codigo }}</a></td><td>{{ $detalle->nombre }}</td></tr>@endforeach</tbody></table></div></section>
<section class="card border-0 shadow-sm rounded-4 mt-4"><div class="card-body p-4"><h2 class="h5">Historial del pedido</h2>@foreach ($eventos as $evento)<div class="border-top py-3"><div class="small text-secondary">{{ $evento->created_at->format('d/m/Y H:i') }} · {{ $evento->usuario->name }}</div><div class="text-break">{{ $evento->descripcion }}</div></div>@endforeach @if ($eventos->hasPages()){{ $eventos->links('pagination::bootstrap-5') }}@endif</div></section>
@endsection
