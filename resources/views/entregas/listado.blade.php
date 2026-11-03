@extends('plantillas.principal')
@section('titulo', 'Entregas')
@section('contenido')
<span class="insignia-marca mb-3">ENTREGAS</span>
<h1 class="fw-bold">De Babybu a sus manos.</h1>
<p class="text-secondary">Prepara pedidos, registra envíos y consulta su recorrido.</p>
@include('inventario.errores')
<form method="GET" action="{{ route('entregas.listar') }}" class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body row g-3 align-items-end">
        <div class="col-lg-4"><label class="form-label" for="buscar">Pedido, cliente o teléfono</label><input class="form-control" id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" maxlength="100" type="search"></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label" for="estado">Estado de entrega</label><select class="form-select" id="estado" name="estado"><option value="">Todos</option>@foreach (['por_preparar' => 'Por preparar', 'empacado' => 'Empacado', 'enviado' => 'Enviado', 'entregado' => 'Entregado', 'devolucion_pendiente' => 'Regreso pendiente', 'devuelto' => 'Devuelto'] as $valor => $etiqueta)<option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $etiqueta }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label" for="transportista">Transportista</label><select class="form-select" id="transportista" name="transportista"><option value="">Todos</option><option value="forza" @selected(($filtros['transportista'] ?? '') === 'forza')>Forza</option><option value="mensajeria_propia" @selected(($filtros['transportista'] ?? '') === 'mensajeria_propia')>Mensajería propia</option></select></div>
        <div class="col-lg-2 d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit">Filtrar</button><a class="btn btn-outline-secondary" href="{{ route('entregas.listar') }}">Limpiar</a></div>
    </div>
</form>
<section class="card border-0 shadow-sm rounded-4">
    <div class="card-body"><strong>{{ $pedidos->total() }}</strong> pedidos encontrados</div>
    <p class="d-md-none small text-secondary px-3">Desliza la tabla para ver todos los datos.</p>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th scope="col">Pedido / cliente</th><th scope="col">Estado</th><th scope="col">Transporte / guía</th><th scope="col">Envío</th><th scope="col">Entrega</th><th scope="col">Acción</th></tr></thead><tbody>
    @forelse ($pedidos as $pedido)
        <tr><td><a href="{{ route('pedidos.mostrar', $pedido) }}">#{{ $pedido->id }}</a><div>{{ $pedido->cliente_nombre }}</div><div class="small text-secondary">{{ $pedido->cliente_telefono }}</div></td>
            <td><span class="badge {{ $pedido->estado_logistico === 'entregado' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst(str_replace('_', ' ', $pedido->estado_logistico)) }}</span>@if ($pedido->estado === 'cancelado')<div><span class="badge text-bg-warning">Cancelado</span></div>@endif @if ($pedido->envio?->devuelto_en)<div class="small">Regreso confirmado</div>@endif</td>
            <td>{{ ['forza' => 'Forza', 'mensajeria_propia' => 'Mensajería propia'][$pedido->envio?->transportista ?? ''] ?? 'Por definir' }}<div class="small text-break">{{ $pedido->envio?->guia ?: 'Sin guía' }}</div></td>
            <td class="text-nowrap">{{ $pedido->envio?->enviado_en?->format('d/m/Y H:i') ?? 'Pendiente' }}</td><td class="text-nowrap">{{ $pedido->envio?->entregado_en?->format('d/m/Y H:i') ?? 'Pendiente' }}</td>
            <td><a class="btn btn-outline-primary btn-sm text-nowrap" href="{{ route('entregas.mostrar', $pedido) }}">Ver entrega</a></td></tr>
    @empty
        <tr><td colspan="6" class="text-center text-secondary py-5">No hay pedidos para estos filtros. Los borradores aparecerán cuando se confirmen.</td></tr>
    @endforelse
    </tbody></table></div>
    @if ($pedidos->hasPages())<div class="card-body">{{ $pedidos->links('pagination::bootstrap-5') }}</div>@endif
</section>
@endsection
