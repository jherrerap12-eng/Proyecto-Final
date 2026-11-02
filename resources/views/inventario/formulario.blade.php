@extends('plantillas.principal')
@section('titulo', $producto->exists ? 'Editar juguete' : 'Registrar juguete')
@section('contenido')
<a href="{{ $producto->exists ? route('inventario.mostrar', $producto) : route('inventario.listar') }}" class="text-decoration-none">← Volver</a>
<h1 class="fw-bold mt-3">{{ $producto->exists ? 'Editar juguete' : 'Registrar juguete' }}</h1>
<p class="text-secondary">Cada pieza lleva un código manual único, incluso si tienes juguetes iguales.</p>
@include('inventario.errores')
<form method="POST" action="{{ $producto->exists ? route('inventario.actualizar', $producto) : route('inventario.guardar') }}" class="card border-0 shadow-sm rounded-4" data-envio-unico>
    @csrf
    @if ($producto->exists)
        @method('PUT')
        {{-- Conserva la versión realmente vista; nunca sustituir por old() tras un conflicto. --}}
        <input type="hidden" name="version" value="{{ $producto->version }}">
    @endif
    <div class="card-body p-4 row g-4">
        <div class="col-md-4">
            <label for="codigo" class="form-label">Código de la pieza <span aria-hidden="true">*</span></label>
            <input id="codigo" name="codigo" class="form-control text-uppercase" value="{{ old('codigo', $producto->codigo) }}" required maxlength="20" pattern="[A-Za-z0-9][A-Za-z0-9_-]{0,19}" aria-describedby="ayuda-codigo">
            <div id="ayuda-codigo" class="form-text">Hasta 20 caracteres: letras, números, guion o guion bajo. Se guarda en mayúsculas.</div>
        </div>
        <div class="col-md-8">
            <label for="nombre" class="form-label">Nombre del juguete <span aria-hidden="true">*</span></label>
            <input id="nombre" name="nombre" class="form-control" value="{{ old('nombre', $producto->nombre) }}" required maxlength="150">
        </div>
        <div class="col-12">
            <label for="descripcion" class="form-label">Descripción y condición</label>
            <textarea id="descripcion" name="descripcion" class="form-control" rows="3" maxlength="2000">{{ old('descripcion', $producto->descripcion) }}</textarea>
        </div>
        <div class="col-md-4">
            <label for="costo" class="form-label">Costo de la pieza (Q) <span aria-hidden="true">*</span></label>
            <input id="costo" name="costo" type="number" class="form-control" value="{{ old('costo', $producto->costo) }}" required min="0" max="99999999.99" step="0.01">
        </div>
        <div class="col-md-4">
            <label for="precio" class="form-label">Precio de venta (Q) <span aria-hidden="true">*</span></label>
            <input id="precio" name="precio" type="number" class="form-control" value="{{ old('precio', $producto->precio) }}" required min="0" max="99999999.99" step="0.01">
        </div>
        <div class="col-md-4">
            @if (! $producto->exists)
                <label for="existencias" class="form-label">Existencia inicial <span aria-hidden="true">*</span></label>
                <select id="existencias" name="existencias" class="form-select" required>
                    <option value="1" @selected((string) old('existencias', '1') === '1')>1 — pieza en inventario</option>
                    <option value="0" @selected((string) old('existencias', '1') === '0')>0 — sin existencia</option>
                </select>
            @else
                <p class="form-label">Existencia actual</p>
                <p class="fw-bold">{{ $producto->existencias }} pieza(s)</p>
                <p class="form-text">Registra entradas o salidas desde la ficha para conservar el historial.</p>
            @endif
        </div>
        <div class="col-12 border-top pt-3 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">{{ $producto->exists ? 'Guardar cambios' : 'Registrar juguete' }}</button>
            <a href="{{ $producto->exists ? route('inventario.mostrar', $producto) : route('inventario.listar') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </div>
</form>
@endsection
