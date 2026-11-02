<?php

namespace App\Models;

use App\Soporte\Importes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'porcentaje_deposito' => 'integer', 'version' => 'integer', 'confirmado_en' => 'datetime', 'cancelado_en' => 'datetime'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id')->withTrashed();
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'pedido_id');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'pedido_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'pedido_id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(EventoPedido::class, 'pedido_id');
    }

    public function pagadoCentavos(bool $bloquear = false): int
    {
        $consulta = $this->pagos()->whereNotNull('verificado_en')->whereNotNull('verificado_por')->whereNull('anulado_en');
        if ($bloquear) {
            $consulta->lockForUpdate();
        }

        return $consulta->get()->sum(fn (Pago $pago) => Importes::aCentavos($pago->monto));
    }

    public function depositoMinimoCentavos(): int
    {
        return intdiv(Importes::aCentavos($this->total) * $this->porcentaje_deposito + 99, 100);
    }

    public function estadoPago(): string
    {
        $pagado = $this->pagadoCentavos();

        return $pagado >= Importes::aCentavos($this->total) ? 'pagado' : ($pagado > 0 ? 'parcial' : 'pendiente');
    }
}
