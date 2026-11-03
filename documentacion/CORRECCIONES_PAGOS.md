# Corrección y anulación de abonos — Persona 4

Rama `feature/correcciones-pagos`, creada desde `feature/usuarios-permisos` porque usa el permiso `corregir-pagos`. Integrar primero el PR de usuarios/permisos y después este.

## Principio

Un abono **nunca se edita ni se borra**. Toda corrección deja rastro: quién, cuándo y por qué. El saldo, el estado de pago y la reserva se recalculan solo desde abonos verificados y no anulados (regla existente de `Pedido::pagadoCentavos()`).

## Anular

`POST /pagos/abonos/{pago}/anular` con motivo (5–300 caracteres) y confirmación explícita.

- Guarda `anulado_en`, `anulado_por` y `motivo_anulacion`; registra el evento `pago_anulado` en el historial del pedido.
- Reenviar el formulario no repite la anulación.
- Un abono pendiente se puede anular siempre, incluso en pedidos cancelados (nunca contó como cobrado).
- Un abono **verificado de un pedido cancelado no se anula**: devolver ese dinero es un reembolso y necesita la regla del negocio (ver abajo).

## Corregir

`GET/POST /pagos/abonos/{pago}/corregir`. Crea un abono nuevo con los datos correctos (`corrige_pago_id` → original) y anula el original con el motivo «Corregido por el abono #N».

- El reemplazo queda **pendiente de verificación**. Quien tiene `verificar-pagos` puede marcar que ya comprobó el comprobante y registrarlo verificado en la misma operación.
- El importe corregido no puede superar el total del pedido; el importe del original cuenta como disponible.
- Idempotente por `clave_operacion` (UUID oculto); un abono ya anulado no se vuelve a corregir.
- Solo en pedidos que admiten pagos (confirmados o borradores con depósito). Si la corrección verificada alcanza el mínimo, promueve la reserva temporal a firme.

## Regla del mínimo para reservas firmes

Ni la anulación ni la corrección pueden dejar un pedido con reserva firme activa por debajo del 25 % verificado (redondeado al centavo superior, `depositoMinimoCentavos()`). Si ocurriría, la operación se rechaza sin cambios y se indica el flujo explícito:

1. registrar y verificar primero el abono correcto, y después anular el erróneo; o
2. cancelar el pedido (supervisor/administrador) y luego anular los abonos pendientes.

La comprobación se hace dentro de la transacción, con el pedido y todos sus pagos bloqueados (orden pedido → pagos, el mismo de `RegistrarPago`), para que un registro, verificación o cancelación simultáneos no la burlen.

## Permisos

`corregir-pagos`: administrador y supervisor (propuesta en `USUARIOS_Y_PERMISOS.md`). El operador recibe 403 y no ve los controles.

## Reembolsos — implementados (regla acordada el 2026-11-02)

`POST /pagos/pedidos/{pedido}/reembolsos`, formulario en la ficha de pagos de un pedido cancelado.

- Solo pedidos **cancelados** y solo dinero **verificado**: el máximo es lo verificado vigente menos lo ya reembolsado. Los abonos pendientes no cuentan.
- Puede ser **parcial o total**, en uno o varios registros (por ejemplo, retener el costo de envío si el cliente desiste).
- Si el pedido se envió, primero debe confirmarse el **regreso físico** de las piezas en la ficha del pedido.
- Solo el **administrador** (permiso `registrar-reembolsos`).
- Se guarda en la tabla propia `reembolsos` (monto, método, referencia, fecha, motivo, responsable, `clave_operacion`). Nunca es un abono negativo ni una anulación: los abonos originales no cambian.
- Evento `reembolso_registrado` en el historial del pedido; idempotente por `clave_operacion`; transacción con bloqueo pedido → pagos → reembolsos.
- En reportes se restan de los cobros por su fecha: **cobro neto = cobros verificados − reembolsos**.

Migración: `2026_11_03_000300_crear_tabla_reembolsos`. Pruebas: `tests/Feature/ReembolsosTest.php`.

## Migración

`2026_11_03_000200_agregar_anulacion_y_correccion_a_pagos`: agrega `anulado_por` (FK users), `motivo_anulacion` y `corrige_pago_id` (FK pagos, única). No modifica abonos existentes. Los anulados antes de esta migración (si los hubiera) aparecen como «Sin responsable registrado».

## Pruebas

`tests/Feature/CorreccionPagosTest.php`: anulación con historial, validación y no repetición; recálculo del saldo; rechazo cuando la reserva firme queda sin mínimo y aceptación cuando otro abono lo cubre; pedido cancelado; corrección pendiente y verificada; promoción de reserva; límite del total; idempotencia; permisos operador/supervisor; abono anulado no verificable ni corregible.

Pendiente: prueba concurrente en MySQL/MariaDB real (anulación simultánea con verificación o cancelación), siguiendo el patrón de `tests/Integracion/verificar_concurrencia_pagos.php`.
