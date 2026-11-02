# Colaboración

## Nombres

Código propio en español sin tildes/ñ en identificadores: `Producto`, `Cliente`, `ControladorInventario`, `$existenciasDisponibles`, `confirmarPedido`, `clientes/`, `documentacion/`.

Conservar nombres predeterminados de Laravel/herramientas: `app`, `Models`, `Http`, `Controllers`, `routes`, `resources`, `database`, `tests`, `User`, `users`, `name`, `email`, `password`, `created_at`, `up`, `down`, `handle`, `rules`, `authorize`, archivos composer/package y README/CHECKLIST. Interfaz y documentación en español.

Tablas propias: español snake_case (`productos`, `detalles_pedido`). Declarar `$table` y claves explícitas cuando la inferencia inglesa no corresponda. Clases PascalCase; métodos/variables camelCase. No traducir APIs del framework.

## Ramas

`main`: base estable; `feature/inventario`: encargado; `feature/clientes`: compañero. Crear ramas desde main publicado y actualizado. La base inicial es una excepción de inicialización en main, su primer commit requiere revisión/autorización; módulos nunca directamente en main.

## Flujo

Issue con responsable → rama → cambios/pruebas → revisar diff/secretos → commit descriptivo → publicar rama → PR hacia main → revisión de otro integrante → integración → actualizar tablero.

No sobrescribir archivos ajenos, reescribir historial compartido ni usar force push. Coordinar rutas, plantilla y migraciones. Cada integrante usa su cuenta y commits auténticos. No subir `.env`, credenciales o datos reales.

Terminado: aceptación satisfecha, validación servidor y permisos, pruebas pertinentes, móvil revisado, PR revisado y documentación actualizada con evidencia.
