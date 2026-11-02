# Reglas de negocio

## Fuente confirmada: definición del negocio

Tres usuarios internos. Código corto manual. Depósito previo confirmado produce reserva firme; contra entrega produce temporal. Ambas retiran disponibilidad. Cancelación devuelve inventario según el documento. Pago pendiente/parcial/pagado separado de logística: empacado, Forza/mensajería propia y entregado. Reportes por día/semana/mes y costos/ganancias.

## Pendiente, no aprobado

1. Código por pieza única o por producto/cantidad; formato, longitud y reutilización.
2. Depósito mínimo y quién lo verifica.
3. Reserva temporal: no asumir expiración automática; no existe plazo indicado.
4. Cancelación después del envío/entrega: propuesta liberar tras retorno físico, requiere aprobación para evitar disponibilidad ficticia.
5. Varios juguetes por pedido, envíos/cancelaciones parciales.
6. Costos por pieza/lote, descuentos, transporte/comisiones. Sin gastos completos solo hay ganancia bruta.
7. Fecha del reporte y comienzo de semana; ventas distintas de cobros.
8. Permisos de costos, ajustes, cancelaciones y correcciones de pagos.
9. Métodos de pago, reembolsos y sobrepagos.
10. GTQ y America/Guatemala propuestos a confirmar.

## Integridad propuesta, aún no implementada

Dinero DECIMAL; cantidades validadas. Reservar mediante transacción y bloqueo de filas en orden consistente, revalidando disponibilidad dentro del bloqueo. Confirmación/cancelación idempotentes. Precio/costo históricos por detalle. No borrar historial comercial.
