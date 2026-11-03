<?php

namespace App\Http\Controllers;

use App\Acciones\Reportes\ConsultarReportes;
use App\Acciones\Reportes\ExportarReporte;
use App\Http\Requests\SolicitudReporte;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ControladorReportes extends Controller
{
    public function index(SolicitudReporte $solicitud, ConsultarReportes $consulta): View
    {
        $filtros = $solicitud->filtros();

        return DB::transaction(function () use ($consulta, $filtros, $solicitud) {
            $resumen = $consulta->resumen($filtros);
            $ventas = $consulta->ventas($filtros)->orderByDesc('fecha_venta')->orderByDesc('pedido_id')
                ->paginate(20, ['*'], 'ventas_pagina')->appends($solicitud->query());
            $cobros = $consulta->cobros($filtros)->orderByDesc('fecha_pago')->orderByDesc('pago_id')
                ->paginate(20, ['*'], 'cobros_pagina')->appends($solicitud->query());

            return view('reportes.indice', compact('filtros', 'resumen', 'ventas', 'cobros'));
        });
    }

    public function exportar(SolicitudReporte $solicitud, ExportarReporte $exportador): StreamedResponse
    {
        return $exportador->descargar($solicitud->validated('tipo'), $solicitud->filtros());
    }
}
