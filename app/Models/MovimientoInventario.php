<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = ['producto_id', 'usuario_id', 'referencia', 'tipo', 'cantidad', 'existencias_anteriores', 'existencias_posteriores', 'motivo'];

    protected function casts(): array
    {
        return ['producto_id' => 'integer', 'usuario_id' => 'integer', 'cantidad' => 'integer'];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
