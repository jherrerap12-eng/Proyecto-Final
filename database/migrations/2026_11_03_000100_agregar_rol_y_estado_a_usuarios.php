<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $tabla) {
            if (! Schema::hasColumn('users', 'rol')) {
                $tabla->string('rol', 20)->default('operador')->after('email');
            }
            if (! Schema::hasColumn('users', 'activo')) {
                $tabla->boolean('activo')->default(true)->after('rol');
            }
            if (! Schema::hasColumn('users', 'desactivado_en')) {
                $tabla->timestamp('desactivado_en')->nullable()->after('activo');
            }
        });

        // Las cuentas creadas antes de esta migración quedan como operador. Para no dejar el
        // sistema sin quien administre usuarios, la cuenta más antigua pasa a administrador.
        if (! DB::table('users')->where('rol', 'administrador')->exists()) {
            $primera = DB::table('users')->orderBy('id')->value('id');
            if ($primera !== null) {
                DB::table('users')->where('id', $primera)->update(['rol' => 'administrador']);
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_valido CHECK (rol IN ('administrador', 'supervisor', 'operador'))");
        }
    }

    public function down(): void
    {
        // No se eliminan roles ni el historial de desactivación: otras tablas registran responsables.
    }
};
