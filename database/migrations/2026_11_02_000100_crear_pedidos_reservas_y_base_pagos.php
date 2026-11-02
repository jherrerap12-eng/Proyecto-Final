<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $tabla) {
            $tabla->engine = 'InnoDB';
            $tabla->id();
            $tabla->uuid('referencia')->unique();
            $tabla->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $tabla->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $tabla->string('cliente_nombre', 150);
            $tabla->string('cliente_telefono', 50);
            $tabla->text('cliente_direccion')->nullable();
            $tabla->string('modalidad', 30);
            $tabla->string('estado', 30)->default('borrador')->index();
            $tabla->string('estado_logistico', 30)->default('por_preparar');
            $tabla->decimal('total', 12, 2);
            $tabla->unsignedTinyInteger('porcentaje_deposito')->default(25);
            $tabla->text('observaciones')->nullable();
            $tabla->unsignedInteger('version')->default(1);
            $tabla->timestamp('confirmado_en')->nullable();
            $tabla->timestamp('cancelado_en')->nullable();
            $tabla->timestamps();
        });
        Schema::create('detalles_pedido', function (Blueprint $tabla) {
            $tabla->engine = 'InnoDB';
            $tabla->id();
            $tabla->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->string('codigo', 20);
            $tabla->string('nombre', 150);
            $tabla->decimal('costo', 10, 2);
            $tabla->decimal('precio', 10, 2);
            $tabla->unsignedInteger('version_producto');
            $tabla->timestamps();
            $tabla->unique(['pedido_id', 'producto_id']);
        });
        Schema::create('reservas', function (Blueprint $tabla) {
            $tabla->engine = 'InnoDB';
            $tabla->id();
            $tabla->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $tabla->foreignId('detalle_pedido_id')->unique()->constrained('detalles_pedido')->restrictOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            // Activas o pendientes de devolución conservan el bloqueo único.
            $tabla->foreignId('producto_bloqueado_id')->nullable()->unique()->constrained('productos')->restrictOnDelete();
            $tabla->string('tipo', 20);
            $tabla->string('estado', 30)->default('activa');
            $tabla->timestamp('liberada_en')->nullable();
            $tabla->timestamps();
        });
        Schema::create('eventos_pedido', function (Blueprint $tabla) {
            $tabla->engine = 'InnoDB';
            $tabla->id();
            $tabla->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $tabla->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $tabla->string('tipo', 40);
            $tabla->string('descripcion', 500);
            $tabla->timestamps();
        });
        // Contrato compartido. El compañero implementará registro/verificación de pagos.
        Schema::create('pagos', function (Blueprint $tabla) {
            $tabla->engine = 'InnoDB';
            $tabla->id();
            $tabla->uuid('clave_operacion')->unique();
            $tabla->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $tabla->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $tabla->foreignId('verificado_por')->nullable()->constrained('users')->restrictOnDelete();
            $tabla->decimal('monto', 12, 2);
            $tabla->string('metodo', 40);
            $tabla->string('referencia', 150)->nullable();
            $tabla->date('fecha_pago');
            $tabla->timestamp('verificado_en')->nullable();
            $tabla->timestamp('anulado_en')->nullable();
            $tabla->timestamps();
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE pagos ADD CONSTRAINT pagos_monto_positivo CHECK (monto > 0)');
            DB::statement('ALTER TABLE pedidos ADD CONSTRAINT pedidos_importes_validos CHECK (total >= 0 AND porcentaje_deposito BETWEEN 1 AND 100)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('eventos_pedido');
        Schema::dropIfExists('reservas');
        Schema::dropIfExists('detalles_pedido');
        Schema::dropIfExists('pedidos');
    }
};
