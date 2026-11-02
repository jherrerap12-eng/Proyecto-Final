<?php

namespace App\Http\Controllers;

use App\Acciones\Pedidos\GestionarPedidos;
use App\Http\Requests\SolicitudPedido;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Soporte\Importes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorPedidos extends Controller
{
    public function listar(Request $solicitud): View
    {
        $filtros = $solicitud->validate(['buscar' => ['nullable', 'string', 'max:100'], 'estado' => ['nullable', Rule::in(['borrador', 'confirmado', 'cancelado'])], 'page' => ['nullable', 'integer', 'min:1']]);
        $consulta = Pedido::query();
        if (($filtros['buscar'] ?? '') !== '') {
            $busqueda = $filtros['buscar'];
            $consulta->where(function ($consulta) use ($busqueda) {
                $consulta->where('cliente_nombre', 'like', '%'.$busqueda.'%');
                if (ctype_digit($busqueda)) {
                    $consulta->orWhere('id', (int) $busqueda);
                }
            });
        }
        if ($filtros['estado'] ?? null) {
            $consulta->where('estado', $filtros['estado']);
        }
        $pedidos = $consulta->orderByDesc('id')->paginate(15)->withQueryString();

        return view('pedidos.listado', compact('pedidos', 'filtros'));
    }

    public function crear(Request $solicitud): View
    {
        return $this->formulario($solicitud, new Pedido);
    }

    public function editar(Request $solicitud, Pedido $pedido): View
    {
        abort_unless($pedido->estado === 'borrador' && ! $pedido->pagos()->exists(), 403, 'Solo se editan borradores sin pagos.');

        return $this->formulario($solicitud, $pedido);
    }

    private function formulario(Request $solicitud, Pedido $pedido): View
    {
        $pedido->load('detalles');
        $seleccionados = array_map('intval', (array) old('productos', $pedido->detalles->pluck('producto_id')->all()));
        $clienteSeleccionado = (int) old('cliente_id', $pedido->cliente_id);
        $clientes = Cliente::orderBy('nombre')->limit(20)->get()->merge(Cliente::whereKey($clienteSeleccionado)->get())->unique('id');
        $piezas = Producto::where('activo', true)->where('existencias', 1)->whereDoesntHave('reservaActual')->orderBy('codigo')->limit(20)->get()->merge(Producto::whereIn('id', $seleccionados)->get())->unique('id');
        $referencia = (string) Str::uuid();

        return view('pedidos.formulario', compact('pedido', 'clientes', 'piezas', 'seleccionados', 'clienteSeleccionado', 'referencia'));
    }

    public function guardar(SolicitudPedido $solicitud, GestionarPedidos $gestion): RedirectResponse
    {
        $pedido = $gestion->guardar($solicitud->validated(), $solicitud->user());

        return redirect()->route('pedidos.mostrar', $pedido)->with('estado', 'Borrador guardado. Las piezas todavía no están reservadas.');
    }

    public function actualizar(SolicitudPedido $solicitud, Pedido $pedido, GestionarPedidos $gestion): RedirectResponse
    {
        $gestion->guardar($solicitud->validated(), $solicitud->user(), $pedido);

        return redirect()->route('pedidos.mostrar', $pedido)->with('estado', 'Borrador actualizado con precios y costos vigentes.');
    }

    public function mostrar(Pedido $pedido): View
    {
        $pedido->load(['detalles', 'reservas']);
        $eventos = $pedido->eventos()->with('usuario')->orderByDesc('id')->paginate(15);
        $pagado = $pedido->pagadoCentavos();
        $saldo = max(0, Importes::aCentavos($pedido->total) - $pagado);

        return view('pedidos.detalle', compact('pedido', 'eventos', 'pagado', 'saldo'));
    }

    public function confirmar(Request $solicitud, Pedido $pedido, GestionarPedidos $gestion): RedirectResponse
    {
        $datos = $solicitud->validate(['version' => ['required', 'integer', 'min:1']]);
        $gestion->confirmar($pedido, (int) $datos['version'], $solicitud->user());

        return redirect()->route('pedidos.mostrar', $pedido)->with('estado', 'Pedido confirmado y piezas reservadas.');
    }

    public function cancelar(Request $solicitud, Pedido $pedido, GestionarPedidos $gestion): RedirectResponse
    {
        $datos = $solicitud->validate(['version' => ['required', 'integer', 'min:1'], 'motivo' => ['required', 'string', 'max:255']]);
        $gestion->cancelar($pedido, (int) $datos['version'], $datos['motivo'], $solicitud->user());

        return redirect()->route('pedidos.mostrar', $pedido)->with('estado', 'Pedido cancelado. Consulta si las piezas se liberaron o quedaron pendientes de devolución.');
    }

    public function devolver(Request $solicitud, Pedido $pedido, GestionarPedidos $gestion): RedirectResponse
    {
        $solicitud->validate(['confirmar_regreso' => ['accepted']]);
        $gestion->confirmarDevolucion($pedido, $solicitud->user());

        return redirect()->route('pedidos.mostrar', $pedido)->with('estado', 'Regreso físico confirmado; piezas liberadas.');
    }

    public function opciones(Request $solicitud, string $tipo): JsonResponse
    {
        $datos = $solicitud->validate(['buscar' => ['nullable', 'string', 'max:100']]);
        $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $datos['buscar'] ?? '').'%';
        if ($tipo === 'clientes') {
            $resultados = Cliente::where(function ($consulta) use ($patron) {
                $consulta->whereRaw("nombre LIKE ? ESCAPE '!'", [$patron])->orWhereRaw("telefono LIKE ? ESCAPE '!'", [$patron]);
            })->orderBy('nombre')->limit(20)->get()->map(fn ($cliente) => ['id' => $cliente->id, 'texto' => $cliente->nombre.' · '.$cliente->telefono]);
        } else {
            $resultados = Producto::where('activo', true)->where('existencias', 1)->whereDoesntHave('reservaActual')->where(function ($consulta) use ($patron) {
                $consulta->whereRaw("codigo LIKE ? ESCAPE '!'", [$patron])->orWhereRaw("nombre LIKE ? ESCAPE '!'", [$patron]);
            })->orderBy('codigo')->limit(20)->get()->map(fn ($pieza) => ['id' => $pieza->id, 'texto' => $pieza->codigo.' · '.$pieza->nombre.' · Q '.$pieza->precio, 'centavos' => Importes::aCentavos($pieza->precio)]);
        }

        return response()->json(['resultados' => $resultados]);
    }
}
