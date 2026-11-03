<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Envio extends Model
{
    protected $table = 'envios';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['empacado_en' => 'datetime', 'enviado_en' => 'datetime', 'entregado_en' => 'datetime', 'devuelto_en' => 'datetime'];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function empacador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'empacado_por');
    }

    public function remitente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function confirmador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entregado_por');
    }

    public function receptorDevolucion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'devuelto_por');
    }
}
