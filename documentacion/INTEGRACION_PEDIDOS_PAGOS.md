# Integración revisable de pedidos y pagos — 2026-11-02

Rama `integracion/pedidos-pagos`, desde pedidos `62b3070`, unión con pagos `e545106`. El encargado autorizó guardar y publicar esta integración, conservando los commits originales de ambos integrantes como padres del merge. Main no se modificó; revisión e integración del Pull Request pendientes.

## Diferencias resueltas

- Ambos módulos definían Pago y creaban pagos. Esquema común: monto, fecha_pago, verificado_en, verificado_por, anulado_en, observaciones y demás campos del contrato.
- Se conserva el formulario de pagos con campos importe/fecha, mapeados en servidor a monto/fecha_pago. No se asigna responsable ni verificación desde el navegador.
- Registrar produce pendiente. Verificar es POST separado con confirmación humana, fecha y actor. Solo pagos verificados/no anulados cuentan como abonados y promueven reserva firme al cubrir 25 %.
- El saldo registrable descuenta también pendientes: no se permite registrar otra vez un importe que ya está esperando verificación. El saldo pendiente comercial se calcula solo desde verificados.
- Pagos admite pedidos confirmados y borradores de depósito. Los borradores contra entrega deben confirmarse primero. Esto permite pagar el 25 % antes de confirmar un pedido con depósito.
- Consulta/historial usa contacto histórico del pedido, conservando acceso aunque se dé de baja al cliente.
- Menú, panel, rutas y modelos contienen ambos módulos; no se eligió todo un lado automáticamente.

## Concurrencia corregida y probada

Se encontró un caso real de REPEATABLE READ: una transacción con lectura previa podía usar una fotografía antigua al sumar pagos. Se corrigió con lecturas bloqueantes de pagos e idempotencia, además del bloqueo del pedido. Confirmación/promoción de reservas también obtiene pagos mediante lectura bloqueante. Reintentos de transacción ante interbloqueos: 3.

Orden: pedido → pagos cuando se calcula saldo/verifica; pedidos y reservas mantienen sus bloqueos de piezas por ID. Nunca escribir pagos fuera de la acción transaccional.

Referencia técnica: https://dev.mysql.com/doc/refman/8.0/en/innodb-transaction-isolation-levels.html . Las lecturas no bloqueantes pueden conservar una fotografía anterior, mientras las bloqueantes consultan el estado actual.

## Compatibilidad de migraciones sin pérdida

Las dos migraciones ya compartidas incluyen guardas mínimas para no crear pagos dos veces. La transformación de datos está en una migración nueva: `2026_11_02_000300_integrar_esquema_pagos`.

- Base canónica: agrega observaciones cuando falta y conserva pagos/verificaciones existentes.
- Base antigua del compañero: copia importe → monto y fecha → fecha_pago; conserva columnas originales, IDs, usuario, referencia, observaciones y fechas de registro. Las columnas originales pasan a nullable para admitir registros nuevos con el contrato común.
- No se verifican automáticamente abonos antiguos: aparecen pendientes para revisión humana.
- Agrega relación con pedidos y restricciones de importe. Si hay pagos huérfanos, importes inválidos o abonos que superan el total, se detiene antes de transformar datos y exige conciliación. No inventa pedidos ni borra pagos.
- El down de la migración puente no destruye columnas ni registros financieros. No usar migrate:fresh ni rollback como solución de incompatibilidades sobre una base con datos.

## Aplicar después de autorizar e integrar el PR

Cada integrante respalda su propia base y detiene escrituras durante la migración. Actualiza su copia desde main y ejecuta, por separado:

```powershell
php artisan migrate
```

Aplica la compatibilidad a su base local, manteniendo su .env y credenciales. Si se detiene por datos no conciliados, conservarlos y revisarlos; no eliminar las tablas para evadir el error.

```powershell
npm ci
npm run build
php artisan test
```

Instala versiones frontend bloqueadas, construye recursos y ejecuta pruebas aisladas. No copia usuarios/datos de otra computadora.

## Verificación ejecutada

- 52 pruebas/517 comprobaciones sobre SQLite en memoria; incluye esquema real, abonos antiguos, huérfanos y doble creación.
- MariaDB real mediante clones aislados: dos abonos de Q 60 sobre Q 100 aceptan uno; mismo UUID concurrente crea un registro; cancelación concurrente rechaza abono posterior. Verificación del abono promueve temporal a firme.
- La misma prueba convierte una tabla legacy, conserva su pago, ejecuta el puente dos veces y admite nuevos pagos sin auto-verificar los viejos.
- Listado/ficha de pagos a 360/1280 px sin desbordamiento de página, usando fixtures en memoria. El control de importe respeta máximo registrable. Tablas desplazables en móvil.
- Migraciones de integración aplicadas a la base local; no se insertaron fixtures en tablas reales.

Comando explícito local: `php tests/Integracion/verificar_concurrencia_pagos.php`. Crea clones con prefijo aleatorio, ejecuta escenarios y elimina solo esos clones al terminar; requiere permisos de creación/eliminación de tablas, nunca usarlo en producción. Devuelve error si algún escenario falla.

## Revisión del encargado

Abrir /pedidos → crear borrador con depósito → /pagos → registrar importe de al menos 25 % → verificar comprobante real → volver a pedido y confirmar firme. En contra entrega, confirmar temporal y luego verificar un abono suficiente debe promoverla a firme. Cancelado no admite nuevos pagos ni verificación de pendientes.

La verificación manual es responsabilidad del operador; el sistema no consulta un banco. Reembolsos, corrección/anulación de pagos, permisos diferenciados, entregas completas y reportes quedan pendientes. No usar datos ficticios como depósitos reales.
