<?php

namespace App\Http\Controllers;

use App\Acciones\Entregas\GestionarEntregas;
use App\Http\Requests\SolicitudEnvio;
use App\Models\Pedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorEntregas extends Controller
{
    public function listar(Request $solicitud): View
    {
        $filtros = $solicitud->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(['por_preparar', 'empacado', 'enviado', 'entregado', 'devolucion_pendiente', 'devuelto'])],
            'transportista' => ['nullable', Rule::in(['forza', 'mensajeria_propia'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $consulta = Pedido::with('envio')->where(function ($consulta) {
            $consulta->where('estado', 'confirmado')->orWhereHas('envio');
        });
        if (($filtros['buscar'] ?? '') !== '') {
            $buscar = $filtros['buscar'];
            $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar).'%';
            $consulta->where(function ($consulta) use ($patron, $buscar) {
                $consulta->whereRaw("cliente_nombre LIKE ? ESCAPE '!'", [$patron])->orWhereRaw("cliente_telefono LIKE ? ESCAPE '!'", [$patron]);
                if (ctype_digit($buscar)) {
                    $consulta->orWhere('id', (int) $buscar);
                }
            });
        }
        $estado = $filtros['estado'] ?? '';
        if ($estado === 'devolucion_pendiente') {
            $consulta->whereHas('reservas', fn ($consulta) => $consulta->where('estado', 'devolucion_pendiente')->whereNotNull('producto_bloqueado_id'));
        } elseif ($estado === 'devuelto') {
            $consulta->whereHas('envio', fn ($consulta) => $consulta->whereNotNull('devuelto_en'));
        } elseif ($estado !== '') {
            $consulta->where('estado_logistico', $estado);
        }
        if ($filtros['transportista'] ?? null) {
            $consulta->whereHas('envio', fn ($consulta) => $consulta->where('transportista', $filtros['transportista']));
        }
        $pedidos = $consulta->orderByDesc('updated_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('entregas.listado', compact('pedidos', 'filtros'));
    }

    public function mostrar(Pedido $pedido): View
    {
        $pedido->load(['envio.empacador', 'envio.remitente', 'envio.confirmador', 'envio.receptorDevolucion', 'detalles', 'reservas']);
        $envio = $pedido->envio;
        $eventos = $pedido->eventos()->with('usuario')->orderByDesc('id')->paginate(15);

        return view('entregas.detalle', compact('pedido', 'envio', 'eventos'));
    }

    public function empacar(Request $solicitud, Pedido $pedido, GestionarEntregas $gestion): RedirectResponse
    {
        $datos = $solicitud->validate(['version' => ['required', 'integer', 'min:1']]);
        $gestion->empacar($pedido, (int) $datos['version'], $solicitud->user());

        return redirect()->route('entregas.mostrar', $pedido)->with('estado', 'Empaque registrado; las piezas siguen en el negocio.');
    }

    public function enviar(SolicitudEnvio $solicitud, Pedido $pedido, GestionarEntregas $gestion): RedirectResponse
    {
        $gestion->enviar($pedido, $solicitud->validated(), $solicitud->user());

        return redirect()->route('entregas.mostrar', $pedido)->with('estado', 'Envío registrado con su salida física de inventario.');
    }

    public function entregar(Request $solicitud, Pedido $pedido, GestionarEntregas $gestion): RedirectResponse
    {
        $datos = $solicitud->validate(['version' => ['required', 'integer', 'min:1'], 'confirmar_entrega' => ['accepted'], 'observaciones_entrega' => ['nullable', 'string', 'max:300']], ['confirmar_entrega.accepted' => 'Confirma que el pedido fue recibido físicamente.']);
        $gestion->entregar($pedido, (int) $datos['version'], $datos['observaciones_entrega'] ?? null, $solicitud->user());

        return redirect()->route('entregas.mostrar', $pedido)->with('estado', 'Entrega física confirmada. Los pagos conservan su estado independiente.');
    }
}
