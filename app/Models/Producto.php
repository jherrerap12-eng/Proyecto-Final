<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
