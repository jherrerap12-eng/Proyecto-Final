# Reglas de negocio

## Fuente confirmada: definición del negocio

Tres usuarios internos. Código corto manual. Depósito previo confirmado produce reserva firme; contra entrega produce temporal. Ambas retiran disponibilidad. Cancelación devuelve inventario según el documento. Pago pendiente/parcial/pagado separado de logística: empacado, Forza/mensajería propia y entregado. Reportes por día/semana/mes y costos/ganancias.

## Pendiente, no aprobado

1. Formato final del código manual. Se implementó hasta 20 caracteres alfanuméricos, guion/guion bajo y mayúsculas; no se reutiliza al desactivar.
2. Quién puede verificar depósitos; mínimo confirmado: 25 % del total.
3. Reserva temporal: no asumir expiración automática; no existe plazo indicado.
4. Procedimiento logístico de regreso/reembolsos. El encargado confirmó liberar tras regreso físico si ya se envió o entregó.
5. Varios juguetes por pedido, envíos/cancelaciones parciales.
6. Costos por pieza/lote, descuentos, transporte/comisiones. Sin gastos completos solo hay ganancia bruta.
7. Fecha del reporte y comienzo de semana; ventas distintas de cobros.
8. Permisos de costos, ajustes, cancelaciones y correcciones de pagos.
9. Métodos de pago, reembolsos y sobrepagos.
10. GTQ y America/Guatemala propuestos a confirmar.

## Decisión validada por el encargado

Un código distinto por cada pieza física, aunque sean juguetes iguales. Inventario implementado con existencia 0 o 1 y costos/precios por pieza. No se agrupan piezas por nombre.

2026-11-02: reserva firme requiere 25 % verificado, redondeado al centavo superior. Cancelar después de envío/entrega conserva bloqueo hasta regreso físico. Se admiten varias piezas distintas por pedido, sin entregas/cancelaciones parciales en esta versión.

## Integridad

Implementado en inventario: dinero DECIMAL; entradas/salidas mediante transacción y bloqueo de fila; referencia única evita repetir el mismo movimiento; versión evita sobrescribir ediciones obsoletas; restricciones MySQL/MariaDB para existencia 0/1. Desactivación conserva la pieza y sus movimientos.

Implementado en pedidos: reservas bajo bloqueo e índice único de pieza bloqueada, operaciones idempotentes y precio/costo históricos por detalle. Disponibilidad es activo + existencia 1 + sin reserva bloqueante. Registro/verificación de pagos y ciclo completo de entregas siguen pendientes. No borrar historial comercial.
