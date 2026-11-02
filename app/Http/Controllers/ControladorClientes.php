<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudCliente;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControladorClientes extends Controller
{
    public function index(Request $request): View
    {
        $datos = $request->validate(['buscar' => ['nullable', 'string', 'max:150']]);
        $buscar = trim($datos['buscar'] ?? '');
        $clientes = Cliente::query()
            ->when($buscar !== '', function ($consulta) use ($buscar) {
                // ! escapa los comodines para buscar el texto escrito literalmente.
                $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar).'%';
                $consulta->where(function ($consulta) use ($patron) {
                    $consulta->whereRaw("nombre LIKE ? ESCAPE '!'", [$patron])
                        ->orWhereRaw("telefono LIKE ? ESCAPE '!'", [$patron])
                        ->orWhereRaw("correo LIKE ? ESCAPE '!'", [$patron]);
                });
            })
            ->orderBy('nombre')->orderBy('id')->paginate(10)->withQueryString();

        return view('clientes.indice', compact('clientes', 'buscar'));
    }

    public function create(): View
    {
        return view('clientes.formulario', ['cliente' => new Cliente]);
    }

    public function store(SolicitudCliente $request): RedirectResponse
    {
        $cliente = Cliente::create($request->validated());

        return redirect()->route('clientes.show', $cliente)->with('estado', 'Cliente registrado correctamente.');
    }

    public function show(Cliente $cliente): View
    {
        return view('clientes.detalle', compact('cliente'));
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.formulario', compact('cliente'));
    }

    public function update(SolicitudCliente $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.show', $cliente)->with('estado', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->delete();

        return redirect()->route('clientes.index')->with('estado', 'Cliente dado de baja. Su registro se conserva.');
    }
}
