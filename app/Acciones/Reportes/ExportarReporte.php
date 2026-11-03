<?php

namespace App\Acciones\Reportes;

use App\Soporte\ImporteReporte;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarReporte
{
    public function descargar(string $tipo, array $filtros): StreamedResponse
    {
        return response()->streamDownload(function () use ($tipo, $filtros) {
            DB::transaction(function () use ($tipo, $filtros) {
                $salida = fopen('php://output', 'w');
                try {
                    fwrite($salida, "\xEF\xBB\xBF");
                    $consulta = app(ConsultarReportes::class);
                    if ($tipo === 'resumen') {
                        $this->fila($salida, ['Desde', 'Hasta', 'Pedidos vendidos', 'Ventas (Q)', 'Costo histórico (Q)', 'Ganancia bruta (Q)', 'Cobros verificados (Q)']);
                        foreach ($consulta->resumen($filtros)['periodos'] as $periodo) {
                            $this->fila($salida, [$periodo['desde'], $periodo['hasta'], $periodo['pedidos'], ...array_map(fn ($campo) => ImporteReporte::decimal($periodo[$campo]), ['ventas', 'costos', 'ganancia', 'cobros'])]);
                        }
                    } elseif ($tipo === 'ventas') {
                        $this->fila($salida, ['Pedido', 'Cliente histórico', 'Fecha de venta', 'Total (Q)', 'Costo histórico (Q)', 'Ganancia bruta (Q)']);
                        foreach ($consulta->ventas($filtros)->orderBy('fecha_venta')->orderBy('pedido_id')->lazy(500) as $venta) {
                            $this->fila($salida, [$venta->pedido_id, $this->textoSeguro($venta->cliente_nombre), $venta->fecha_venta, ImporteReporte::decimal((int) $venta->total_centavos), ImporteReporte::decimal((int) $venta->costo_centavos), ImporteReporte::decimal((int) $venta->ganancia_centavos)]);
                        }
                    } else {
                        $this->fila($salida, ['Abono', 'Fecha de pago', 'Pedido', 'Cliente histórico', 'Monto (Q)', 'Método', 'Estado del pedido']);
                        foreach ($consulta->cobros($filtros)->orderBy('fecha_pago')->orderBy('pago_id')->lazy(500) as $cobro) {
                            $this->fila($salida, [$cobro->pago_id, $cobro->fecha_pago, $cobro->pedido_id, $this->textoSeguro($cobro->cliente_nombre), ImporteReporte::decimal((int) $cobro->monto_centavos), $this->textoSeguro($cobro->metodo), $this->textoSeguro($cobro->estado_pedido)]);
                        }
                    }
                } finally {
                    fclose($salida);
                }
            });
        }, "reportes-$tipo-{$filtros['desde']}-{$filtros['hasta']}.csv", ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function fila($salida, array $datos): void
    {
        fputcsv($salida, $datos, ',', '"', '', "\r\n");
    }

    private function textoSeguro(string $texto): string
    {
        // Excel puede interpretar fórmulas incluso tras espacios o controles.
        return preg_match('/^[\s\x{FEFF}]*[=+\-@]|^[\t\r\n]/u', $texto) ? "'".$texto : $texto;
    }
}
