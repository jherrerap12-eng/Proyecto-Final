@extends('plantillas.principal')
@section('titulo', 'Mi contraseña')
@section('contenido')
<div class="row justify-content-center"><div class="col-lg-6">
<h1 class="fw-bold mb-2">Cambiar mi contraseña</h1>
<p class="text-secondary mb-4">Por seguridad se pide la contraseña actual. Al guardar se cerrarán tus sesiones en otros dispositivos.</p>
@include('inventario.errores')
<section class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
    <form method="POST" action="{{ route('cuenta.contrasena.actualizar') }}" data-envio-unico>
        @csrf @method('PUT')
        <div class="mb-3"><label for="contrasena_actual" class="form-label">Contraseña actual</label><input id="contrasena_actual" name="contrasena_actual" type="password" class="form-control" required autocomplete="current-password"></div>
        <div class="mb-3"><label for="password" class="form-label">Contraseña nueva</label><input id="password" name="password" type="password" class="form-control" required minlength="12" autocomplete="new-password" aria-describedby="ayuda-nueva"><div id="ayuda-nueva" class="form-text">Mínimo 12 caracteres con letras y números, distinta de la actual.</div></div>
        <div class="mb-3"><label for="password_confirmation" class="form-label">Confirmar contraseña nueva</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required minlength="12" autocomplete="new-password"></div>
        <button type="submit" class="btn btn-primary">Guardar contraseña</button>
    </form>
</div></section>
</div></div>
@endsection
