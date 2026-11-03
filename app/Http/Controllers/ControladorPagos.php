<?php

namespace App\Http\Controllers;

use App\Acciones\Pagos\ConsultarPedidos;
use App\Acciones\Pagos\CorregirPago;
use App\Acciones\Pagos\RegistrarPago;
use App\Http\Requests\SolicitudCorreccionPago;
use App\Http\Requests\SolicitudPago;
use App\Models\Pago;
use App\Models\Pedido;
use App\Soporte\Importes;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ControladorPagos extends Controller
{
    public function index(Request $solicitud, ConsultarPedidos $fuente)
    {
        $datos = $solicitud->validate(['buscar' => ['nullable', 'string', 'max:150']]);
        $buscar = trim($datos['buscar'] ?? '');
        $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar).'%';
        $pedidos = $fuente->consulta()->when($buscar !== '', function ($consulta) use ($patron, $buscar) {
            $consulta->where(function ($consulta) use ($patron, $buscar) {
                $consulta->whereRaw("cliente_nombre LIKE ? ESCAPE '!'", [$patron]);
                if (ctype_digit($buscar)) {
                    $consulta->orWhere('id', (int) $buscar);
                }
            });
        })->orderByDesc('id')->paginate(10)->withQueryString();
        $resumenes = $pedidos->getCollection()->mapWithKeys(fn (Pedido $pedido) => [$pedido->id => $fuente->resumen($pedido)]);

        return view('pagos.indice', compact('pedidos', 'buscar', 'resumenes'));
    }

    public function show(int $pedido, ConsultarPedidos $fuente)
    {
        $pedido = Pedido::findOrFail($pedido);
        $resumen = $fuente->resumen($pedido);
        $pagos = $pedido->pagos()->with(['usuario', 'verificador', 'anulador', 'correccion'])->orderByDesc('fecha_pago')->orderByDesc('id')->paginate(15);
        $clave = (string) Str::uuid();

        return view('pagos.detalle', compact('pedido', 'pagos', 'resumen', 'clave'));
    }

    public function store(SolicitudPago $solicitud, int $pedido, RegistrarPago $accion)
    {
        $accion->registrar($pedido, $solicitud->validated(), $solicitud->user());

        return redirect()->route('pagos.show', $pedido)->with('estado', 'Abono registrado, pendiente de verificación.');
    }

    public function verificar(Request $solicitud, Pago $pago, RegistrarPago $accion)
    {
        $solicitud->validate(['confirmar_verificacion' => ['accepted']], ['confirmar_verificacion.accepted' => 'Confirma que comprobaste la recepción real del abono.']);
        $accion->verificar($pago, $solicitud->user());

        return redirect()->route('pagos.show', $pago->pedido_id)->with('estado', 'Abono verificado. El saldo y la reserva se actualizaron.');
    }

    public function corregir(Pago $pago, ConsultarPedidos $fuente)
    {
        $pedido = $pago->pedido;
        if ($pago->anulado_en) {
            return redirect()->route('pagos.show', $pedido->id)->withErrors(['pago' => 'El abono #'.$pago->id.' ya está anulado.']);
        }
        $resumen = $fuente->resumen($pedido);
        $maximo = $resumen['saldoRegistrable'] + Importes::aCentavos($pago->monto);
        $clave = (string) Str::uuid();

        return view('pagos.corregir', compact('pago', 'pedido', 'maximo', 'clave'));
    }

    public function guardarCorreccion(SolicitudCorreccionPago $solicitud, Pago $pago, CorregirPago $accion)
    {
        $nuevo = $accion->corregir($pago, $solicitud->validated(), $solicitud->user());

        return redirect()->route('pagos.show', $pago->pedido_id)->with('estado', 'Abono #'.$pago->id.' corregido por el abono #'.$nuevo->id.($nuevo->verificado_en ? ', ya verificado.' : ', pendiente de verificación.'));
    }

    public function anular(Request $solicitud, Pago $pago, CorregirPago $accion)
    {
        $datos = $solicitud->validate(
            ['motivo' => ['required', 'string', 'min:5', 'max:300'], 'confirmar_anulacion' => ['accepted']],
            ['confirmar_anulacion.accepted' => 'Confirma que deseas anular el abono.'],
            ['motivo' => 'motivo de la anulación']
        );
        $accion->anular($pago, $datos['motivo'], $solicitud->user());

        return redirect()->route('pagos.show', $pago->pedido_id)->with('estado', 'Abono #'.$pago->id.' anulado. Se conserva en el historial.');
    }
}
