<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'fecha_pago' => 'date', 'verificado_en' => 'datetime', 'anulado_en' => 'datetime'];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}
