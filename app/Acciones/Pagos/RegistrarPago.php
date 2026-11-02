<?php

namespace App\Acciones\Pagos;

use App\Models\Pago;
use App\Models\User;
use App\Soporte\Importe;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarPago
{
    public function registrar(int $pedidoId, array $datos, User $usuario): Pago
    {
        abort_unless(app(ConsultarPedidos::class)->disponible(), 409, 'El módulo de pedidos aún no está integrado.');

        try {
            return DB::transaction(function () use ($pedidoId, $datos, $usuario) {
                $pedido = DB::table('pedidos')->where('id', $pedidoId)->lockForUpdate()->first();
                abort_unless($pedido, 404);
                $importe = Importe::centavos((string) $datos['importe']);
                $anterior = Pago::where('clave_operacion', $datos['clave_operacion'])->first();
                if ($anterior) {
                    if ($anterior->pedido_id != $pedidoId || $anterior->usuario_id !== $usuario->id ||
                        Importe::centavos($anterior->importe) !== $importe || $anterior->metodo !== $datos['metodo'] ||
                        $anterior->referencia !== ($datos['referencia'] ?? null) || $anterior->fecha->format('Y-m-d') !== $datos['fecha'] ||
                        $anterior->observaciones !== ($datos['observaciones'] ?? null)) {
                        throw ValidationException::withMessages(['clave_operacion' => 'La operación ya se utilizó con otros datos. Recarga la página.']);
                    }

                    return $anterior;
                }
                if ($pedido->estado === 'cancelado') {
                    throw ValidationException::withMessages(['importe' => 'No se pueden registrar pagos en un pedido cancelado.']);
                }
                $abonado = Pago::where('pedido_id', $pedidoId)->get()->sum(fn ($pago) => Importe::centavos($pago->importe));
                $saldo = Importe::centavos((string) $pedido->total) - $abonado;
                if ($importe <= 0 || $importe > $saldo) {
                    throw ValidationException::withMessages(['importe' => 'El abono debe ser mayor que cero y no superar el saldo pendiente.']);
                }

                return Pago::create(array_merge($datos, ['pedido_id' => $pedidoId, 'usuario_id' => $usuario->id, 'importe' => Importe::decimal($importe)]));
            }, 3);
        } catch (UniqueConstraintViolationException $excepcion) {
            throw ValidationException::withMessages(['clave_operacion' => 'Esta operación ya se registró. Recarga la página para revisar el historial.']);
        }
    }
}
