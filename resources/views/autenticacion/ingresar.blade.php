@extends('plantillas.principal')
@section('titulo', 'Iniciar sesión')
@section('contenido')
<div class="row align-items-center g-5 py-lg-5">
    <div class="col-lg-6">
        <span class="insignia-marca mb-3">BIENVENIDO A BABYBU</span>
        <h1 class="display-5 fw-bold">Un lugar para organizar cada nueva sonrisa.</h1>
        <p class="lead text-secondary mt-3">Accede al espacio de trabajo de Babybu Toys.</p>
    </div>
    <div class="col-lg-5 offset-lg-1">
        <section class="card border-0 shadow-sm rounded-4 p-2 p-sm-4" aria-labelledby="titulo-ingreso">
            <div class="card-body">
                <h2 id="titulo-ingreso" class="h3 fw-bold">Iniciar sesión</h2>
                <p class="text-secondary mb-4">Usa tu cuenta del negocio para continuar.</p>
                <form action="{{ route('sesion.ingresar') }}" method="POST" data-envio-unico>
                    @csrf
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input id="correo" name="email" type="email" autocomplete="username" required maxlength="255"
                            class="form-control form-control-lg @error('email') is-invalid @enderror"
                            value="{{ old('email') }}" @error('email') aria-invalid="true" aria-describedby="error-correo" @enderror>
                        @error('email') <div id="error-correo" class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input id="contrasena" name="password" type="password" autocomplete="current-password" required maxlength="255"
                            class="form-control form-control-lg @error('password') is-invalid @enderror"
                            @error('password') aria-invalid="true" aria-describedby="error-contrasena" @enderror>
                        @error('password') <div id="error-contrasena" class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Ingresar</button>
                </form>
                <p class="small text-secondary mt-4 mb-0">Acceso exclusivo para usuarios autorizados de Babybu Toys.</p>
            </div>
        </section>
    </div>
</div>
@endsection
