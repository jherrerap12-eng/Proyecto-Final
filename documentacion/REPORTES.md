# Reportes del negocio

Implementación de RF11 y consultas RF12 en `feature/reportes`, desde `origin/main` integrado (`42fcffb`). No requiere nuevas tablas, dependencias ni cambios a operaciones de pedidos, pagos, inventario o entregas. Añade un enlace al menú y un acceso en el panel, sin implementar el dashboard que corresponde al otro integrante.

## Reglas acordadas

| Indicador | Cálculo |
|---|---|
| Venta (criterio único, acordado 2026-11-02) | Pedido con `estado=confirmado`, `confirmado_en` no nulo, total positivo, `estado_logistico=entregado` y suma de pagos válidos mayor o igual al total. Es la misma definición del panel (`ConsultarReportes::totalesHistoricos`) |
| Pago válido | `verificado_en` y `verificado_por` no nulos, `anulado_en` nulo |
| Fecha de venta | La más reciente entre confirmación, última verificación de los abonos válidos y entrega física (`envios.entregado_en`) |
| Ventas | Suma de `pedidos.total` de las ventas reconocidas dentro del rango |
| Costo histórico | Suma de `detalles_pedido.costo` de esas ventas |
| Ganancia bruta | Suma de `detalles_pedido.precio - detalles_pedido.costo` de esas ventas |
| Cobros | Suma de pagos válidos por `fecha_pago`, incluidos los de pedidos parciales, borradores con depósito y cancelados |
| Reembolsos | Suma de `reembolsos.monto` por `fecha_reembolso` |
| Cobro neto | Cobros − reembolsos del mismo período |

El esquema actual impide sobrepagos y congela total/detalles una vez que existen pagos, por lo que la última verificación vigente identifica la finalización del pago. Si se añaden sobrepagos, descuentos o reaperturas, revisar esta regla y la relación entre total y precios históricos antes de cambiar el contrato. Actualmente el total es la suma de precios históricos y cada detalle representa una pieza.

No se consultan precios/costos actuales de productos ni el nombre actual del cliente: se conserva `cliente_nombre` del pedido. Una baja lógica del cliente no elimina su historial. Se admiten ganancias negativas y se conserva su signo.

Un pedido gratuito sin abonos no cuenta como venta pagada. Borradores y cancelados no generan venta ni ganancia. Cancelar un pedido pagado lo excluye retroactivamente de ventas, pero conserva sus cobros; si se devuelve dinero, se registra como reembolso y se resta en el cobro neto. Un pedido pagado y aún no entregado, o entregado sin pagar completo, todavía no es venta. El reporte refleja los registros vigentes al consultar; no constituye un cierre contable inmutable. Una verificación posterior puede incorporar cobros a una fecha de pago anterior, y anulaciones/cancelaciones pueden cambiar períodos pasados.

La ganancia es bruta: no descuenta transporte, comisiones u otros gastos operativos y no debe presentarse como utilidad neta.

### Ejemplo

Pedido con dos piezas: precios Q60.00 y Q40.00, costos Q20.00 y Q10.00. Se reciben Q30.00 el 31 de octubre y Q70.00 el 2 de noviembre; el último abono se verifica el 3 de noviembre y el pedido ya estaba confirmado. Octubre muestra Q30.00 de cobros y ninguna venta; noviembre muestra Q70.00 de cobros, una venta de Q100.00, costo Q30.00 y ganancia bruta Q70.00. Registrar varios abonos no multiplica las piezas ni las ventas.

## Consultas e interfaz

- `GET /reportes` (`reportes.index`): pantalla protegida por `auth`. Todos los usuarios internos autenticados pueden consultarla; los permisos diferenciados se integrarán con el módulo del compañero.
- Filtros `desde` y `hasta`: fechas `Y-m-d`, obligatorias si se envían; predeterminan inicio y fin del mes actual. `hasta` debe ser igual o posterior a `desde`.
- `agrupacion`: `diaria` (predeterminada), `semanal` o `mensual`. Zona `America/Guatemala`; semanas lunes–domingo. Los timestamps actuales del proyecto usan esa hora local; si se migra el almacenamiento a UTC, adaptar los límites y agrupaciones.
- Ambos extremos de las fechas son inclusivos: internamente se usa un límite superior exclusivo al inicio del siguiente día. Los períodos parciales se recortan al rango seleccionado. La tabla agrupada solo muestra períodos con actividad; un rango vacío muestra totales cero y un mensaje.
- Resumen: ventas, costo histórico, ganancia bruta, cobros y cantidad de pedidos vendidos. Detalles de ventas y cobros con 20 filas por página y parámetros independientes `ventas_pagina` y `cobros_pagina`, conservando filtros.
- `GET /reportes/exportar.csv` (`reportes.exportar`): mismos filtros y `tipo=resumen|ventas|cobros` obligatorio. Exporta todos los registros filtrados aunque se esté viendo una página posterior.
- CSV con coma como separador, UTF-8 con BOM y saltos CRLF; importes con punto decimal y sin símbolo Q dentro de las celdas numéricas. Si Excel no separa columnas automáticamente, usar Datos → Desde texto/CSV y seleccionar coma/UTF-8.
- Textos de usuario que puedan interpretarse como fórmulas, incluso tras espacios/controles, llevan un apóstrofo protector. Números negativos de ganancia conservan su valor numérico. Comillas, comas y saltos se codifican mediante `fputcsv`.

## Organización e integridad

`SolicitudReporte` valida y proporciona los filtros; `ConsultarReportes` concentra las consultas y agrupaciones; `ExportarReporte` genera descargas por streaming. El controlador y las vistas presentan los resultados y no modifican datos.

Pagos y detalles se preagrupan independientemente por pedido antes de unirlos. Cada DECIMAL se convierte a centavos enteros antes de sumar; los acumulados PHP usan enteros de 64 bits y se formatean sin floats ni el límite de un importe individual. Se leen resultados en lotes de 500 para resumen y CSV. Cada consulta de pantalla/exportación se ejecuta dentro de una transacción de lectura; la consistencia entre consultas en MariaDB/MySQL depende del aislamiento habitual `REPEATABLE READ`. Una descarga posterior puede diferir de una pantalla anterior si otro usuario cambia operaciones entre ambas solicitudes.

## Uso local

La base compartida requiere pedidos y pagos integrados. Si esa integración aún no se aplicó en otra computadora, revisar y ejecutar las migraciones pendientes con PHP de XAMPP; reportes no aporta ninguna migración nueva. No usar `migrate:fresh` ni borrar datos existentes.

```powershell
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan serve --port=8001
```

Abrir `http://127.0.0.1:8001/reportes`, iniciar sesión y elegir fechas/agrupación. Descargar cada CSV y contrastar sus importes con el resumen de pantalla. Los abonos deben estar verificados desde Pagos para aparecer.

## Verificación

```powershell
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit
C:\xampp\php\php.exe vendor/laravel/pint/builds/pint --dirty --test
C:\xampp\php\php.exe tests/Integracion/verificar_reportes.php
```

Las pruebas funcionales cubren sesión, fechas inválidas/invertidas y parámetros de tipo incorrecto, mes predeterminado, períodos vacíos, abonos parciales/completos, pagos pendientes/anulados/sin verificador, borradores/cancelados/gratuitos, fecha de confirmación, cobros y ventas en meses distintos, fronteras inclusivas, semanas/meses recortados, históricos, pérdidas, grandes acumulados, paginación y CSV completos, BOM y protección de fórmulas.

La prueba explícita MariaDB/MySQL usa cinco tablas `TEMPORARY` privadas de su conexión (`pedidos`, `detalles_pedido`, `pagos`, `envios`, `reembolsos`) para contrastar consultas, agrupaciones, paginación y CSV con escenarios controlados. Incluye `estado_logistico`, entrega posterior al pago, un pedido pagado sin entregar, reembolsos en meses distintos y cobro neto negativo. También contrasta el criterio del dashboard con el de reportes. No inserta ni modifica registros del negocio. Las tablas se eliminan al finalizar, incluso cuando una comprobación falla, y desaparecen al cerrar esa conexión. Requiere entorno `local`, conexión `mysql`, permiso para tablas temporales y sin prefijo.

Los resultados automatizados no acreditan aprobación del negocio, revisión móvil manual, revisión de otro integrante ni publicación. No incluye PDF, gráficos, cierres históricos ni reglas de permisos diferenciados. El resumen y los CSV sí incluyen reembolsos y cobro neto.

### Resultado de esta entrega

- Suite completa: **62 pruebas y 624 aserciones correctas**, con PHP 8.2.12 de XAMPP y SQLite en memoria.
- Formato Pint y `git diff --check` correctos; dos rutas bajo `auth` registradas.
- Consultas y CSV comprobados en MariaDB local: escenario controlado con Q100.30 en ventas, Q30.31 de costo, Q69.99 de ganancia bruta y Q195.30 de cobros. Los cobros incluyen cancelados/borradores y corresponden a fechas distintas de la venta.
- Migraciones preexistentes de integración de pedidos y pagos aplicadas a la copia local sin crear migraciones para reportes.
- Entrega en `feature/reportes` para revisión mediante Pull Request; la aprobación e integración en main corresponden a la revisión del equipo.

### Actualización de verificación — 2026-11-03

Tras integrar entregas y reembolsos en main, se actualizó el esquema temporal y sus resultados esperados en `fix/verificacion-financiera`. La prueba pasa en **MariaDB 10.4.32**: dos ventas entregadas y pagadas, Q100.30 en ventas, Q30.31 de costo, Q69.99 de ganancia bruta, Q235.30 en cobros, Q5.00 en reembolsos y Q230.30 de cobro neto en noviembre. El reembolso de diciembre produce Q-5.00 netos sin alterar noviembre. Las tablas temporales impiden mezclar estos escenarios con datos reales.
