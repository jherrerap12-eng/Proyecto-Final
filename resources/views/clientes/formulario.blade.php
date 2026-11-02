@extends('plantillas.principal')
@section('titulo', $cliente->exists ? 'Editar cliente' : 'Registrar cliente')
@section('contenido')
<a href="{{ route('clientes.index') }}" class="d-inline-block mb-3">Volver a clientes</a>
<h1 class="fw-bold mb-4">{{ $cliente->exists ? 'Editar cliente' : 'Registrar cliente' }}</h1>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <p class="text-secondary">Nombre y teléfono son obligatorios.</p>
    @if ($errors->any())<div class="alert alert-danger" role="alert">Revisa los campos señalados antes de guardar.</div>@endif
    <form method="POST" action="{{ $cliente->exists ? route('clientes.update', $cliente) : route('clientes.store') }}">
        @csrf
        @if ($cliente->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="nombre">Nombre completo</label>
                <input id="nombre" name="nombre" autocomplete="name" required maxlength="150" value="{{ old('nombre', $cliente->nombre) }}" class="form-control @error('nombre') is-invalid @enderror" @error('nombre') aria-invalid="true" aria-describedby="error-nombre" @enderror>
                @error('nombre')<div id="error-nombre" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="telefono">Teléfono / WhatsApp</label>
                <input id="telefono" name="telefono" type="tel" autocomplete="tel" required maxlength="25" value="{{ old('telefono', $cliente->telefono) }}" class="form-control @error('telefono') is-invalid @enderror" aria-describedby="ayuda-telefono @error('telefono') error-telefono @enderror" @error('telefono') aria-invalid="true" @enderror>
                <div id="ayuda-telefono" class="form-text">Entre 8 y 15 dígitos. Puedes incluir el código de país.</div>
                @error('telefono')<div id="error-telefono" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="correo">Correo electrónico (opcional)</label>
                <input id="correo" name="correo" type="email" autocomplete="email" maxlength="254" value="{{ old('correo', $cliente->correo) }}" class="form-control @error('correo') is-invalid @enderror" @error('correo') aria-invalid="true" aria-describedby="error-correo" @enderror>
                @error('correo')<div id="error-correo" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="direccion">Dirección (opcional)</label>
                <textarea id="direccion" name="direccion" autocomplete="street-address" rows="3" maxlength="500" class="form-control @error('direccion') is-invalid @enderror" @error('direccion') aria-invalid="true" aria-describedby="error-direccion" @enderror>{{ old('direccion', $cliente->direccion) }}</textarea>
                @error('direccion')<div id="error-direccion" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">Guardar cliente</button>
            <a class="btn btn-outline-secondary" href="{{ route('clientes.index') }}">Cancelar</a>
        </div>
    </form>
</div></div>
@endsection
