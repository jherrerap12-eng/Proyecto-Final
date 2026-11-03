<?php

namespace App\Acciones\Reportes;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ConsultarReportes
{
    private function centavos(string $columna): string
    {
        $tipo = DB::getDriverName() === 'sqlite' ? 'INTEGER' : 'SIGNED';

        // Convertir cada DECIMAL antes de sumar, también en las pruebas SQLite.
        return "CAST(ROUND($columna * 100, 0) AS $tipo)";
    }

    private function pagosValidos(): Builder
    {
        return DB::table('pagos')->whereNotNull('verificado_en')
            ->whereNotNull('verificado_por')->whereNull('anulado_en');
    }

    public function ventas(array $filtros): Builder
    {
        $abonos = $this->pagosValidos()->select('pedido_id')
            ->selectRaw('SUM('.$this->centavos('monto').') AS abonado_centavos, MAX(verificado_en) AS ultima_verificacion')
            ->groupBy('pedido_id');
        $detalles = DB::table('detalles_pedido')->select('pedido_id')
            ->selectRaw('SUM('.$this->centavos('costo').') AS costo_centavos, SUM('.$this->centavos('precio').') AS precio_centavos')
            ->groupBy('pedido_id');
        $base = DB::table('pedidos as p')
            ->joinSub($abonos, 'a', 'a.pedido_id', '=', 'p.id')
            ->joinSub($detalles, 'd', 'd.pedido_id', '=', 'p.id')
            ->where('p.estado', 'confirmado')->whereNotNull('p.confirmado_en')->where('p.total', '>', 0)
            ->whereRaw('a.abonado_centavos >= '.$this->centavos('p.total'))
            ->select('p.id as pedido_id', 'p.cliente_nombre')
            ->selectRaw('CASE WHEN p.confirmado_en > a.ultima_verificacion THEN p.confirmado_en ELSE a.ultima_verificacion END AS fecha_venta')
            ->selectRaw($this->centavos('p.total').' AS total_centavos, d.costo_centavos, d.precio_centavos - d.costo_centavos AS ganancia_centavos');
        $fin = CarbonImmutable::parse($filtros['hasta'], 'America/Guatemala')->addDay()->toDateString();

        return DB::query()->fromSub($base, 'ventas')
            ->where('fecha_venta', '>=', $filtros['desde'].' 00:00:00')
            ->where('fecha_venta', '<', $fin.' 00:00:00');
    }

    public function cobros(array $filtros): Builder
    {
        $fin = CarbonImmutable::parse($filtros['hasta'], 'America/Guatemala')->addDay()->toDateString();

        return $this->pagosValidos()->join('pedidos as p', 'p.id', '=', 'pagos.pedido_id')
            ->where('pagos.fecha_pago', '>=', $filtros['desde'])
            ->where('pagos.fecha_pago', '<', $fin)
            ->select('pagos.id as pago_id', 'pagos.pedido_id', 'p.cliente_nombre', 'pagos.fecha_pago', 'pagos.metodo', 'p.estado as estado_pedido')
            ->selectRaw($this->centavos('pagos.monto').' AS monto_centavos');
    }

    public function resumen(array $filtros): array
    {
        $totales = $this->ceros();
        $periodos = [];
        foreach ($this->ventas($filtros)->orderBy('pedido_id')->lazy(500) as $venta) {
            $clave = $this->periodo($periodos, $venta->fecha_venta, $filtros);
            foreach (['pedidos' => 1, 'ventas' => (int) $venta->total_centavos, 'costos' => (int) $venta->costo_centavos, 'ganancia' => (int) $venta->ganancia_centavos] as $campo => $valor) {
                $totales[$campo] += $valor;
                $periodos[$clave][$campo] += $valor;
            }
        }
        foreach ($this->cobros($filtros)->orderBy('pago_id')->lazy(500) as $cobro) {
            $clave = $this->periodo($periodos, $cobro->fecha_pago, $filtros);
            $totales['cobros'] += (int) $cobro->monto_centavos;
            $periodos[$clave]['cobros'] += (int) $cobro->monto_centavos;
        }
        ksort($periodos);

        return ['totales' => $totales, 'periodos' => array_values($periodos)];
    }

    private function ceros(): array
    {
        return ['pedidos' => 0, 'ventas' => 0, 'costos' => 0, 'ganancia' => 0, 'cobros' => 0];
    }

    private function periodo(array &$periodos, string $fecha, array $filtros): string
    {
        $dia = CarbonImmutable::parse($fecha, 'America/Guatemala')->startOfDay();
        [$inicio, $fin] = match ($filtros['agrupacion']) {
            'semanal' => [$dia->startOfWeek(CarbonImmutable::MONDAY), $dia->endOfWeek(CarbonImmutable::SUNDAY)],
            'mensual' => [$dia->startOfMonth(), $dia->endOfMonth()],
            default => [$dia, $dia],
        };
        $clave = $inicio->toDateString();
        if (! isset($periodos[$clave])) {
            $desde = max($inicio->toDateString(), $filtros['desde']);
            $hasta = min($fin->toDateString(), $filtros['hasta']);
            $periodos[$clave] = array_merge(['desde' => $desde, 'hasta' => $hasta], $this->ceros());
        }

        return $clave;
    }
}
