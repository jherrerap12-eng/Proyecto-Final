<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Producto extends Model
{
    protected $table = 'productos';

    // Existencias, estado y versión solo se modifican desde la acción de negocio.
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'costo', 'precio'];

    protected function casts(): array
    {
        return ['costo' => 'decimal:2', 'precio' => 'decimal:2', 'existencias' => 'integer', 'activo' => 'boolean', 'version' => 'integer'];
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }

    public function reservaActual(): HasOne
    {
        return $this->hasOne(Reserva::class, 'producto_bloqueado_id');
    }

    public function situacionInventario(): string
    {
        $reserva = $this->reservaActual;
        if ($reserva) {
            if ($reserva->estado === 'devolucion_pendiente') {
                return 'Pendiente de regreso';
            }
            $estado = $reserva->pedido?->estado_logistico;
            if ($estado === 'entregado') {
                return 'Entregada';
            }
            if ($estado === 'enviado') {
                return 'Enviada';
            }

            return 'Reserva '.$reserva->tipo;
        }

        return $this->existencias === 1 ? ($this->activo ? 'Disponible' : 'En bodega') : 'Sin existencia';
    }

    public function esEntregadaArchivable(): bool
    {
        return $this->existencias === 0
            && $this->reservaActual?->estado === 'activa'
            && $this->reservaActual?->pedido?->estado === 'confirmado'
            && $this->reservaActual?->pedido?->estado_logistico === 'entregado';
    }
}
