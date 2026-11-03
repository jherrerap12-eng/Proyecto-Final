<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reembolso extends Model
{
    protected $table = 'reembolsos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'fecha_reembolso' => 'date'];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
