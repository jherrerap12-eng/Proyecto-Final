@extends('plantillas.principal')
@section('titulo', 'Clientes')
@section('contenido')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="fw-bold">Clientes</h1><p class="text-secondary mb-0">Administra los compradores y sus datos de contacto.</p></div>
    <a class="btn btn-primary" href="{{ route('clientes.create') }}">Registrar cliente</a>
</div>
<form action="{{ route('clientes.index') }}" method="GET" class="card card-body border-0 shadow-sm mb-4">
    <label for="buscar" class="form-label">Buscar por nombre, teléfono o correo</label>
    <div class="d-flex flex-wrap gap-2">
        <input id="buscar" name="buscar" type="search" maxlength="150" value="{{ $buscar }}" class="form-control w-auto flex-grow-1">
        <button class="btn btn-primary" type="submit">Buscar</button>
        @if ($buscar !== '')<a class="btn btn-outline-secondary" href="{{ route('clientes.index') }}">Limpiar</a>@endif
    </div>
    @error('buscar')<p class="text-danger mt-2 mb-0" role="alert">{{ $message }}</p>@enderror
</form>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <caption class="px-3">{{ $clientes->total() }} cliente(s) encontrado(s)</caption>
            <thead><tr><th scope="col">Nombre</th><th scope="col">Teléfono</th><th scope="col">Correo</th><th scope="col">Acciones</th></tr></thead>
            <tbody>
                @forelse ($clientes as $cliente)
                    <tr>
                        <th scope="row">{{ $cliente->nombre }}</th>
                        <td>{{ $cliente->telefono }}</td>
                        <td class="text-break">{{ $cliente->correo ?? 'Sin correo' }}</td>
                        <td><div class="d-flex gap-2">
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('clientes.show', $cliente) }}" aria-label="Ver a {{ $cliente->nombre }}">Ver</a>
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('clientes.edit', $cliente) }}" aria-label="Editar a {{ $cliente->nombre }}">Editar</a>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-secondary p-4">{{ $buscar !== '' ? 'No hay clientes que coincidan con la búsqueda.' : 'Aún no hay clientes registrados.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $clientes->links('pagination::bootstrap-5') }}</div>
@endsection
