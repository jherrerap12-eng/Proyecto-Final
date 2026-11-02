<?php

namespace App\Acciones\Pagos;

use App\Models\Pago;
use App\Models\Pedido;
use App\Soporte\Importes;
use Illuminate\Database\Eloquent\Builder;

class ConsultarPedidos
{
    public function consulta(): Builder
    {
        return Pedido::query()->with('pagos');
    }

    public function resumen(Pedido $pedido, bool $bloquear = false): array
    {
        $pagos = $bloquear ? $pedido->pagos()->lockForUpdate()->get() : ($pedido->relationLoaded('pagos') ? $pedido->pagos : $pedido->pagos()->get());
        $vigentes = $pagos->filter(fn (Pago $pago) => $pago->anulado_en === null);
        $registrado = $vigentes->sum(fn (Pago $pago) => Importes::aCentavos($pago->monto));
        $abonado = $vigentes->filter(fn (Pago $pago) => $pago->verificado_en !== null && $pago->verificado_por !== null)->sum(fn (Pago $pago) => Importes::aCentavos($pago->monto));
        $total = Importes::aCentavos($pedido->total);

        return ['abonado' => $abonado, 'pendienteVerificacion' => $registrado - $abonado, 'saldo' => max(0, $total - $abonado), 'saldoRegistrable' => max(0, $total - $registrado)];
    }
}
