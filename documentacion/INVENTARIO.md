# Inventario — Sprint 2

Rama: `feature/inventario`. Cada pieza física tiene código manual único, incluso si el nombre coincide con otra. Existencia 0 o 1. Dinero en quetzales con DECIMAL(10,2); costo/precio no negativos y hasta dos decimales. Código normalizado a mayúsculas, máximo 20 caracteres alfanuméricos/guion/guion bajo.

## Archivos y responsabilidades

- `database/migrations/2026_11_01_000100_crear_tablas_inventario.php`: crea productos y movimientos, claves foráneas, índices y restricciones de pieza única en MySQL/MariaDB; no elimina tablas existentes al migrar.
- `app/Models/Producto.php` y `MovimientoInventario.php`: tablas españolas y relaciones explícitas.
- `app/Http/Requests/SolicitudProducto.php` y `SolicitudMovimiento.php`: validación/normalización servidor.
- `app/Acciones/Inventario/GestionarInventario.php`: altas, cambios de datos, movimientos y desactivación con transacciones.
- `app/Http/Controllers/ControladorInventario.php`: formularios/listados/filtros y respuestas.
- `routes/inventario.php`: rutas cargadas dentro de auth desde web.php.
- `resources/views/inventario/`: listado, formulario, detalle/historial y errores.
- Cambios compartidos: una inclusión en web.php, enlace Inventario/ajuste responsive en plantilla, acceso desde panel y traducciones. Coordinar con clientes para conservar ambos enlaces y grupos de rutas al integrar.

## Cómo usarlo

Iniciar sesión → Inventario → Registrar juguete. Introducir código, nombre, descripción opcional, costo, precio y existencia inicial. El alta con existencia 1 genera apertura con responsable. Alta con 0 no crea un movimiento ficticio.

La ficha permite editar datos y registrar entrada (+1) o salida (−1), con motivo. Editar datos no modifica existencias. Un formulario obsoleto se rechaza; recargar y revisar antes de volver a guardar. Reenviar el mismo movimiento no lo aplica otra vez.

Desactivar reemplaza la baja física del CRUD: conserva código, existencias y movimientos; filtrar inactivos para reactivar. No se borran piezas ni se reutilizan códigos. Las salidas manuales no equivalen a ventas ni generan ganancias. Reservas, pedidos y sus reglas se integrarán después.

## Colaborador que recibe la rama integrada

Después de revisar/integrar el PR y actualizar su copia, ejecutar:

```powershell
php artisan migrate
```

Crea las tablas nuevas en su propia base. No usar migrate:fresh ni editar migraciones ya compartidas. No ejecutar migraciones que no se hayan revisado.

```powershell
npm run build
```

Construye los estilos y traducciones/vistas se leen desde Laravel. No agregar datos de producción en pruebas.

## Verificación

`php artisan test` usa SQLite en memoria: CRUD, normalización/duplicados, importes/cantidades, validación, filtros/paginación, inactivos, acceso protegido, escape HTML, reenvíos y edición obsoleta.

`php tests/Integracion/verificar_concurrencia_inventario.php` es una prueba explícita local MySQL/MariaDB: clona tres tablas con prefijo aleatorio `verif_...`, genera datos solo allí, mantiene una transacción abierta mientras otra conexión intenta retirar la misma pieza y comprueba una salida, un rechazo y existencia 0. También comprueba el CHECK 0/1. Elimina solo esas tablas aisladas al finalizar. Requiere permisos de creación/eliminación de tablas en la base local y no debe usarse en producción. No demuestra todavía reservas ni sobreventa de pedidos.

Las vistas se revisaron con datos en memoria a 1280 y 360 px; tablas con desplazamiento horizontal en móvil. No se insertaron productos de demostración en las tablas reales.

## Pendientes

El encargado confirmó que inventario funciona en navegador y autorizó el commit y publicación de `feature/inventario`. Pendientes: revisión de otro integrante, Pull Request e integración en main. Todos los usuarios autenticados tienen el mismo acceso inicial a inventario; permisos diferenciados continúan pendientes de definición. No se implementaron clientes, reservas, pagos, ventas ni indicadores del dashboard.
