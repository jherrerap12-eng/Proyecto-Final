<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // En instalaciones nuevas la tabla ya fue creada por la base de pedidos.
        // En instalaciones legacy existentes, la migración posterior conserva y adapta datos.
        if (Schema::hasTable('pagos')) {
            return;
        }
        Schema::create('pagos', function (Blueprint $tabla) {
            $tabla->id();
            // FK a pedidos pendiente de la migración del módulo del compañero.
            $tabla->unsignedBigInteger('pedido_id')->index();
            $tabla->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $tabla->decimal('importe', 10, 2);
            $tabla->string('metodo', 30);
            $tabla->string('referencia', 100)->nullable();
            $tabla->date('fecha');
            $tabla->string('observaciones', 500)->nullable();
            $tabla->uuid('clave_operacion')->unique();
            $tabla->timestamps();
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE pagos ADD CONSTRAINT pagos_importe_positivo CHECK (importe > 0)');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pagos') && ! Schema::hasColumn('pagos', 'monto')) {
            Schema::dropIfExists('pagos');
        }
    }
};
