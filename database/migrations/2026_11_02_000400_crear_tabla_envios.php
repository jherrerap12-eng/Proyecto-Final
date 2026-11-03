<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envios', function (Blueprint $tabla) {
            $tabla->engine = 'InnoDB';
            $tabla->id();
            $tabla->foreignId('pedido_id')->unique()->constrained('pedidos')->restrictOnDelete();
            $tabla->string('destinatario', 150);
            $tabla->string('telefono', 50);
            $tabla->text('direccion')->nullable();
            $tabla->string('transportista', 30)->nullable()->index();
            $tabla->string('guia', 100)->nullable();
            $tabla->text('observaciones')->nullable();
            $tabla->text('observaciones_entrega')->nullable();
            $tabla->foreignId('empacado_por')->constrained('users')->restrictOnDelete();
            $tabla->foreignId('enviado_por')->nullable()->constrained('users')->restrictOnDelete();
            $tabla->foreignId('entregado_por')->nullable()->constrained('users')->restrictOnDelete();
            $tabla->foreignId('devuelto_por')->nullable()->constrained('users')->restrictOnDelete();
            $tabla->timestamp('empacado_en');
            $tabla->timestamp('enviado_en')->nullable()->index();
            $tabla->timestamp('entregado_en')->nullable()->index();
            $tabla->timestamp('devuelto_en')->nullable();
            $tabla->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envios');
    }
};
