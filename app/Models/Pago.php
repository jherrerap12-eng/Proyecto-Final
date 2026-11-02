<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = ['pedido_id', 'usuario_id', 'importe', 'metodo', 'referencia', 'fecha', 'observaciones', 'clave_operacion'];

    protected function casts(): array
    {
        return ['importe' => 'decimal:2', 'fecha' => 'date'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
