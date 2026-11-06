<?php

namespace App\Acciones\Inventario;

use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GestionarInventario
{
    public function registrar(array $datos, User $usuario): Producto
    {
        return $this->protegerCodigo(function () use ($datos, $usuario) {
            return DB::transaction(function () use ($datos, $usuario) {
                $producto = Producto::create(collect($datos)->only(['codigo', 'nombre', 'descripcion', 'costo', 'precio'])->all());
                $producto->refresh();
                $cantidad = (int) $datos['existencias'];
                if ($cantidad > 0) {
                    $this->registrarMovimiento($producto, $usuario, $cantidad, 'apertura', 'Existencias iniciales', (string) Str::uuid());
                }

                return $producto->refresh();
            }, 3);
        });
    }

    public function actualizar(Producto $producto, array $datos): void
    {
        $this->protegerCodigo(function () use ($producto, $datos) {
            DB::transaction(function () use ($producto, $datos) {
                $producto = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
                $this->comprobarVersion($producto, (int) $datos['version']);
                $producto->fill(collect($datos)->only(['codigo', 'nombre', 'descripcion', 'costo', 'precio'])->all());
                $producto->version++;
                $producto->save();
            }, 3);
        });
    }

    public function ajustar(Producto $producto, array $datos, User $usuario): void
    {
        DB::transaction(function () use ($producto, $datos, $usuario) {
            $producto = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
            $cantidad = (int) $datos['cantidad'] * ($datos['tipo'] === 'salida' ? -1 : 1);
            $anterior = MovimientoInventario::where('referencia', $datos['referencia'])->first();

            // Reenviar el mismo formulario no vuelve a sumar ni restar unidades.
            if ($anterior) {
                if ($anterior->producto_id !== $producto->id || $anterior->usuario_id !== $usuario->id || $anterior->cantidad !== $cantidad || $anterior->motivo !== $datos['motivo']) {
                    throw ValidationException::withMessages(['referencia' => 'Esta referencia ya fue utilizada. Recarga la página.']);
                }

                return;
            }

            if (! $producto->activo) {
                throw ValidationException::withMessages(['cantidad' => 'Reactiva el producto antes de modificar sus existencias.']);
            }

            if ($producto->reservaActual()->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['cantidad' => 'La pieza está reservada. Gestiona su liberación desde el pedido.']);
            }

            $posteriores = $producto->existencias + $cantidad;
            if ($posteriores < 0 || $posteriores > 1) {
                throw ValidationException::withMessages(['cantidad' => 'Cada código identifica una pieza: no puede salir si ya no está disponible ni ingresar dos veces.']);
            }

            $this->registrarMovimiento($producto, $usuario, $cantidad, $datos['tipo'], $datos['motivo'], $datos['referencia']);
        }, 3);
    }

    public function cambiarEstado(Producto $producto, bool $activo, int $version): void
    {
        DB::transaction(function () use ($producto, $activo, $version) {
            // Coincidir con logística: pedido → pieza → reserva. Archivar no libera nada.
            $pedidoId = $producto->reservaActual()->value('pedido_id');
            $pedido = $pedidoId ? Pedido::whereKey($pedidoId)->lockForUpdate()->first() : null;
            $producto = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
            $this->comprobarVersion($producto, $version);
            $reserva = $producto->reservaActual()->lockForUpdate()->first();
            if ($reserva && (int) $reserva->pedido_id !== (int) $pedidoId) {
                throw ValidationException::withMessages(['version' => 'Cambió la reserva de la pieza. Recarga la página.']);
            }
            $entregada = $reserva && $reserva->estado === 'activa' && $producto->existencias === 0
                && $pedido?->estado === 'confirmado' && $pedido?->estado_logistico === 'entregado';
            if (! $activo && $reserva && ! $entregada) {
                throw ValidationException::withMessages(['version' => 'No puedes desactivar una pieza reservada. Gestiona primero el pedido.']);
            }
            $producto->activo = $activo;
            $producto->version++;
            $producto->save();
        }, 3);
    }

    private function registrarMovimiento(Producto $producto, User $usuario, int $cantidad, string $tipo, string $motivo, string $referencia): void
    {
        $anteriores = $producto->existencias;
        $producto->existencias += $cantidad;
        $producto->version++;
        $producto->save();
        $producto->movimientos()->create([
            'usuario_id' => $usuario->id,
            'referencia' => $referencia,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'existencias_anteriores' => $anteriores,
            'existencias_posteriores' => $producto->existencias,
            'motivo' => $motivo,
        ]);
    }

    private function comprobarVersion(Producto $producto, int $version): void
    {
        if ($producto->version !== $version) {
            throw ValidationException::withMessages(['version' => 'Otro usuario modificó este producto. Recarga la página y revisa los datos antes de guardar.']);
        }
    }

    private function protegerCodigo(callable $operacion): mixed
    {
        try {
            return $operacion();
        } catch (UniqueConstraintViolationException $excepcion) {
            // La restricción de la BD también protege dos altas simultáneas.
            throw ValidationException::withMessages(['codigo' => 'El código ya está registrado. Utiliza otro.']);
        }
    }
}
