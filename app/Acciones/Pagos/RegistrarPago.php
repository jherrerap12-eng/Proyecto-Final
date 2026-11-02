<?php

namespace App\Acciones\Pagos;

use App\Acciones\Pedidos\GestionarPedidos;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use App\Soporte\Importes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarPago
{
    public function registrar(int $pedidoId, array $datos, User $usuario): Pago
    {
        try {
            return DB::transaction(function () use ($pedidoId, $datos, $usuario) {
                $pedido = Pedido::whereKey($pedidoId)->lockForUpdate()->firstOrFail();
                $importe = Importes::aCentavos((string) $datos['importe']);
                $anterior = Pago::where('clave_operacion', $datos['clave_operacion'])->lockForUpdate()->first();
                if ($anterior) {
                    if ((int) $anterior->pedido_id !== $pedidoId || (int) $anterior->usuario_id !== $usuario->id ||
                        Importes::aCentavos($anterior->monto) !== $importe || $anterior->metodo !== $datos['metodo'] ||
                        $anterior->referencia !== ($datos['referencia'] ?? null) || $anterior->fecha_pago->format('Y-m-d') !== $datos['fecha'] ||
                        $anterior->observaciones !== ($datos['observaciones'] ?? null)) {
                        throw ValidationException::withMessages(['clave_operacion' => 'La operación ya se utilizó con otros datos. Recarga la página.']);
                    }

                    return $anterior;
                }
                $this->admitePagos($pedido);
                $resumen = app(ConsultarPedidos::class)->resumen($pedido, bloquear: true);
                if ($importe <= 0 || $importe > $resumen['saldoRegistrable']) {
                    throw ValidationException::withMessages(['importe' => 'El abono debe ser positivo y no superar el saldo disponible para registrar. Se incluyen los abonos pendientes de verificar.']);
                }
                $pago = Pago::create([
                    'pedido_id' => $pedido->id, 'usuario_id' => $usuario->id, 'monto' => Importes::aDecimal($importe),
                    'metodo' => $datos['metodo'], 'referencia' => $datos['referencia'] ?? null, 'fecha_pago' => $datos['fecha'],
                    'observaciones' => $datos['observaciones'] ?? null, 'clave_operacion' => $datos['clave_operacion'],
                ]);
                $pedido->increment('version');
                $pedido->eventos()->create(['usuario_id' => $usuario->id, 'tipo' => 'pago_registrado', 'descripcion' => 'Abono #'.$pago->id.' registrado, pendiente de verificación.']);

                return $pago;
            }, 3);
        } catch (UniqueConstraintViolationException $excepcion) {
            throw ValidationException::withMessages(['clave_operacion' => 'Esta operación ya se registró. Recarga la página para revisar el historial.']);
        }
    }

    public function verificar(Pago $pago, User $usuario): void
    {
        DB::transaction(function () use ($pago, $usuario) {
            // Compartir siempre el orden de bloqueo con pedidos/cancelaciones.
            $pedido = Pedido::whereKey($pago->pedido_id)->lockForUpdate()->firstOrFail();
            $pago = Pago::whereKey($pago->id)->lockForUpdate()->firstOrFail();
            if ($pago->verificado_en && $pago->verificado_por) {
                return;
            }
            $this->admitePagos($pedido);
            if ($pago->anulado_en) {
                throw ValidationException::withMessages(['pago' => 'No se puede verificar un abono anulado.']);
            }
            if ($pedido->pagadoCentavos(bloquear: true) + Importes::aCentavos($pago->monto) > Importes::aCentavos($pedido->total)) {
                throw ValidationException::withMessages(['pago' => 'El pago excedería el total del pedido. Revisa los abonos.']);
            }
            $pago->forceFill(['verificado_en' => now(), 'verificado_por' => $usuario->id])->save();
            $pedido->increment('version');
            $pedido->eventos()->create(['usuario_id' => $usuario->id, 'tipo' => 'pago_verificado', 'descripcion' => 'Abono #'.$pago->id.' verificado por el operador.']);
            app(GestionarPedidos::class)->promoverReserva($pedido, $usuario);
        }, 3);
    }

    private function admitePagos(Pedido $pedido): void
    {
        if ($pedido->estado === 'cancelado' || ($pedido->estado === 'borrador' && $pedido->modalidad !== 'deposito') || ! in_array($pedido->estado, ['borrador', 'confirmado'], true)) {
            throw ValidationException::withMessages(['importe' => 'Solo se admiten abonos en pedidos confirmados o borradores con depósito previo.']);
        }
    }
}
