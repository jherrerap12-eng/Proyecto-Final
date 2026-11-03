<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $tabla) {
            if (! Schema::hasColumn('pagos', 'anulado_por')) {
                $tabla->foreignId('anulado_por')->nullable()->constrained('users')->restrictOnDelete();
            }
            if (! Schema::hasColumn('pagos', 'motivo_anulacion')) {
                $tabla->string('motivo_anulacion', 500)->nullable();
            }
            if (! Schema::hasColumn('pagos', 'corrige_pago_id')) {
                // Un abono anulado por corrección tiene a lo sumo un reemplazo.
                $tabla->foreignId('corrige_pago_id')->nullable()->unique()->constrained('pagos')->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Historial financiero: no se eliminan anulaciones ni vínculos de corrección.
    }
};
