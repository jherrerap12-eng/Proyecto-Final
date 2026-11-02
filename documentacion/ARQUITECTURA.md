# Arquitectura y DER conceptual

Monolito Laravel MVC + Blade + Bootstrap 5 + JavaScript/Fetch + MySQL. Laravel 12 por PHP 8.2; revisar actualización/soporte futuro. Vite construye recursos; archivos lock compartidos. Sesiones/cache en archivos y cola síncrona para un servidor inicial. Sin React/Vue, API pública o WebSockets.

Flujo: navegador → rutas/middleware → controlador/validación → acción de negocio → Eloquent/MySQL → Blade/JSON. Controladores breves, Form Requests en módulos, acciones futuras en `app/Acciones/`, Policies cuando se acuerden permisos. Archivos propios en español, convenciones Laravel preservadas.

Autenticación con sesión, sin registro público; `User`, `users` y campos de autenticación predeterminados conservados. Fetch no garantiza sincronía instantánea entre pantallas: la disponibilidad se revalida al confirmar en servidor.

## DER inicial, no tablas implementadas

```mermaid
erDiagram
    CLIENTE ||--o{ PEDIDO : realiza
    USER ||--o{ PEDIDO : registra
    PEDIDO ||--|{ DETALLE_PEDIDO : contiene
    PRODUCTO ||--o{ DETALLE_PEDIDO : aparece
    PRODUCTO ||--o{ MOVIMIENTO_INVENTARIO : registra
    DETALLE_PEDIDO ||--o{ RESERVA : origina
    PEDIDO ||--o{ PAGO : recibe
    USER ||--o{ PAGO : registra
    PEDIDO ||--o{ HISTORIAL_ESTADO : conserva
    PEDIDO ||--o{ ENVIO : gestiona
```

Producto: código/descripción/costo/precio. Cliente: contacto. Pedido: modalidad/estado. Detalle: cantidad y valores históricos. Reserva: tipo/cantidad/estado. Pago: importe/referencia. Envio: transportista/seguimiento. Movimiento: cantidad/tipo/motivo. Historial: estado/fecha/responsable.

Cada código representa una pieza física, con existencia 0/1, según decisión del encargado. La cardinalidad de envíos continúa pendiente.

## Inventario implementado

Además de las tablas estándar Laravel, existen `productos` y `movimientos_inventario`: Producto 1:N MovimientoInventario, User 1:N MovimientoInventario. Las claves `producto_id` y `usuario_id` restringen el borrado del historial. Índice único en código y referencia de movimiento. Costos/precios DECIMAL(10,2); activo y versión en producto. MySQL/MariaDB aplica CHECK de existencia 0/1 y consistencia antes/cambio/después.

`GestionarInventario` realiza cambios con transacciones y bloqueo de fila. La edición valida versión; la baja es desactivación sin borrar piezas. El diagrama anterior conserva entidades futuras de pedidos/reservas, aún no implementadas.

Pruebas de acceso: SQLite en memoria. Concurrencia futura: MySQL y conexiones independientes, no asumir que SQLite demuestra bloqueo InnoDB.
