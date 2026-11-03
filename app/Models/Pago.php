<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function verificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }

    public function anulador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    /** Abono original que este registro corrige. */
    public function corrige(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'corrige_pago_id');
    }

    /** Abono que reemplazó a este cuando se corrigió. */
    public function correccion(): HasOne
    {
        return $this->hasOne(Pago::class, 'corrige_pago_id');
    }
}
