<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Revisar primero, sin mutar datos ni inventar pedidos para pagos huérfanos.
        $huerfanos = DB::table('pagos')->leftJoin('pedidos', 'pedidos.id', '=', 'pagos.pedido_id')->whereNull('pedidos.id')->count();
        if ($huerfanos > 0) {
            throw new RuntimeException('Integración detenida: hay '.$huerfanos.' pago(s) sin pedido válido. Respalda y concilia sus pedido_id; no se borraron ni inventaron registros.');
        }
        $legacy = Schema::hasColumn('pagos', 'importe');
        $gramatica = DB::getQueryGrammar();
        $monto = $gramatica->wrap('pagos.monto');
        $importe = $gramatica->wrap('pagos.importe');
        $columna = Schema::hasColumn('pagos', 'monto') ? ($legacy ? 'COALESCE('.$monto.', '.$importe.')' : $monto) : $importe;
        if (DB::table('pagos')->whereRaw($columna.' <= 0 OR '.$columna.' IS NULL')->exists()) {
            throw new RuntimeException('Hay pagos sin importe positivo; revisarlos antes de integrar.');
        }
        $saldos = DB::table('pagos')->join('pedidos', 'pedidos.id', '=', 'pagos.pedido_id');
        if (Schema::hasColumn('pagos', 'anulado_en')) {
            $saldos->whereNull('pagos.anulado_en');
        }
        if ($saldos->groupBy('pagos.pedido_id', 'pedidos.total')->havingRaw('SUM('.$columna.') > '.$gramatica->wrap('pedidos.total'))->exists()) {
            throw new RuntimeException('Hay abonos que superan el total del pedido; conciliar antes de integrar.');
        }
        Schema::table('pagos', function (Blueprint $tabla) {
            if (! Schema::hasColumn('pagos', 'monto')) {
                $tabla->decimal('monto', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('pagos', 'fecha_pago')) {
                $tabla->date('fecha_pago')->nullable();
            }
            if (! Schema::hasColumn('pagos', 'verificado_por')) {
                $tabla->foreignId('verificado_por')->nullable()->constrained('users')->restrictOnDelete();
            }
            if (! Schema::hasColumn('pagos', 'verificado_en')) {
                $tabla->timestamp('verificado_en')->nullable();
            }
            if (! Schema::hasColumn('pagos', 'anulado_en')) {
                $tabla->timestamp('anulado_en')->nullable();
            }
            if (! Schema::hasColumn('pagos', 'observaciones')) {
                $tabla->string('observaciones', 500)->nullable();
            }
        });
        if ($legacy) {
            DB::table('pagos')->whereNull('monto')->update(['monto' => DB::raw('importe')]);
            DB::table('pagos')->whereNull('fecha_pago')->update(['fecha_pago' => DB::raw('fecha')]);
            // Conservar originales, pero permitir nuevos registros canónicos sin duplicar columnas.
            Schema::table('pagos', function (Blueprint $tabla) {
                $tabla->decimal('importe', 10, 2)->nullable()->change();
                $tabla->date('fecha')->nullable()->change();
                $tabla->decimal('monto', 12, 2)->nullable(false)->change();
                $tabla->date('fecha_pago')->nullable(false)->change();
            });
            $nombreCheck = DB::getTablePrefix().'pagos_monto_integrado_positivo';
            $tieneCheck = DB::getDriverName() === 'mysql' && (int) DB::selectOne('SELECT COUNT(*) AS total FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = ? AND constraint_name = ?', [DB::getTablePrefix().'pagos', $nombreCheck])->total > 0;
            if (DB::getDriverName() === 'mysql' && ! $tieneCheck) {
                DB::statement('ALTER TABLE '.$gramatica->wrapTable('pagos').' ADD CONSTRAINT '.$gramatica->wrap($nombreCheck).' CHECK (monto > 0)');
            }
            // Los abonos legacy se conservan pendientes; nunca verificarlos automáticamente.
        }
        $tieneRelacion = collect(Schema::getForeignKeys('pagos'))->contains(fn ($clave) => in_array('pedido_id', $clave['columns'], true));
        if (! $tieneRelacion) {
            Schema::table('pagos', function (Blueprint $tabla) {
                $tabla->foreign('pedido_id')->references('id')->on('pedidos')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // La conciliación financiera no se revierte borrando columnas ni datos originales.
    }
};
