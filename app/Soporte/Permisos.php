<?php

namespace App\Soporte;

/**
 * Matriz única de roles y permisos. Propuesta pendiente de validación con el negocio:
 * cambiar aquí si el encargado decide otra distribución; las rutas, vistas y pruebas la consultan.
 */
final class Permisos
{
    public const ROLES = [
        'administrador' => 'Administrador',
        'supervisor' => 'Supervisor',
        'operador' => 'Operador',
    ];

    public const PERMISOS = [
        'administrar-usuarios' => ['administrador'],
        'verificar-pagos' => ['administrador', 'supervisor'],
        'corregir-pagos' => ['administrador', 'supervisor'],
        'ajustar-inventario' => ['administrador', 'supervisor'],
        'cancelar-pedidos' => ['administrador', 'supervisor'],
    ];

    public static function permite(?string $rol, string $permiso): bool
    {
        return $rol !== null && in_array($rol, self::PERMISOS[$permiso] ?? [], true);
    }

    public static function etiqueta(?string $rol): string
    {
        return self::ROLES[$rol] ?? 'Sin rol';
    }
}
