<?php

namespace App\Acciones\Pagos;

use App\Models\Pedido;
use App\Models\Reembolso;
use App\Models\User;
use App\Soporte\Importes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Regla acordada con el equipo (2026-11-02):
 * - solo pedidos cancelados y solo lo verificado (pagos vigentes) menos lo ya reembolsado;
 * - puede ser parcial (retener una parte) o total, en uno o varios registros;
 * - si el pedido se envió, primero debe confirmarse el regreso físico de las piezas;
 * - solo administrador (permiso registrar-reembolsos).
 * Orden de bloqueo: pedido → pagos → reembolsos.
 */
class RegistrarReembolso
{
    public function registrar(int $pedidoId, array $datos, User $usuario): Reembolso
    {
        try {
            return DB::transaction(function () use ($pedidoId, $datos, $usuario) {
                $pedido = Pedido::whereKey($pedidoId)->lockForUpdate()->firstOrFail();
                $importe = Importes::aCentavos((string) $datos['importe']);

                $anterior = Reembolso::where('clave_operacion', $datos['clave_operacion'])->lockForUpdate()->first();
                if ($anterior) {
                    if ((int) $anterior->pedido_id !== $pedidoId || Importes::aCentavos($anterior->monto) !== $importe || $anterior->fecha_reembolso->format('Y-m-d') !== $datos['fecha']) {
                        $this->error('La operación ya se utilizó con otros datos. Recarga la página.', 'clave_operacion');
                    }

                    return $anterior; // Reenvío del mismo formulario.
                }

                if ($pedido->estado !== 'cancelado') {
                    $this->error('Solo se reembolsa un pedido cancelado. Cancela el pedido primero.');
                }
                // La cancelación pudo marcar la devolución mientras esperábamos el pedido.
                // Una lectura de snapshot antigua no debe permitir devolver dinero antes del regreso.
                if ($pedido->reservas()->where('estado', 'devolucion_pendiente')->lockForUpdate()->get()->isNotEmpty()) {
                    $this->error('Las piezas enviadas todavía no han regresado. Confirma el regreso físico en la ficha del pedido antes de reembolsar.');
                }

                $disponible = $this->disponibleCentavos($pedido, bloquear: true);
                if ($importe <= 0 || $importe > $disponible) {
                    $this->error('El reembolso debe ser positivo y no superar Q '.Importes::aDecimal($disponible).', lo verificado que aún no se ha devuelto.', 'importe');
                }

                $reembolso = Reembolso::create([
                    'pedido_id' => $pedido->id, 'usuario_id' => $usuario->id, 'monto' => Importes::aDecimal($importe),
                    'metodo' => $datos['metodo'], 'referencia' => $datos['referencia'] ?? null, 'fecha_reembolso' => $datos['fecha'],
                    'motivo' => $datos['motivo'], 'clave_operacion' => $datos['clave_operacion'],
                ]);
                $pedido->increment('version');
                $retenido = $disponible - $importe;
                $pedido->eventos()->create(['usuario_id' => $usuario->id, 'tipo' => 'reembolso_registrado', 'descripcion' => 'Reembolso #'.$reembolso->id.' de Q '.$reembolso->monto.'. '.($retenido > 0 ? 'Quedan Q '.Importes::aDecimal($retenido).' sin devolver.' : 'Se devolvió todo lo verificado.').' Motivo: '.$datos['motivo']]);

                return $reembolso;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            $this->error('Este reembolso ya se registró. Recarga la página para revisar el historial.', 'clave_operacion');
        }
    }

    /** Verificado vigente menos lo ya reembolsado, en centavos. */
    public function disponibleCentavos(Pedido $pedido, bool $bloquear = false): int
    {
        $reembolsos = $pedido->reembolsos();
        if ($bloquear) {
            $reembolsos->lockForUpdate();
        }
        $reembolsado = $reembolsos->get()->sum(fn (Reembolso $reembolso) => Importes::aCentavos($reembolso->monto));

        return max(0, $pedido->pagadoCentavos(bloquear: $bloquear) - $reembolsado);
    }

    private function error(string $mensaje, string $campo = 'reembolso'): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
