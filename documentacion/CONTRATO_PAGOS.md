# Integración para el compañero — pagos

El contrato se implementó al preparar la unión de feature/pedidos y feature/pagos en `integracion/pedidos-pagos`. Registro, verificación, consultas y promoción de reserva están adaptados y probados; revisión manual, commit/publicación y PR pendientes. No duplicar tablas ni integrar la versión antigua de pagos sin las adaptaciones. Ver INTEGRACION_PEDIDOS_PAGOS.md.

## Modelo Pago y tabla pagos

| Campo | Contrato |
|---|---|
| clave_operacion | UUID único, evita duplicación por reenvío |
| pedido_id | Pedido existente |
| usuario_id | Usuario que registra, tomado de sesión |
| verificado_por | Usuario verificador, nullable hasta verificar |
| monto | DECIMAL(12,2), mayor que cero |
| metodo | Método acordado con negocio, máximo 40 caracteres |
| referencia | Referencia externa opcional, máximo 150 caracteres |
| fecha_pago | Fecha del abono |
| verificado_en | Null hasta verificación real |
| anulado_en | Null mientras sea vigente |
| id/created_at/updated_at | Convenciones Laravel |

Usar Form Requests y listas explícitas. No asignar usuario, verificador ni fechas de verificación/anulación desde entradas arbitrarias del navegador. No registrar pagos ficticios ni marcar verificado automáticamente por guardar el formulario. Definir quién puede verificar/anular.

## Flujo transaccional

1. Abrir transacción y bloquear el pedido con `Pedido::whereKey(...)->lockForUpdate()->firstOrFail()`.
2. Dentro del bloqueo revisar estado, clave_operacion y saldo. Rechazar pedidos cancelados, importes no positivos, exceso de decimales y sobrepagos, incluso simultáneos.
3. Reenvío con misma clave y mismos datos: no duplicar. Misma clave/datos distintos: rechazar.
4. Al verificar, establecer verificado_en/verificado_por en servidor y llamar `app(GestionarPedidos::class)->promoverReserva($pedido, $usuario)` dentro de la misma transacción. Esto promueve reservas temporales confirmadas al alcanzar el mínimo; no confirma borradores automáticamente.
5. Conservar historial. No borrar pagos, reembolsar al cancelar ni representar reembolsos como abonos negativos.

Orden compartido de bloqueos: pedido → piezas por ID → reservas, cuando corresponda. Usar `Importes::aCentavos()` y `aDecimal()`; no calcular dinero con float. `Pedido::pagadoCentavos()`, `depositoMinimoCentavos()` y `estadoPago()` ya existen. Solo cuentan pagos con verificado_en y verificado_por completos, no anulados.

Política validada: 25 % guardado por pedido, redondeado al centavo superior. Total Q 100.01 requiere Q 25.01. Un borrador con pagos ya registrados no se edita, para conservar el importe acordado.

No permitir anular/corregir pagos dejando una reserva firme sin mínimo; acordar flujo explícito, por ejemplo cancelar el pedido antes. Reembolsos y permisos diferenciados siguen pendientes de definición.

## Entrega

Controlador, Form Requests, acción y vistas propios en español; rutas en routes/pagos.php dentro de auth. Coordinar enlace del menú y acceso desde ficha del pedido. Probar saldo, verificación, anulaciones, reenvíos, simultaneidad y promoción de reserva. Entregar PR hacia main con revisión de otro integrante.
