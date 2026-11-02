# Reglas de negocio

## Fuente confirmada: definición del negocio

Tres usuarios internos. Código corto manual. Depósito previo confirmado produce reserva firme; contra entrega produce temporal. Ambas retiran disponibilidad. Cancelación devuelve inventario según el documento. Pago pendiente/parcial/pagado separado de logística: empacado, Forza/mensajería propia y entregado. Reportes por día/semana/mes y costos/ganancias.

## Pendiente, no aprobado

1. Formato final del código manual. Se implementó hasta 20 caracteres alfanuméricos, guion/guion bajo y mayúsculas; no se reutiliza al desactivar.
2. Depósito mínimo y quién lo verifica.
3. Reserva temporal: no asumir expiración automática; no existe plazo indicado.
4. Cancelación después del envío/entrega: propuesta liberar tras retorno físico, requiere aprobación para evitar disponibilidad ficticia.
5. Varios juguetes por pedido, envíos/cancelaciones parciales.
6. Costos por pieza/lote, descuentos, transporte/comisiones. Sin gastos completos solo hay ganancia bruta.
7. Fecha del reporte y comienzo de semana; ventas distintas de cobros.
8. Permisos de costos, ajustes, cancelaciones y correcciones de pagos.
9. Métodos de pago, reembolsos y sobrepagos.
10. GTQ y America/Guatemala propuestos a confirmar.

## Decisión validada por el encargado

Un código distinto por cada pieza física, aunque sean juguetes iguales. Inventario implementado con existencia 0 o 1 y costos/precios por pieza. No se agrupan piezas por nombre.

## Integridad

Implementado en inventario: dinero DECIMAL; entradas/salidas mediante transacción y bloqueo de fila; referencia única evita repetir el mismo movimiento; versión evita sobrescribir ediciones obsoletas; restricciones MySQL/MariaDB para existencia 0/1. Desactivación conserva la pieza y sus movimientos.

Pendiente en pedidos: reservas bajo bloqueo, confirmación/cancelación idempotentes y precio/costo históricos por detalle. No borrar historial comercial.
