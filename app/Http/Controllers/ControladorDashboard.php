<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Pieza;
use App\Models\Pago; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControladorDashboard extends Controller
{
    public function index()
    {
        $resumenVentas = Pedido::where('estado', 'entregado')
            ->select(
                DB::raw('SUM(total) as total_ventas'),
                DB::raw('SUM(ganancia_bruta) as total_ganancia') 
            )->first();

        // 2. Pedidos por estado
        $pedidosPorPreparar = Pedido::where('estado', 'por_preparar')->count();
        $pedidosEnviados    = Pedido::where('estado', 'enviado')->count();
        $pedidosEntregados  = Pedido::where('estado', 'entregado')->count();

        $piezasDisponibles = DB::table('piezas')->where('estado', 'disponible')->count();
        $piezasReservadas  = DB::table('piezas')->where('estado', 'reservada')->count();

        $saldosPorCobrar = Pedido::whereIn('estado', ['pendiente', 'parcial', 'por_preparar', 'enviado'])
            ->sum('saldo_pendientes'); 

        return view('panel.inicio', compact(
            'resumenVentas',
            'pedidosPorPreparar',
            'pedidosEnviados',
            'pedidosEntregados',
            'piezasDisponibles',
            'piezasReservadas',
            'saldosPorCobrar'
        ));
    }
}