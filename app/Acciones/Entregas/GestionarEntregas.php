<?php

namespace App\Acciones\Entregas;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GestionarEntregas
{
    public function empacar(Pedido $pedido, int $version, User $usuario): void
    {
        DB::transaction(function () use ($pedido, $version, $usuario) {
            $pedido = $this->bloquearPedido($pedido);
            $envio = $pedido->envio()->lockForUpdate()->first();
            if ($pedido->estado_logistico === 'empacado' && $envio) {
                return;
            }
            $this->version($pedido, $version);
            if ($pedido->estado_logistico !== 'por_preparar' || $envio) {
                $this->error('Solo puedes empacar un pedido que está por preparar.');
            }
            $this->piezasReservadas($pedido, 1);
            $pedido->envio()->create(['destinatario' => $pedido->cliente_nombre, 'telefono' => $pedido->cliente_telefono, 'direccion' => $pedido->cliente_direccion, 'empacado_por' => $usuario->id, 'empacado_en' => now()]);
            $pedido->estado_logistico = 'empacado';
            $pedido->version++;
            $pedido->save();
            $this->evento($pedido, $usuario, 'empacado', 'Pedido empacado; las piezas continúan reservadas en el negocio.');
        }, 3);
    }

    public function enviar(Pedido $pedido, array $datos, User $usuario): void
    {
        DB::transaction(function () use ($pedido, $datos, $usuario) {
            $pedido = $this->bloquearPedido($pedido);
            $envio = $pedido->envio()->lockForUpdate()->first();
            if (! $envio) {
                $this->error('Empaca el pedido antes de registrar el envío.');
            }
            $campos = ['transportista', 'guia', 'destinatario', 'telefono', 'direccion', 'observaciones'];
            if (in_array($pedido->estado_logistico, ['enviado', 'entregado'], true)) {
                foreach ($campos as $campo) {
                    if ($envio->$campo !== ($datos[$campo] ?? null)) {
                        $this->error('El envío ya está registrado con otros datos. No se duplicará la salida.');
                    }
                }

                return;
            }
            $this->version($pedido, (int) $datos['version']);
            if ($pedido->estado_logistico !== 'empacado') {
                $this->error('El pedido debe estar empacado antes del envío.');
            }
            if ($pedido->modalidad === 'deposito' && $pedido->pagadoCentavos(bloquear: true) < $pedido->depositoMinimoCentavos()) {
                $this->error('El pedido con depósito ya no cubre el mínimo verificado requerido. Revisa los pagos.');
            }
            $piezas = $this->piezasReservadas($pedido, 1);
            foreach ($piezas as $pieza) {
                $pieza->existencias = 0;
                $pieza->version++;
                $pieza->save();
                $pieza->movimientos()->create(['usuario_id' => $usuario->id, 'referencia' => (string) Str::uuid(), 'tipo' => 'envio', 'cantidad' => -1, 'existencias_anteriores' => 1, 'existencias_posteriores' => 0, 'motivo' => 'Salida física por envío del pedido #'.$pedido->id]);
            }
            $envio->fill(collect($datos)->only($campos)->all());
            $envio->enviado_por = $usuario->id;
            $envio->enviado_en = now();
            $envio->save();
            $pedido->estado_logistico = 'enviado';
            $pedido->version++;
            $pedido->save();
            $this->evento($pedido, $usuario, 'enviado', 'Pedido enviado por '.($envio->transportista === 'forza' ? 'Forza' : 'mensajería propia').'. Se registró la salida física de todas sus piezas.');
        }, 3);
    }

    public function entregar(Pedido $pedido, int $version, ?string $observaciones, User $usuario): void
    {
        DB::transaction(function () use ($pedido, $version, $observaciones, $usuario) {
            $pedido = $this->bloquearPedido($pedido);
            $envio = $pedido->envio()->lockForUpdate()->first();
            if ($pedido->estado_logistico === 'entregado' && $envio) {
                if ($envio->observaciones_entrega !== $observaciones) {
                    $this->error('La entrega ya fue confirmada con otras observaciones.');
                }

                return;
            }
            $this->version($pedido, $version);
            if ($pedido->estado_logistico !== 'enviado' || ! $envio || ! $envio->enviado_en) {
                $this->error('Solo se confirma la entrega de un pedido enviado.');
            }
            $this->piezasReservadas($pedido, 0);
            $envio->entregado_por = $usuario->id;
            $envio->entregado_en = now();
            $envio->observaciones_entrega = $observaciones;
            $envio->save();
            $pedido->estado_logistico = 'entregado';
            $pedido->version++;
            $pedido->save();
            $this->evento($pedido, $usuario, 'entregado', 'Entrega física confirmada.'.($observaciones ? ' '.$observaciones : ''));
        }, 3);
    }

    private function bloquearPedido(Pedido $pedido): Pedido
    {
        $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
        if ($pedido->estado !== 'confirmado') {
            $this->error('Solo se preparan o entregan pedidos confirmados. Los cancelados conservan su historial.');
        }

        return $pedido;
    }

    private function piezasReservadas(Pedido $pedido, int $existencia)
    {
        $detalles = $pedido->detalles()->orderBy('producto_id')->lockForUpdate()->get();
        $piezas = Producto::whereIn('id', $detalles->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $reservas = $pedido->reservas()->whereNotNull('producto_bloqueado_id')->orderBy('producto_id')->lockForUpdate()->get()->keyBy('producto_id');
        if ($detalles->isEmpty() || $detalles->count() !== $piezas->count() || $detalles->count() !== $reservas->count()) {
            $this->error('Faltan piezas o reservas del pedido. Revisa inventario antes de continuar.');
        }
        foreach ($detalles as $detalle) {
            $pieza = $piezas->get($detalle->producto_id);
            $reserva = $reservas->get($detalle->producto_id);
            if (! $pieza || ! $reserva || ! $pieza->activo || $pieza->existencias !== $existencia || $reserva->estado !== 'activa' || (int) $reserva->detalle_pedido_id !== $detalle->id || (int) $reserva->producto_bloqueado_id !== $pieza->id) {
                $this->error('Las existencias o la reserva de una pieza no corresponden al estado del envío. No se aplicaron cambios.');
            }
        }

        return $piezas;
    }

    private function version(Pedido $pedido, int $version): void
    {
        if ($pedido->version !== $version) {
            $this->error('Otro usuario modificó el pedido. Recarga y revisa antes de continuar.');
        }
    }

    private function evento(Pedido $pedido, User $usuario, string $tipo, string $descripcion): void
    {
        $pedido->eventos()->create(['usuario_id' => $usuario->id, 'tipo' => $tipo, 'descripcion' => $descripcion]);
    }

    private function error(string $mensaje): never
    {
        throw ValidationException::withMessages(['entrega' => $mensaje]);
    }
}
