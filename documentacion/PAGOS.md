# Pagos — preparación de integración

Rama `feature/pagos`. Implementación de RF09: listado de pedidos con total, abonado y saldo; búsqueda por cliente o número; paginación; historial con responsable; registro de abonos recibidos; estados pendiente/parcial/pagado calculados desde el saldo.

## Estado y alcance

El compañero desarrolla pedidos. No se creó ese módulo, no se alteró inventario y no hay pedidos ficticios en la base real. Mientras no exista el contrato descrito abajo, `/pagos` muestra integración pendiente y las operaciones de detalle/registro responden 409. Las pruebas usan una tabla de pedidos mínima exclusivamente en SQLite en memoria.

Se propone GTQ, métodos efectivo/transferencia/depósito, referencia bancaria obligatoria excepto efectivo, fecha no futura y hasta dos decimales. Confirmar estas decisiones con el negocio. La referencia bancaria no es única: puede identificar un comprobante que cubra varios pedidos. El UUID oculto identifica el envío del formulario y evita su duplicación; no sustituye la revisión humana del comprobante.

Todos los usuarios internos autenticados pueden registrar pagos. Los permisos específicos siguen pendientes. Los registros conservan responsable y fecha de registro, no ofrecen edición ni eliminación. Correcciones, reembolsos y manejo de sobrepagos requieren reglas acordadas antes de implementarlos. Este módulo no cobra automáticamente, no confirma reservas ni modifica logística.

## Contrato para el compañero que desarrolla pedidos

| Elemento | Requisito de integración |
|---|---|
| Tabla `pedidos` | `id` BIGINT UNSIGNED, `cliente_id` relacionado con `clientes.id`, `total` DECIMAL(10,2) no negativo, `estado` texto |
| Estado cancelado | Valor exacto `cancelado`; rechaza nuevos abonos |
| Total | Calculado en servidor a partir de detalles y precios históricos; nunca aceptar el total enviado por el navegador |
| Cambios de total/cancelación | Bloquear la misma fila del pedido en transacción; no reducir el total por debajo de lo abonado |
| Historial | Conservar pedidos y clientes con pagos; no borrar físicamente el pedido ni el cliente |
| Dinero | Máximo 99,999,999.99; no cambiar moneda sin adaptación explícita |
| Borrador | No exponer borradores como pedidos cobrables; si se incorpora ese estado, adaptar la consulta y validación de pagos |

`ConsultarPedidos` concentra el acceso al esquema y puede adaptarse al modelo definitivo. La comprobación de columnas evita errores por una tabla ausente; no certifica que todas las reglas del contrato estén satisfechas.

La tabla `pagos` tiene clave foránea a `users`, índice en `pedido_id` y UUID único. **La clave foránea `pagos.pedido_id → pedidos.id` queda pendiente:** no puede declararse contra una tabla que aún no existe. Al integrar pedidos, agregar una migración posterior a ambas tablas que use `foreign('pedido_id')->references('id')->on('pedidos')->restrictOnDelete()`. No editar una migración que ya se haya compartido/aplicado. Verificar registros huérfanos antes de agregar la restricción. Esta integración debe completarse antes de uso real.

## Seguridad y consistencia

Rutas bajo `auth`, CSRF en formularios, validación servidor, consultas parametrizadas y escape Blade. El cliente no envía el total ni el responsable. Cada abono bloquea la fila del pedido, suma lo abonado dentro de la transacción y compara con su total. El cálculo se hace en centavos enteros, se almacena DECIMAL y nunca se compara dinero con float. Un mismo UUID con los mismos datos no registra dos abonos; reutilizarlo con otros datos se rechaza. La base MySQL/MariaDB también exige importes positivos.

Los bloqueos dependen de InnoDB y de que pedidos use el mismo protocolo. Las pruebas SQLite no certifican concurrencia MySQL. Antes de cerrar la integración, probar con dos conexiones independientes dos abonos simultáneos que excedan juntos el saldo: solo el que quepa debe confirmarse. Probar también doble envío simultáneo con el mismo UUID y cancelación concurrente.

## Archivos principales

- `app/Acciones/Pagos/ConsultarPedidos.php`: adaptación del contrato de pedidos.
- `app/Acciones/Pagos/RegistrarPago.php`: transacción, saldo e idempotencia.
- `app/Models/Pago.php`, `SolicitudPago`, `Importe`: persistencia, validación y dinero.
- `routes/pagos.php`, `ControladorPagos`, `resources/views/pagos/`: navegación, listado y detalle/formulario.
- `database/migrations/2026_11_02_000200_crear_tabla_pagos.php`: tabla de abonos.
- `tests/Feature/PagosTest.php`: pruebas funcionales contra el contrato propuesto.

## Ejecución local

Con MySQL de XAMPP activo y `.env` configurado:

```powershell
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan serve --port=8001
```

La migración crea solamente `pagos`; iniciar sesión y abrir `http://127.0.0.1:8001/pagos`. Por ahora se verá el aviso de integración pendiente. Cuando pedidos esté integrado y se haya añadido la clave foránea, crear un pedido desde su módulo, abrir Pagos y registrar un abono menor al total; comprobar Parcial y saldo; completar el saldo y comprobar Pagado. No hace falta reinstalar dependencias para esta entrega.

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit
C:\xampp\php\php.exe vendor/laravel/pint/builds/pint --dirty --test
```

Las pruebas cubren sesión, módulo ausente, importes exactos, validación, sobrepagos, idempotencia, referencias, responsable, búsqueda/paginación y conservación del historial de un cliente dado de baja. Revisión visual móvil y pruebas de integración real con pedidos pendientes.

Verificado en esta entrega: suite completa de 32 pruebas y 336 aserciones correcta con PHP 8.2 de XAMPP; tabla `pagos` migrada en la base local; tres rutas protegidas registradas y formato Pint correcto. El módulo se entrega para revisión e integración con pedidos; no está listo para registrar pagos reales hasta completar las condiciones descritas arriba.
