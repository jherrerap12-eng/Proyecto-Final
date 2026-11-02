<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $tabla) {
            $tabla->engine = 'InnoDB';
            $tabla->id();
            $tabla->string('codigo', 20)->unique();
            $tabla->string('nombre', 150);
            $tabla->text('descripcion')->nullable();
            $tabla->decimal('costo', 10, 2);
            $tabla->decimal('precio', 10, 2);
            $tabla->unsignedInteger('existencias')->default(0);
            $tabla->boolean('activo')->default(true)->index();
            $tabla->unsignedInteger('version')->default(1);
            $tabla->timestamps();
        });

        Schema::create('movimientos_inventario', function (Blueprint $tabla) {
            $tabla->engine = 'InnoDB';
            $tabla->id();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $tabla->uuid('referencia')->unique();
            $tabla->string('tipo', 20);
            $tabla->integer('cantidad');
            $tabla->unsignedInteger('existencias_anteriores');
            $tabla->unsignedInteger('existencias_posteriores');
            $tabla->string('motivo', 255);
            $tabla->timestamps();
            $tabla->index(['producto_id', 'id']);
        });

        // MySQL 8 / MariaDB 10.4: la BD también protege la regla de pieza única.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE productos ADD CONSTRAINT productos_pieza_unica CHECK (existencias IN (0, 1) AND costo >= 0 AND precio >= 0)');
            DB::statement('ALTER TABLE movimientos_inventario ADD CONSTRAINT movimientos_pieza_unica CHECK (cantidad IN (-1, 1) AND existencias_anteriores IN (0, 1) AND existencias_posteriores IN (0, 1) AND existencias_posteriores = existencias_anteriores + cantidad)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('productos');
    }
};
