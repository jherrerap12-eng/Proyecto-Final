<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reembolsos')) {
            return;
        }
        // Devoluciones de dinero: registro propio, nunca abonos negativos ni anulaciones.
        Schema::create('reembolsos', function (Blueprint $tabla) {
            $tabla->engine = 'InnoDB';
            $tabla->id();
            $tabla->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $tabla->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $tabla->decimal('monto', 12, 2);
            $tabla->string('metodo', 40);
            $tabla->string('referencia', 100)->nullable();
            $tabla->date('fecha_reembolso')->index();
            $tabla->string('motivo', 500);
            $tabla->uuid('clave_operacion')->unique();
            $tabla->timestamps();
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE reembolsos ADD CONSTRAINT reembolsos_monto_positivo CHECK (monto > 0)');
        }
    }

    public function down(): void
    {
        // Historial financiero: no se elimina la tabla si ya tiene registros.
        if (Schema::hasTable('reembolsos') && DB::table('reembolsos')->doesntExist()) {
            Schema::drop('reembolsos');
        }
    }
};
