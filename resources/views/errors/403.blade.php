@extends('plantillas.principal')
@section('titulo', 'Acceso denegado')
@section('contenido')
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-lg-5">
    <h1 class="fw-bold">Acceso denegado</h1>
    <p class="text-secondary">Tu rol no tiene permiso para realizar esta operación. Si crees que deberías tenerlo, consulta con el administrador.</p>
    <a class="btn btn-primary" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('panel') }}">Volver</a>
</div></div>
@endsection
