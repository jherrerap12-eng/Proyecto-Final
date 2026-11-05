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

/**
 * Anulación y corrección de abonos sin borrar historial.
 * Orden de bloqueo compartido con RegistrarPago y pedidos: pedido → pagos.
 */
class CorregirPago
{
    public function anular(Pago $pago, string $motivo, User $usuario): Pago
    {
        return DB::transaction(function () use ($pago, $motivo, $usuario) {
            [$pedido, $pago] = $this->bloquear($pago);
            if ($pago->anulado_en) {
                return $pago; // Reenvío del mismo formulario: no repetir la anulación.
            }
            $verificado = $this->estaVerificado($pago);
            if ($verificado && $pedido->estado === 'cancelado') {
                $this->error('No se anula un abono verificado de un pedido cancelado: para devolver el dinero, registra un reembolso en la ficha de pagos del pedido.');
            }
            if ($verificado) {
                $this->protegerReservaFirme($pedido, $pedido->pagadoCentavos(bloquear: true) - Importes::aCentavos($pago->monto));
            }
            $this->marcarAnulado($pago, $motivo, $usuario);
            $pedido->increment('version');
            $pedido->eventos()->create(['usuario_id' => $usuario->id, 'tipo' => 'pago_anulado', 'descripcion' => 'Abono #'.$pago->id.' de Q '.$pago->monto.' anulado'.($verificado ? ' (estaba verificado)' : '').'. Motivo: '.$motivo]);

            return $pago;
        }, 3);
    }

    /**
     * Reemplaza un abono con datos corregidos: el original queda anulado y enlazado,
     * el nuevo queda pendiente salvo que se confirme su verificación.
     */
    public function corregir(Pago $pago, array $datos, User $usuario): Pago
    {
        try {
            return DB::transaction(function () use ($pago, $datos, $usuario) {
                [$pedido, $pago] = $this->bloquear($pago);
                $importe = Importes::aCentavos((string) $datos['importe']);

                $anterior = Pago::where('clave_operacion', $datos['clave_operacion'])->lockForUpdate()->first();
                if ($anterior) {
                    if ((int) $anterior->corrige_pago_id !== $pago->id || Importes::aCentavos($anterior->monto) !== $importe) {
                        $this->error('La operación ya se utilizó con otros datos. Recarga la página.', 'clave_operacion');
                    }

                    return $anterior;
                }
                if ($pago->anulado_en) {
                    $this->error('El abono ya está anulado. Si falta registrar el importe correcto, regístralo como abono nuevo.');
                }
                if (! in_array($pedido->estado, ['borrador', 'confirmado'], true) || ($pedido->estado === 'borrador' && $pedido->modalidad !== 'deposito')) {
                    $this->error('Solo se corrigen abonos de pedidos que todavía admiten pagos. En un pedido cancelado, anula el abono pendiente o registra un reembolso.');
                }

                $verificarNuevo = (bool) ($datos['verificar'] ?? false);
                $originalVerificado = $this->estaVerificado($pago);
                if ($verificarNuevo && ! $usuario->can('verificar-pagos')) {
                    $this->error('Tu rol no permite verificar pagos. La corrección quedará pendiente si desmarcas la verificación.', 'verificar');
                }

                $resumen = app(ConsultarPedidos::class)->resumen($pedido, bloquear: true);
                $disponible = $resumen['saldoRegistrable'] + Importes::aCentavos($pago->monto);
                if ($importe <= 0 || $importe > $disponible) {
                    $this->error('El importe corregido debe ser positivo y no superar Q '.Importes::aDecimal($disponible).', el saldo disponible sin contar el abono original.', 'importe');
                }

                $pagadoDespues = $pedido->pagadoCentavos(bloquear: true)
                    - ($originalVerificado ? Importes::aCentavos($pago->monto) : 0)
                    + ($verificarNuevo ? $importe : 0);
                $this->protegerReservaFirme($pedido, $pagadoDespues);

                $nuevo = Pago::create([
                    'pedido_id' => $pedido->id, 'usuario_id' => $usuario->id, 'monto' => Importes::aDecimal($importe),
                    'metodo' => $datos['metodo'], 'referencia' => $datos['referencia'] ?? null, 'fecha_pago' => $datos['fecha'],
                    'observaciones' => $datos['observaciones'] ?? null, 'clave_operacion' => $datos['clave_operacion'],
                    'corrige_pago_id' => $pago->id,
                ]);
                if ($verificarNuevo) {
                    $nuevo->forceFill(['verificado_en' => now(), 'verificado_por' => $usuario->id])->save();
                }
                $this->marcarAnulado($pago, 'Corregido por el abono #'.$nuevo->id.'. '.$datos['motivo'], $usuario);
                $pedido->increment('version');
                $pedido->eventos()->create(['usuario_id' => $usuario->id, 'tipo' => 'pago_corregido', 'descripcion' => 'Abono #'.$pago->id.' (Q '.$pago->monto.') corregido por el abono #'.$nuevo->id.' (Q '.$nuevo->monto.', '.($verificarNuevo ? 'verificado' : 'pendiente de verificación').'). Motivo: '.$datos['motivo']]);
                if ($verificarNuevo) {
                    app(GestionarPedidos::class)->promoverReserva($pedido, $usuario);
                }

                return $nuevo;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            $this->error('Esta corrección ya se registró. Recarga la página para revisar el historial.', 'clave_operacion');
        }
    }

    /** @return array{0: Pedido, 1: Pago} */
    private function bloquear(Pago $pago): array
    {
        $pedido = Pedido::whereKey($pago->pedido_id)->lockForUpdate()->firstOrFail();
        $pedido->pagos()->lockForUpdate()->get();

        return [$pedido, Pago::whereKey($pago->id)->lockForUpdate()->firstOrFail()];
    }

    /**
     * Regla acordada: no dejar una reserva firme sin el mínimo verificado.
     * El flujo explícito es registrar/verificar otro abono primero, o cancelar el pedido.
     */
    private function protegerReservaFirme(Pedido $pedido, int $pagadoDespues): void
    {
        // Lectura actual: bajo REPEATABLE READ una consulta normal puede seguir viendo
        // la reserva temporal anterior a una verificación concurrente ya confirmada.
        $tieneFirme = $pedido->reservas()->where('estado', 'activa')->where('tipo', 'firme')->lockForUpdate()->get()->isNotEmpty();
        $minimo = max(1, $pedido->depositoMinimoCentavos());
        if ($tieneFirme && $pagadoDespues < $minimo) {
            $this->error('La reserva firme quedaría con Q '.Importes::aDecimal(max(0, $pagadoDespues)).' verificados y necesita al menos Q '.Importes::aDecimal($minimo).'. Registra y verifica primero el abono correcto, o cancela el pedido.');
        }
    }

    private function marcarAnulado(Pago $pago, string $motivo, User $usuario): void
    {
        $pago->forceFill(['anulado_en' => now(), 'anulado_por' => $usuario->id, 'motivo_anulacion' => mb_substr($motivo, 0, 500)])->save();
    }

    private function estaVerificado(Pago $pago): bool
    {
        return $pago->verificado_en !== null && $pago->verificado_por !== null;
    }

    private function error(string $mensaje, string $campo = 'pago'): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
