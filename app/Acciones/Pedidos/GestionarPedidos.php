<?php

namespace App\Acciones\Pedidos;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Soporte\Importes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GestionarPedidos
{
    public function guardar(array $datos, User $usuario, ?Pedido $pedido = null): Pedido
    {
        try {
            return DB::transaction(function () use ($datos, $usuario, $pedido) {
                if ($pedido) {
                    $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
                    $this->version($pedido, (int) $datos['version']);
                    if ($pedido->estado !== 'borrador' || $pedido->pagos()->exists()) {
                        $this->error('Solo se edita un borrador sin pagos registrados.');
                    }
                } elseif ($anterior = Pedido::where('referencia', $datos['referencia'])->lockForUpdate()->first()) {
                    return $this->reenvio($anterior, $datos, $usuario);
                }
                $cliente = Cliente::whereKey($datos['cliente_id'])->lockForUpdate()->first();
                if (! $cliente) {
                    $this->error('Selecciona un cliente activo.');
                }
                $piezas = Producto::whereIn('id', $datos['productos'])->orderBy('id')->lockForUpdate()->get();
                if ($piezas->count() !== count($datos['productos'])) {
                    $this->error('La selección de piezas no es válida.');
                }
                $total = 0;
                foreach ($piezas as $pieza) {
                    $this->disponible($pieza);
                    $total += Importes::aCentavos($pieza->precio);
                }
                if ($datos['modalidad'] === 'deposito' && $total === 0) {
                    $this->error('Un pedido con depósito debe tener un total mayor que cero.');
                }
                $nuevo = ! $pedido;
                $pedido ??= new Pedido(['referencia' => $datos['referencia'], 'usuario_id' => $usuario->id, 'porcentaje_deposito' => 25]);
                $pedido->fill(['cliente_id' => $cliente->id, 'cliente_nombre' => $cliente->nombre, 'cliente_telefono' => $cliente->telefono, 'cliente_direccion' => $cliente->direccion, 'modalidad' => $datos['modalidad'], 'observaciones' => $datos['observaciones'] ?? null, 'total' => Importes::aDecimal($total)]);
                if (! $nuevo) {
                    $pedido->version++;
                }
                $pedido->save();
                // Solo se sustituyen detalles de borradores sin pagos ni reservas.
                $pedido->detalles()->delete();
                foreach ($piezas as $pieza) {
                    $pedido->detalles()->create(['producto_id' => $pieza->id, 'codigo' => $pieza->codigo, 'nombre' => $pieza->nombre, 'costo' => $pieza->costo, 'precio' => $pieza->precio, 'version_producto' => $pieza->version]);
                }
                $this->evento($pedido, $usuario, $nuevo ? 'creado' : 'editado', 'Borrador guardado; todavía no aparta piezas.');

                return $pedido->refresh();
            }, 3);
        } catch (UniqueConstraintViolationException $excepcion) {
            if (! $pedido && ($anterior = Pedido::where('referencia', $datos['referencia'])->first())) {
                return $this->reenvio($anterior, $datos, $usuario);
            }
            $this->error('La operación ya existe o una pieza fue modificada. Recarga la página.');
        }
    }

    public function confirmar(Pedido $pedido, int $version, User $usuario): void
    {
        try {
            DB::transaction(function () use ($pedido, $version, $usuario) {
                $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
                if ($pedido->estado === 'confirmado') {
                    return;
                }
                $this->version($pedido, $version);
                if ($pedido->estado !== 'borrador') {
                    $this->error('Este pedido no se puede confirmar.');
                }
                $cliente = Cliente::whereKey($pedido->cliente_id)->lockForUpdate()->first();
                if (! $cliente) {
                    $this->error('El cliente está de baja. Cambia el cliente del borrador.');
                }
                $detalles = $pedido->detalles()->orderBy('producto_id')->get();
                if ($detalles->isEmpty()) {
                    $this->error('El pedido necesita al menos una pieza.');
                }
                $piezas = Producto::whereIn('id', $detalles->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $firme = $pedido->pagadoCentavos(bloquear: true) >= max(1, $pedido->depositoMinimoCentavos());
                if ($pedido->modalidad === 'deposito' && ! $firme) {
                    $this->error('El depósito verificado todavía no cubre el 25 % mínimo del total. Registra y verifica el pago antes de confirmar.');
                }
                foreach ($detalles as $detalle) {
                    $pieza = $piezas->get($detalle->producto_id);
                    if (! $pieza) {
                        $this->error('Una pieza ya no existe.');
                    }
                    $this->disponible($pieza);
                    if ($pieza->version !== $detalle->version_producto && ! $pedido->pagos()->exists()) {
                        $this->error('Cambió una pieza desde que guardaste el borrador. Edita y guarda de nuevo para revisar sus precios y disponibilidad.');
                    }
                    $pedido->reservas()->create(['detalle_pedido_id' => $detalle->id, 'producto_id' => $pieza->id, 'producto_bloqueado_id' => $pieza->id, 'tipo' => $firme ? 'firme' : 'temporal', 'estado' => 'activa']);
                    $pieza->version++;
                    $pieza->save();
                }
                $pedido->fill(['estado' => 'confirmado', 'confirmado_en' => now(), 'cliente_nombre' => $cliente->nombre, 'cliente_telefono' => $cliente->telefono, 'cliente_direccion' => $cliente->direccion, 'version' => $pedido->version + 1])->save();
                $this->evento($pedido, $usuario, 'confirmado', 'Piezas apartadas con reserva '.($firme ? 'firme.' : 'temporal.'));
            }, 3);
        } catch (UniqueConstraintViolationException $excepcion) {
            $this->error('Otra operación ya reservó una de las piezas. El pedido no se confirmó.');
        }
    }

    public function cancelar(Pedido $pedido, int $version, string $motivo, User $usuario): void
    {
        DB::transaction(function () use ($pedido, $version, $motivo, $usuario) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            if ($pedido->estado === 'cancelado') {
                return;
            }
            $this->version($pedido, $version);
            if (! in_array($pedido->estado, ['borrador', 'confirmado'], true)) {
                $this->error('El estado actual no admite cancelación.');
            }
            $piezas = $this->bloquearPiezas($pedido);
            $requiereRegreso = in_array($pedido->estado_logistico, ['enviado', 'entregado'], true);
            foreach ($pedido->reservas()->whereNotNull('producto_bloqueado_id')->lockForUpdate()->get() as $reserva) {
                $reserva->estado = $requiereRegreso ? 'devolucion_pendiente' : 'liberada';
                if (! $requiereRegreso) {
                    $reserva->producto_bloqueado_id = null;
                    $reserva->liberada_en = now();
                }
                $reserva->save();
                $pieza = $piezas->get($reserva->producto_id);
                $pieza->version++;
                $pieza->save();
            }
            $pedido->fill(['estado' => 'cancelado', 'cancelado_en' => now(), 'version' => $pedido->version + 1])->save();
            $this->evento($pedido, $usuario, 'cancelado', $motivo.($requiereRegreso ? ' — Liberación pendiente del regreso físico.' : ' — Reservas liberadas.'));
        }, 3);
    }

    public function confirmarDevolucion(Pedido $pedido, User $usuario): void
    {
        DB::transaction(function () use ($pedido, $usuario) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            if ($pedido->estado !== 'cancelado') {
                $this->error('Solo se confirma el regreso de un pedido cancelado.');
            }
            $piezas = $this->bloquearPiezas($pedido);
            $reservas = $pedido->reservas()->where('estado', 'devolucion_pendiente')->lockForUpdate()->get();
            foreach ($reservas as $reserva) {
                $pieza = $piezas->get($reserva->producto_id);
                if ($pieza->existencias === 0) {
                    $pieza->existencias = 1;
                    $pieza->movimientos()->create(['usuario_id' => $usuario->id, 'referencia' => (string) Str::uuid(), 'tipo' => 'devolucion', 'cantidad' => 1, 'existencias_anteriores' => 0, 'existencias_posteriores' => 1, 'motivo' => 'Regreso físico del pedido #'.$pedido->id]);
                }
                $pieza->version++;
                $pieza->save();
                $reserva->update(['estado' => 'liberada', 'producto_bloqueado_id' => null, 'liberada_en' => now()]);
            }
            if ($reservas->isNotEmpty()) {
                $pedido->increment('version');
                $this->evento($pedido, $usuario, 'devolucion_confirmada', 'El operador confirmó el regreso físico de todas las piezas pendientes.');
            }
        }, 3);
    }

    // Pagos debe invocarlo dentro de su transacción, después de verificar un abono.
    public function promoverReserva(Pedido $pedido, User $usuario): void
    {
        DB::transaction(function () use ($pedido, $usuario) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            if ($pedido->estado === 'confirmado' && $pedido->pagadoCentavos(bloquear: true) >= max(1, $pedido->depositoMinimoCentavos())) {
                $cambios = $pedido->reservas()->where('estado', 'activa')->where('tipo', 'temporal')->update(['tipo' => 'firme']);
                if ($cambios) {
                    $this->evento($pedido, $usuario, 'reserva_firme', 'Los pagos verificados alcanzaron el mínimo requerido.');
                }
            }
        }, 3);
    }

    private function bloquearPiezas(Pedido $pedido)
    {
        return Producto::whereIn('id', $pedido->reservas()->whereNotNull('producto_bloqueado_id')->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    private function disponible(Producto $pieza): void
    {
        if (! $pieza->activo || $pieza->existencias !== 1 || $pieza->reservaActual()->lockForUpdate()->first()) {
            $this->error('La pieza '.$pieza->codigo.' ya no está disponible.');
        }
    }

    private function version(Pedido $pedido, int $version): void
    {
        if ($pedido->version !== $version) {
            $this->error('Otro usuario cambió el pedido. Recarga y revisa antes de continuar.');
        }
    }

    private function evento(Pedido $pedido, User $usuario, string $tipo, string $descripcion): void
    {
        $pedido->eventos()->create(['usuario_id' => $usuario->id, 'tipo' => $tipo, 'descripcion' => $descripcion]);
    }

    private function reenvio(Pedido $pedido, array $datos, User $usuario): Pedido
    {
        $ids = array_map('intval', $datos['productos']);
        sort($ids);
        $anteriores = $pedido->detalles()->orderBy('producto_id')->pluck('producto_id')->map(fn ($id) => (int) $id)->all();
        if ((int) $pedido->usuario_id !== $usuario->id || (int) $pedido->cliente_id !== (int) $datos['cliente_id'] || $pedido->modalidad !== $datos['modalidad'] || $ids !== $anteriores || ($pedido->observaciones ?? '') !== ($datos['observaciones'] ?? '')) {
            $this->error('La referencia ya pertenece a otro envío del formulario. Recarga la página.');
        }

        return $pedido;
    }

    private function error(string $mensaje): never
    {
        throw ValidationException::withMessages(['pedido' => $mensaje]);
    }
}
