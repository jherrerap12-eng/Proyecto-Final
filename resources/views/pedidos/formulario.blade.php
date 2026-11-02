@extends('plantillas.principal')
@section('titulo', $pedido->exists ? 'Editar borrador' : 'Crear pedido')
@section('contenido')
<a href="{{ $pedido->exists ? route('pedidos.mostrar', $pedido) : route('pedidos.listar') }}">← Volver</a>
<h1 class="fw-bold mt-3">{{ $pedido->exists ? 'Editar borrador' : 'Crear pedido' }}</h1>
<p class="text-secondary">Guardar el borrador no aparta piezas. La disponibilidad se comprueba al confirmar.</p>
@include('inventario.errores')
<form method="POST" action="{{ $pedido->exists ? route('pedidos.actualizar', $pedido) : route('pedidos.guardar') }}" class="card border-0 shadow-sm rounded-4" data-envio-unico data-formulario-pedido>
    @csrf
    @if ($pedido->exists) @method('PUT') <input type="hidden" name="version" value="{{ $pedido->version }}">
    @else <input type="hidden" name="referencia" value="{{ old('referencia', $referencia) }}"> @endif
    <div class="card-body p-4 row g-4">
        <div class="col-lg-6">
            <label for="buscar-cliente" class="form-label">Buscar cliente por nombre o teléfono</label>
            <div class="input-group mb-3"><input id="buscar-cliente" type="search" maxlength="100" class="form-control" data-buscar-cliente><button type="button" class="btn btn-outline-primary" data-boton-clientes data-url="{{ route('pedidos.opciones', 'clientes') }}">Buscar</button></div>
            <label for="cliente_id" class="form-label">Cliente *</label>
            <select id="cliente_id" name="cliente_id" class="form-select" required data-clientes><option value="">Selecciona un cliente</option>@foreach ($clientes as $cliente)<option value="{{ $cliente->id }}" @selected($clienteSeleccionado === $cliente->id)>{{ $cliente->nombre }} · {{ $cliente->telefono }}</option>@endforeach</select>
        </div>
        <div class="col-lg-6">
            <label for="modalidad" class="form-label">Modalidad de pago *</label>
            <select id="modalidad" name="modalidad" class="form-select" required><option value="contra_entrega" @selected(old('modalidad', $pedido->modalidad) === 'contra_entrega')>Contra entrega — reserva temporal</option><option value="deposito" @selected(old('modalidad', $pedido->modalidad) === 'deposito')>Depósito previo — mínimo 25 % verificado</option></select>
            <p class="form-text">El depósito debe estar registrado y verificado para confirmar una reserva firme.</p>
        </div>
        <div class="col-12">
            <label for="buscar-pieza" class="form-label">Buscar piezas por código o nombre</label>
            <div class="input-group mb-3"><input id="buscar-pieza" type="search" maxlength="100" class="form-control" data-buscar-pieza><button type="button" class="btn btn-outline-primary" data-boton-piezas data-url="{{ route('pedidos.opciones', 'piezas') }}">Buscar</button></div>
            <fieldset><legend class="h6">Piezas del pedido * <span class="fw-normal text-secondary">(máximo 50)</span></legend>
                <p class="small text-secondary">Cada código es una pieza distinta. La búsqueda muestra hasta 20 resultados y conserva tu selección.</p>
                <div class="row g-2" data-piezas>
                @foreach ($piezas as $pieza)
                    <div class="col-md-6"><label class="d-flex gap-2 border rounded p-3 h-100"><input type="checkbox" name="productos[]" value="{{ $pieza->id }}" class="form-check-input flex-shrink-0" data-centavos="{{ \App\Soporte\Importes::aCentavos($pieza->precio) }}" data-texto="{{ $pieza->codigo }} · {{ $pieza->nombre }} · Q {{ $pieza->precio }}" @checked(in_array($pieza->id, $seleccionados, true))><span class="text-break">{{ $pieza->codigo }} · {{ $pieza->nombre }} · Q {{ $pieza->precio }}</span></label></div>
                @endforeach
                </div>
                @if ($piezas->isEmpty())<p class="text-secondary">No hay piezas disponibles. Regístralas en inventario o prueba otra búsqueda.</p>@endif
            </fieldset>
        </div>
        <div class="col-12"><p class="small text-secondary" role="status" aria-live="polite" data-estado-busqueda></p></div>
        <div class="col-12"><label for="observaciones" class="form-label">Observaciones</label><textarea id="observaciones" name="observaciones" class="form-control" maxlength="2000" rows="3">{{ old('observaciones', $pedido->observaciones) }}</textarea></div>
        <div class="col-12 border-top pt-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><strong data-total-pedido aria-live="polite">Selecciona las piezas</strong><div class="small text-secondary">El total definitivo se calcula al guardar.</div></div>
            <button type="submit" class="btn btn-primary">Guardar borrador</button>
        </div>
    </div>
</form>
@endsection
