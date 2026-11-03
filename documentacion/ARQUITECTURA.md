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
    PEDIDO ||--o| ENVIO : tiene
```

Producto: código/descripción/costo/precio. Cliente: contacto. Pedido: modalidad/estado. Detalle: cantidad y valores históricos. Reserva: tipo/cantidad/estado. Pago: importe/referencia. Envio: transportista/seguimiento. Movimiento: cantidad/tipo/motivo. Historial: estado/fecha/responsable.

Cada código representa una pieza física, con existencia 0/1, según decisión del encargado. La cardinalidad de envíos continúa pendiente.

## Inventario implementado

Además de las tablas estándar Laravel, existen `productos` y `movimientos_inventario`: Producto 1:N MovimientoInventario, User 1:N MovimientoInventario. Las claves `producto_id` y `usuario_id` restringen el borrado del historial. Índice único en código y referencia de movimiento. Costos/precios DECIMAL(10,2); activo y versión en producto. MySQL/MariaDB aplica CHECK de existencia 0/1 y consistencia antes/cambio/después.

`GestionarInventario` realiza cambios con transacciones y bloqueo de fila. La edición valida versión; la baja es desactivación sin borrar piezas. Una pieza reservada no admite ajustes manuales ni desactivación.

## Pedidos implementados — 2026-11-02

Cliente 1:N Pedido; Pedido 1:N DetallePedido/Reserva/EventoPedido/Pago. Cada detalle representa una pieza y conserva código/nombre/costo/precio. Una reserva retiene producto_id histórico y producto_bloqueado_id nullable único para bloquear disponibilidad. Confirmación/cancelación/devolución transaccionales con bloqueos y orden consistente. Mínimo histórico del 25 % por pedido y cálculo en centavos enteros.

Pago usa monto/fecha_pago, verificación con actor, saldo calculado y compatibilidad legacy; integrado en main (PR #3). Ver INTEGRACION_PEDIDOS_PAGOS.md. Reembolsos/anulaciones siguen pendientes; historial comercial usa eventos_pedido.

## Entregas implementadas en rama

Pedido 1:0..1 Envio, con pedido_id único. Envios conserva destino, transporte/guía, fechas de empaque/envío/entrega/regreso y FK de cada responsable a users. La fuente del estado logístico es pedidos.estado_logistico. GestionarEntregas coordina pedido, envío, piezas, reservas y movimientos en transacción con bloqueos. Solo enviar reduce existencia física; entregar no registra cobros. GestionarPedidos anota el regreso en envios/movimientos. Ver ENTREGAS.md.

Pruebas de acceso/reglas: SQLite en memoria. Concurrencia de inventario y pedidos comprobada con conexiones independientes MariaDB e InnoDB sobre tablas clonadas aisladas. MySQL 8 todavía no probado.
