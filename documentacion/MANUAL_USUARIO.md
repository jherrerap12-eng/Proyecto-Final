# Manual de usuario — Babybu Toys

Versión de cierre: 2026-11-03. Dirección pública: https://babybu-toys.onrender.com.

## Acceso y cuentas

Abrir la dirección pública e ingresar con el correo y contraseña asignados. No existe registro público. Si se olvidó la contraseña, solicitar al administrador que la restablezca desde Usuarios; el responsable técnico también dispone del comando privado de recuperación. El enlace del nombre de usuario permite cambiar la contraseña propia indicando la actual. Cerrar sesión al finalizar.

El servicio gratuito puede tardar en responder después de estar inactivo. Si aparece un error persistente, registrar hora y operación y comunicarlo al responsable; no repetir operaciones financieras sin consultar primero su historial.

| Operación sensible | Administrador | Supervisor | Operador |
|---|:---:|:---:|:---:|
| Administrar usuarios | Sí | No | No |
| Verificar/corregir/anular abonos | Sí | Sí | No |
| Registrar reembolsos | Sí | No | No |
| Ajustar existencias y cancelar pedidos | Sí | Sí | No |

Los tres roles consultan módulos y registran clientes, pedidos, abonos pendientes y entregas. La matriz describe el código actual; no implica separación obligatoria entre quien registra y quien verifica un pago. La aprobación formal de la matriz por el negocio debe adjuntarse por separado.

## Clientes

1. Entrar a Clientes → Registrar cliente.
2. Escribir nombre y teléfono; correo y dirección son opcionales en la ficha del cliente.
3. Guardar y consultar la ficha. Buscar por nombre, teléfono o correo desde el listado.
4. Editar cuando corresponda. La baja conserva el registro y el historial comercial.

## Inventario

1. Registrar cada pieza física con código manual único, nombre, costo y precio.
2. Utilizar códigos distintos incluso para juguetes iguales. La existencia de cada pieza es 0 o 1.
3. Buscar y filtrar el listado; abrir la ficha para consultar movimientos.
4. Los ajustes requieren permiso, motivo y disponibilidad compatible. Una pieza reservada no admite ajustes ni desactivación.
5. Desactivar conserva el historial; no reutilizar el código de una pieza anterior.

## Pedidos y reservas

1. Crear un pedido, seleccionar cliente y buscar piezas disponibles. Se pueden reunir varias piezas distintas.
2. Seleccionar modalidad contra entrega o depósito y guardar borrador. El borrador todavía no bloquea las piezas.
3. Revisar total y datos antes de confirmar. El servidor calcula los importes y conserva costos/precios históricos.
4. En modalidad depósito, registrar y verificar al menos el 25 % antes de confirmar; el mínimo se redondea al centavo superior.
5. Confirmar bloquea las piezas. Contra entrega usa reserva temporal; alcanzar el mínimo verificado permite promoverla a firme. No hay vencimiento automático configurado.

Si otra persona confirma la última pieza primero, la operación se rechaza y hay que recargar y seleccionar otra. Los cambios obsoletos también se rechazan para no sobrescribir trabajo ajeno.

## Pagos, correcciones y reembolsos

1. Abrir Pagos y seleccionar el pedido.
2. Registrar importe, fecha, método y referencia cuando corresponda. Un abono nuevo queda pendiente.
3. Un administrador o supervisor comprueba la recepción real y utiliza Verificar abono. Solo los abonos verificados vigentes reducen el saldo real.
4. Ante un error, utilizar Corregir o Anular con motivo. No eliminar registros: se conserva original, responsable e historial.
5. Una corrección no puede dejar una reserva firme activa por debajo del mínimo verificado. El sistema indicará si es necesario registrar otro abono o cancelar.
6. Un administrador puede registrar un reembolso parcial o total de un pedido cancelado, hasta el importe verificado no devuelto. Si hubo envío, primero debe confirmarse el regreso físico de las piezas.

Registrar una entrega no confirma un cobro. Registrar un reembolso documenta dinero realmente devuelto; no realiza una transferencia bancaria automática.

## Entregas y cancelaciones

1. Abrir la entrega de un pedido confirmado y marcarlo empacado.
2. Revisar destinatario, teléfono y dirección; elegir Forza o mensajería propia. Forza requiere guía/referencia.
3. Confirmar envío retira físicamente las piezas del inventario una sola vez.
4. Confirmar entrega únicamente cuando todas las piezas fueron recibidas. Se registra fecha y responsable.
5. Cancelar antes del envío libera la reserva. Después del envío, conserva el bloqueo hasta registrar el regreso físico desde Pedidos.

La versión actual maneja el paquete completo: no admite entregas o cancelaciones parciales ni consulta automática al transportista.

## Dashboard y reportes

El dashboard muestra estados logísticos, piezas y saldos. Tanto dashboard como reportes reconocen venta cuando el pedido está confirmado, entregado y completamente pagado con abonos verificados vigentes.

En Reportes seleccionar fechas y agrupación diaria, semanal o mensual. Las semanas comienzan el lunes y se utiliza America/Guatemala. Consultar ventas, costo histórico, ganancia bruta, cobros, reembolsos y cobro neto. Exportar el CSV requerido; si Excel no separa columnas, importar como UTF-8 separado por comas.

Ganancia bruta no equivale a utilidad neta: no descuenta todos los gastos operativos. Cobro neto es cobros verificados menos reembolsos por sus respectivas fechas. Cancelaciones y correcciones pueden modificar cifras históricas; no es un cierre contable inmutable.

## Capturas para la entrega

Adjuntar capturas propias de ingreso, panel, cliente, pieza, pedido/reserva, abono verificado, entrega, reporte y permisos. Usar datos de demostración y ocultar información personal. Las capturas no se incluyen automáticamente en este manual.
