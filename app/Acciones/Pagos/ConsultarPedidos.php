<?php

namespace App\Acciones\Pagos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConsultarPedidos
{
    public function disponible(): bool
    {
        return Schema::hasTable('pedidos') && Schema::hasColumns('pedidos', ['id', 'cliente_id', 'total', 'estado']);
    }

    public function consulta()
    {
        return DB::table('pedidos')->join('clientes', 'clientes.id', '=', 'pedidos.cliente_id')
            ->select('pedidos.id', 'pedidos.total', 'pedidos.estado', 'clientes.nombre');
    }
}
