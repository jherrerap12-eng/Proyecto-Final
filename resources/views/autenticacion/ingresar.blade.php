@extends('plantillas.principal')
@section('titulo', 'Iniciar sesión')
@section('contenido')
<div class="toy-signin-layout">
    <section class="toy-signin-art" aria-labelledby="toy-signin-title">
        <span class="toy-signin-label"><svg class="ui-icon"><use href="#ui-gift" /></svg> BABYBU TOYS</span>
        <h1 id="toy-signin-title">Cada juguete tiene una historia. <span>Hagamos que llegue a casa.</span></h1>
        <p>Pedidos, clientes y juguetes, conectados para hacer más fácil cada jornada.</p>
        <div class="toy-signin-blocks" aria-hidden="true"><span class="toy-cube cube-one">B</span><span class="toy-cube cube-two">A</span><span class="toy-cube cube-three">B</span><span class="toy-signin-ring"></span><span class="toy-signin-ball"></span></div>
        <div class="toy-signin-foot">OPERACIÓN DE LA JUGUETERÍA <span aria-hidden="true">✳</span> UN SOLO LUGAR</div>
    </section>
    <section class="toy-signin-form-wrap" aria-labelledby="titulo-ingreso">
        <div class="toy-signin-form">
            <span class="toy-signin-form-icon"><svg class="ui-icon"><use href="#ui-key" /></svg></span>
            <p class="toy-signin-overline">ACCESO DEL EQUIPO</p>
            <h2 id="titulo-ingreso">Bienvenido de vuelta</h2>
            <p class="toy-signin-help">Ingresa para continuar con la administración de Babybu.</p>
            <form action="{{ route('sesion.ingresar') }}" method="POST" data-envio-unico>
                @csrf
                <div class="mb-4"><label class="form-label" for="correo">Correo electrónico</label><input id="correo" name="email" type="email" autocomplete="username" required maxlength="255" placeholder="tu-correo@ejemplo.com" class="form-control form-control-lg @error('email') is-invalid @enderror" value="{{ old('email') }}" @error('email') aria-invalid="true" aria-describedby="error-correo" @enderror>@error('email') <div id="error-correo" class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="mb-4"><label class="form-label" for="contrasena">Contraseña</label><div class="password-field"><input id="contrasena" name="password" type="password" autocomplete="current-password" required maxlength="255" placeholder="Tu contraseña" class="form-control form-control-lg @error('password') is-invalid @enderror" @error('password') aria-invalid="true" aria-describedby="error-contrasena" @enderror><button type="button" class="password-toggle" data-toggle-password="contrasena" aria-label="Mostrar contraseña" aria-pressed="false"><svg class="ui-icon"><use href="#ui-eye" /></svg></button></div>@error('password') <div id="error-contrasena" class="invalid-feedback d-block">{{ $message }}</div> @enderror</div>
                <button type="submit" class="btn btn-primary btn-lg w-100 toy-signin-submit">Entrar a mi tienda <svg class="ui-icon"><use href="#ui-arrow" /></svg></button>
            </form>
            <p class="toy-signin-secure"><svg class="ui-icon"><use href="#ui-shield" /></svg> Área exclusiva para el equipo autorizado de Babybu Toys.</p>
        </div>
    </section>
</div>
@endsection
