@extends('plantillas.principal')
@section('titulo', 'Usuarios')
@section('contenido')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div><span class="insignia-marca mb-2">ADMINISTRACIÓN</span><h1 class="fw-bold mb-1">Usuarios</h1><p class="text-secondary mb-0">Cuentas internas, sus roles y su estado. Las cuentas no se eliminan: se desactivan para conservar el historial.</p></div>
    <a class="btn btn-primary" href="{{ route('usuarios.create') }}">Nueva cuenta</a>
</div>
@include('inventario.errores')
<section class="card border-0 shadow-sm rounded-4 mb-4">
    <p class="small text-secondary d-md-none px-3 pt-3 mb-0">Desliza la tabla para ver todas las columnas.</p>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th scope="col">Nombre</th><th scope="col">Correo</th><th scope="col">Rol</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
        <tbody>
        @foreach ($usuarios as $usuario)
            <tr>
                <td>{{ $usuario->name }} @if ($usuario->is(auth()->user()))<span class="badge text-bg-light">Tú</span>@endif</td>
                <td class="text-break">{{ $usuario->email }}</td>
                <td>{{ $usuario->nombreRol() }}</td>
                <td>
                    @if ($usuario->activo)<span class="badge text-bg-success">Activa</span>
                    @else<span class="badge text-bg-secondary">Desactivada</span><div class="small text-secondary">{{ $usuario->desactivado_en?->format('d/m/Y H:i') }}</div>@endif
                </td>
                <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('usuarios.edit', $usuario) }}">Editar</a>
                    @unless ($usuario->is(auth()->user()))
                    <form method="POST" action="{{ route('usuarios.estado', $usuario) }}" class="d-inline" data-envio-unico>
                        @csrf @method('PATCH')
                        <input type="hidden" name="activo" value="{{ $usuario->activo ? 0 : 1 }}">
                        <button type="submit" class="btn btn-sm {{ $usuario->activo ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $usuario->activo ? 'Desactivar' : 'Reactivar' }}</button>
                    </form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @if ($usuarios->hasPages())<div class="card-body">{{ $usuarios->links('pagination::bootstrap-5') }}</div>@endif
</section>
<section class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
    <h2 class="h5">Qué puede hacer cada rol</h2>
    <p class="small text-secondary">Propuesta vigente. Todos pueden consultar, registrar clientes, pedidos, abonos pendientes y entregas.</p>
    <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th scope="col">Operación</th>@foreach (\App\Soporte\Permisos::ROLES as $etiqueta)<th scope="col" class="text-center">{{ $etiqueta }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach (['administrar-usuarios' => 'Administrar usuarios', 'verificar-pagos' => 'Verificar pagos', 'corregir-pagos' => 'Corregir o anular abonos', 'ajustar-inventario' => 'Ajustar inventario', 'cancelar-pedidos' => 'Cancelar pedidos'] as $permiso => $nombre)
            <tr><th scope="row" class="fw-normal">{{ $nombre }}</th>@foreach (array_keys(\App\Soporte\Permisos::ROLES) as $rol)<td class="text-center">{!! \App\Soporte\Permisos::permite($rol, $permiso) ? '<span aria-label="Sí">✔</span>' : '<span class="text-secondary" aria-label="No">—</span>' !!}</td>@endforeach</tr>
        @endforeach
        </tbody>
    </table></div>
</div></section>
@endsection
