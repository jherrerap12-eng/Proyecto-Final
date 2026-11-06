<?php

namespace App\Http\Controllers;

use App\Acciones\Inventario\GestionarInventario;
use App\Http\Requests\SolicitudMovimiento;
use App\Http\Requests\SolicitudProducto;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorInventario extends Controller
{
    public function listar(Request $solicitud): View
    {
        $filtros = $solicitud->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(['activos', 'inactivos', 'todos'])],
            'existencias' => ['nullable', Rule::in(['con', 'sin', 'todas'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $consulta = Producto::with('reservaActual.pedido');
        $estado = $filtros['estado'] ?? 'activos';
        if ($estado !== 'todos') {
            $consulta->where('activo', $estado === 'activos');
        }
        if (($filtros['buscar'] ?? '') !== '') {
            $busqueda = $filtros['buscar'];
            $consulta->where(function ($consulta) use ($busqueda) {
                $consulta->where('codigo', 'like', '%'.$busqueda.'%')->orWhere('nombre', 'like', '%'.$busqueda.'%');
            });
        }
        if (($filtros['existencias'] ?? '') === 'con') {
            $consulta->where('activo', true)->where('existencias', '>', 0)->whereDoesntHave('reservaActual');
        } elseif (($filtros['existencias'] ?? '') === 'sin') {
            $consulta->where('existencias', 0);
        }
        $productos = $consulta->orderBy('codigo')->orderBy('id')->paginate(15)->withQueryString();

        return view('inventario.listado', compact('productos', 'filtros'));
    }

    public function crear(Request $solicitud): View
    {
        $datos = $solicitud->validate(['copiar_de' => ['nullable', 'integer', 'exists:productos,id']]);
        $origen = isset($datos['copiar_de']) ? Producto::findOrFail($datos['copiar_de']) : null;
        // Solo copiar datos de catálogo; código, stock, reservas e historial son nuevos.
        $producto = new Producto($origen?->only(['nombre', 'descripcion', 'costo', 'precio']) ?? []);

        return view('inventario.formulario', compact('producto', 'origen'));
    }

    public function guardar(SolicitudProducto $solicitud, GestionarInventario $inventario): RedirectResponse
    {
        $producto = $inventario->registrar($solicitud->validated(), $solicitud->user());

        return redirect()->route('inventario.mostrar', $producto)->with('estado', 'Juguete registrado correctamente.');
    }

    public function mostrar(Producto $producto): View
    {
        $producto->load('reservaActual.pedido');
        $movimientos = $producto->movimientos()->with('usuario')->orderByDesc('id')->paginate(15);
        $referencia = (string) Str::uuid();

        return view('inventario.detalle', compact('producto', 'movimientos', 'referencia'));
    }

    public function editar(Producto $producto): View
    {
        return view('inventario.formulario', compact('producto'));
    }

    public function actualizar(SolicitudProducto $solicitud, Producto $producto, GestionarInventario $inventario): RedirectResponse
    {
        $inventario->actualizar($producto, $solicitud->validated());

        return redirect()->route('inventario.mostrar', $producto)->with('estado', 'Datos del juguete actualizados.');
    }

    public function ajustar(SolicitudMovimiento $solicitud, Producto $producto, GestionarInventario $inventario): RedirectResponse
    {
        $inventario->ajustar($producto, $solicitud->validated(), $solicitud->user());

        return redirect()->route('inventario.mostrar', $producto)->with('estado', 'Movimiento procesado correctamente.');
    }

    public function cambiarEstado(Request $solicitud, Producto $producto, GestionarInventario $inventario): RedirectResponse
    {
        $datos = $solicitud->validate(['activo' => ['required', 'boolean'], 'version' => ['required', 'integer', 'min:1']]);
        $inventario->cambiarEstado($producto, (bool) $datos['activo'], (int) $datos['version']);

        return redirect()->route('inventario.mostrar', $producto)->with('estado', $datos['activo'] ? 'Producto reactivado.' : 'Producto desactivado. Su historial y existencias se conservan.');
    }
}
