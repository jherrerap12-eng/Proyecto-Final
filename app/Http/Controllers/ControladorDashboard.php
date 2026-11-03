<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Soporte\Importe;
use Illuminate\View\View;

class ControladorDashboard extends Controller
{
    public function index(): View
    {
        // El estado comercial es distinto del logístico. Dinero calculado en centavos.
        $pedidos = Pedido::where('estado', 'confirmado')->with(['detalles', 'pagos'])->get();
        $ventas = 0;
        $ganancia = 0;
        $saldo = 0;
        foreach ($pedidos as $pedido) {
            $pagado = $pedido->pagos
                ->filter(fn (Pago $pago) => $pago->verificado_en !== null && $pago->verificado_por !== null && $pago->anulado_en === null)
                ->sum(fn (Pago $pago) => Importe::centavos($pago->monto));
            $saldo += max(0, Importe::centavos($pedido->total) - $pagado);
            if ($pedido->estado_logistico === 'entregado') {
                $ventas += Importe::centavos($pedido->total);
                $ganancia += $pedido->detalles->sum(fn ($detalle) => Importe::centavos($detalle->precio) - Importe::centavos($detalle->costo));
            }
        }
        $resumenVentas = (object) ['total_ventas' => Importe::decimal($ventas), 'total_ganancia' => Importe::decimal($ganancia)];
        $pedidosPorPreparar = $pedidos->where('estado_logistico', 'por_preparar')->count();
        $pedidosEmpacados = $pedidos->where('estado_logistico', 'empacado')->count();
        $pedidosEnviados = $pedidos->where('estado_logistico', 'enviado')->count();
        $pedidosEntregados = $pedidos->where('estado_logistico', 'entregado')->count();
        $piezasDisponibles = Producto::where('activo', true)->where('existencias', 1)->whereDoesntHave('reservaActual')->count();
        $piezasReservadas = Producto::where('activo', true)->where('existencias', 1)->whereHas('reservaActual')->count();
        $saldosPorCobrar = Importe::decimal($saldo);

        return view('panel.inicio', compact('resumenVentas', 'pedidosPorPreparar', 'pedidosEmpacados', 'pedidosEnviados', 'pedidosEntregados', 'piezasDisponibles', 'piezasReservadas', 'saldosPorCobrar'));
    }
}
