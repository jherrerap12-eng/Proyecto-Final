<?php

namespace App\Http\Controllers;

use App\Acciones\Pagos\ConsultarPedidos;
use App\Acciones\Pagos\RegistrarPago;
use App\Http\Requests\SolicitudPago;
use App\Models\Pago;
use App\Soporte\Importe;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ControladorPagos extends Controller
{
    public function index(Request $request, ConsultarPedidos $fuente)
    {
        $datos = $request->validate(['buscar' => ['nullable', 'string', 'max:150']]);
        $buscar = trim($datos['buscar'] ?? '');
        $disponible = $fuente->disponible();
        $pedidos = null;
        if ($disponible) {
            $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar).'%';
            $pedidos = $fuente->consulta()->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereRaw("clientes.nombre LIKE ? ESCAPE '!'", [$patron])->orWhere('pedidos.id', $buscar)))
                ->orderByDesc('pedidos.id')->paginate(10)->withQueryString();
            $pagos = Pago::whereIn('pedido_id', $pedidos->pluck('id'))->get()->groupBy('pedido_id');
            foreach ($pedidos as $pedido) {
                $pedido->abonado = ($pagos->get($pedido->id) ?? collect())->sum(fn ($p) => Importe::centavos($p->importe));
                $pedido->saldo = Importe::centavos((string) $pedido->total) - $pedido->abonado;
            }
        }

        return view('pagos.indice', compact('disponible', 'pedidos', 'buscar'));
    }

    public function show(int $pedido, ConsultarPedidos $fuente)
    {
        abort_unless($fuente->disponible(), 409, 'El módulo de pedidos aún no está integrado.');
        $pedido = $fuente->consulta()->where('pedidos.id', $pedido)->first();
        abort_unless($pedido, 404);
        $pagos = Pago::with('usuario')->where('pedido_id', $pedido->id)->orderByDesc('fecha')->orderByDesc('id')->get();
        $abonado = $pagos->sum(fn ($p) => Importe::centavos($p->importe));
        $saldo = Importe::centavos((string) $pedido->total) - $abonado;
        $clave = (string) Str::uuid();

        return view('pagos.detalle', compact('pedido', 'pagos', 'abonado', 'saldo', 'clave'));
    }

    public function store(SolicitudPago $request, int $pedido, RegistrarPago $accion)
    {
        $accion->registrar($pedido, $request->validated(), $request->user());

        return redirect()->route('pagos.show', $pedido)->with('estado', 'Abono registrado correctamente.');
    }
}
