@extends('plantillas.principal')
@section('titulo', $producto->codigo)
@section('contenido')
<a href="{{ route('inventario.listar') }}" class="text-decoration-none">← Volver al inventario</a>
<div class="d-flex flex-wrap justify-content-between gap-3 mt-3 mb-4">
    <div><span class="insignia-marca mb-2">{{ $producto->codigo }}</span><h1 class="fw-bold">{{ $producto->nombre }}</h1></div>
    <div><a class="btn btn-outline-primary" href="{{ route('inventario.editar', $producto) }}">Editar datos</a></div>
</div>
@include('inventario.errores')
@if (! $producto->activo)<div class="alert alert-secondary">Esta pieza está inactiva. Su historial se conserva y no admite movimientos hasta reactivarla.</div>@endif
<div class="row g-4">
    <div class="col-lg-7">
        <section class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h2 class="h5">Datos de la pieza</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Costo</dt><dd class="col-sm-7">Q {{ number_format((float) $producto->costo, 2) }}</dd>
                    <dt class="col-sm-5">Precio de venta</dt><dd class="col-sm-7">Q {{ number_format((float) $producto->precio, 2) }}</dd>
                    <dt class="col-sm-5">Existencia</dt><dd class="col-sm-7">{{ $producto->existencias ? '1 pieza en inventario' : 'Sin existencia' }}</dd>
                    <dt class="col-sm-5">Estado</dt><dd class="col-sm-7">{{ $producto->activo ? 'Activo' : 'Inactivo' }}</dd>
                    <dt class="col-sm-5">Descripción</dt><dd class="col-sm-7 text-break">{{ $producto->descripcion ?: 'Sin descripción.' }}</dd>
                </dl>
            </div>
        </section>
        <section class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h2 class="h5">{{ $producto->activo ? 'Desactivar pieza' : 'Reactivar pieza' }}</h2>
                <p class="text-secondary small">Desactivar conserva código, existencias e historial. Puedes encontrar la pieza usando el filtro de inactivos.</p>
                <form method="POST" action="{{ route('inventario.estado', $producto) }}" data-envio-unico>
                    @csrf @method('PATCH')
                    <input type="hidden" name="activo" value="{{ $producto->activo ? 0 : 1 }}">
                    <input type="hidden" name="version" value="{{ $producto->version }}">
                    <button type="submit" class="btn btn-outline-secondary">{{ $producto->activo ? 'Desactivar pieza' : 'Reactivar pieza' }}</button>
                </form>
            </div>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h2 class="h5">Registrar movimiento</h2>
                <p class="text-secondary small">Entradas y salidas manuales de esta pieza. Los pedidos y reservas se incorporarán en su módulo.</p>
                @if ($producto->activo)
                <form method="POST" action="{{ route('inventario.ajustar', $producto) }}" data-envio-unico>
                    @csrf
                    <input type="hidden" name="referencia" value="{{ old('referencia', $referencia) }}">
                    <input type="hidden" name="cantidad" value="1">
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Operación</label>
                        <select id="tipo" name="tipo" class="form-select" required>
                            <option value="entrada" @selected(old('tipo', $producto->existencias ? 'salida' : 'entrada') === 'entrada')>Entrada de la pieza (+1)</option>
                            <option value="salida" @selected(old('tipo', $producto->existencias ? 'salida' : 'entrada') === 'salida')>Salida de la pieza (−1)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo</label>
                        <textarea id="motivo" name="motivo" class="form-control" required maxlength="255" rows="3" placeholder="Describe por qué ingresa o sale la pieza">{{ old('motivo') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Registrar movimiento</button>
                </form>
                @else
                    <p class="text-secondary mb-0">Reactiva la pieza para registrar movimientos.</p>
                @endif
            </div>
        </section>
    </div>
</div>
<section class="card border-0 shadow-sm rounded-4 mt-4" aria-labelledby="titulo-historial">
    <div class="card-body"><h2 id="titulo-historial" class="h5 mb-0">Historial de movimientos</h2></div>
    <p class="small text-secondary d-md-none px-3 mb-2">Desliza la tabla para ver todas las columnas.</p>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th scope="col">Fecha</th><th scope="col">Tipo</th><th scope="col">Cambio</th><th scope="col">Antes → después</th><th scope="col">Motivo</th><th scope="col">Responsable</th></tr></thead>
            <tbody>
            @forelse ($movimientos as $movimiento)
                <tr><td class="text-nowrap">{{ $movimiento->created_at->format('d/m/Y H:i') }}</td><td>{{ ucfirst($movimiento->tipo) }}</td><td>{{ $movimiento->cantidad > 0 ? '+' : '' }}{{ $movimiento->cantidad }}</td><td>{{ $movimiento->existencias_anteriores }} → {{ $movimiento->existencias_posteriores }}</td><td class="text-break">{{ $movimiento->motivo }}</td><td>{{ $movimiento->usuario->name }}</td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-secondary py-4">Esta pieza todavía no tiene movimientos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($movimientos->hasPages())<div class="card-body">{{ $movimientos->links('pagination::bootstrap-5') }}</div>@endif
</section>
@endsection
