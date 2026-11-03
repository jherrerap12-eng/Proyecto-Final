@extends('plantillas.principal')
@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nueva cuenta')
@section('contenido')
<a href="{{ route('usuarios.index') }}" class="d-inline-block mb-3">← Volver a usuarios</a>
<h1 class="fw-bold mb-4">{{ $usuario->exists ? 'Editar a '.$usuario->name : 'Nueva cuenta' }}</h1>
@include('inventario.errores')
<div class="row g-4">
<section class="col-lg-7"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
    <h2 class="h5">Datos de la cuenta</h2>
    <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" data-envio-unico>
        @csrf
        @if ($usuario->exists) @method('PUT') @endif
        <div class="mb-3"><label for="name" class="form-label">Nombre</label><input id="name" name="name" class="form-control" required maxlength="255" value="{{ old('name', $usuario->name) }}" autocomplete="name"></div>
        <div class="mb-3"><label for="email" class="form-label">Correo</label><input id="email" name="email" type="email" class="form-control" required maxlength="255" value="{{ old('email', $usuario->email) }}" autocomplete="email"></div>
        <div class="mb-3"><label for="rol" class="form-label">Rol</label>
            <select id="rol" name="rol" class="form-select" required>
                @foreach ($roles as $valor => $etiqueta)<option value="{{ $valor }}" @selected(old('rol', $usuario->rol ?? 'operador') === $valor)>{{ $etiqueta }}</option>@endforeach
            </select>
        </div>
        @unless ($usuario->exists)
        <div class="mb-3"><label for="password" class="form-label">Contraseña inicial</label><input id="password" name="password" type="password" class="form-control" required minlength="12" autocomplete="new-password" aria-describedby="ayuda-clave"><div id="ayuda-clave" class="form-text">Mínimo 12 caracteres con letras y números. Entrégala en persona y pide que la cambie al ingresar.</div></div>
        <div class="mb-3"><label for="password_confirmation" class="form-label">Confirmar contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required minlength="12" autocomplete="new-password"></div>
        @endunless
        <button type="submit" class="btn btn-primary">{{ $usuario->exists ? 'Guardar cambios' : 'Crear cuenta' }}</button>
    </form>
</div></div></section>
@if ($usuario->exists && ! $usuario->is(auth()->user()))
<section class="col-lg-5"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
    <h2 class="h5">Restablecer contraseña</h2>
    <p class="small text-secondary">Úsalo si la persona olvidó su contraseña. Se cerrarán sus sesiones abiertas.</p>
    <form method="POST" action="{{ route('usuarios.contrasena', $usuario) }}" data-envio-unico>
        @csrf @method('PUT')
        <div class="mb-3"><label for="nueva" class="form-label">Contraseña nueva</label><input id="nueva" name="password" type="password" class="form-control" required minlength="12" autocomplete="new-password"></div>
        <div class="mb-3"><label for="nueva_confirmacion" class="form-label">Confirmar contraseña nueva</label><input id="nueva_confirmacion" name="password_confirmation" type="password" class="form-control" required minlength="12" autocomplete="new-password"></div>
        <div class="mb-3"><label for="contrasena_responsable" class="form-label">Tu contraseña de administrador</label><input id="contrasena_responsable" name="contrasena_responsable" type="password" class="form-control" required autocomplete="current-password"></div>
        <button type="submit" class="btn btn-outline-primary">Restablecer contraseña</button>
    </form>
</div></div></section>
@endif
</div>
@endsection
