# Operación, respaldo y recuperación

Entorno publicado: Render Docker + Aiven MySQL. Actualizado: 2026-11-03.

## Actualizaciones

1. Revisar el PR y las migraciones antes de integrar en main. Respaldar la base antes de cambios de esquema.
2. Publicar el commit autorizado. Render construye la imagen y ejecuta `despliegue/iniciar.sh`.
3. El inicio prepara el CA para Apache, cachea configuración, ejecuta `migrate --force`, compila vistas y arranca Apache.
4. Esperar Live y comprobar ingreso, permisos, lectura/escritura y CSV. `/up` no sustituye pruebas de base de datos.

La reversión del código no revierte automáticamente el esquema ni los datos. Nunca ejecutar `migrate:fresh`, `db:wipe` o restaurar sobre la base activa para probar.

## Respaldo

Utilizar un cliente compatible con la versión de MySQL del proveedor y validar TLS con el certificado de Aiven. Guardar credenciales en un almacén privado o introducir la contraseña interactivamente; no escribirla en argumentos ni guardar volcados en Git.

Para una exportación lógica, incluir estructura y datos de todas las tablas y un snapshot transaccional consistente. Si se usa mysqldump para un servicio administrado, revisar opciones compatibles como `--single-transaction`, `--no-tablespaces` y `--set-gtid-purged=OFF`; verificar los permisos y la versión antes de ejecutar. No asumir que una copia se creó correctamente sin comprobar su resultado.

Guardar cada respaldo con fecha/versión en una ubicación privada fuera de Render. Propuesta operativa: copia antes de migraciones, después de cambios importantes y diariamente si hay operaciones reales; el encargado debe asignar responsable, frecuencia y retención. La propuesta no acredita que exista una tarea automática configurada.

## Prueba de restauración

1. Crear una base destino independiente y vacía; anotar explícitamente nombre y servidor.
2. Importar el respaldo con un cliente compatible sin apuntar a producción.
3. Conectar una instancia aislada de la aplicación a esa copia, con HTTPS/cookies ajustados al entorno de prueba.
4. Contrastar cantidades de registros y totales monetarios: pedidos, detalles, pagos vigentes, reservas y reembolsos; comprobar FK e historial.
5. Probar ingreso, consulta de pieza, pedido y reporte. Confirmar que las pruebas no generan transacciones reales ni notificaciones.
6. Registrar fecha, responsable, identificación del respaldo, destino, conteos/totales y resultado. Guardar la evidencia sin datos personales ni secretos.

El encargado comunicó que completó el cierre indicado después del despliegue. No hay un archivo de respaldo ni un acta de restauración incorporados al repositorio; adjuntar los datos concretos al registro privado y una evidencia sanitizada a la entrega.

## Recuperación de acceso

Primera cuenta o recuperación técnica: usar la configuración privada `.env.aiven` y `php artisan usuario:crear --env=aiven` o `php artisan usuario:restablecer-contrasena --env=aiven` según corresponda. Comprobar el destino antes de ejecutar. No cambiar el `.env` de XAMPP ni ejecutar pruebas de integración contra la base publicada. Si existe configuración cacheada local, eliminar esa caché antes de seleccionar otro archivo de entorno.

El cambio de contraseña de usuarios existentes puede realizarlo un administrador autorizado desde Usuarios. No regenerar APP_KEY para resolver errores de ingreso: invalidaría información cifrada y sesiones.

## Diagnóstico

| Síntoma | Comprobación |
|---|---|
| Error 500 | Render Logs → mensaje production.ERROR más reciente; mantener APP_DEBUG=false |
| Error SSL | CA correcto, Secret File, ruta configurada y copia legible por www-data |
| Columna faltante | Estado de migraciones y commit publicado; no borrar la base |
| Credenciales rechazadas | Cuenta de Aiven, estado activo y contraseña; no confundir con XAMPP |
| Lentitud inicial | Esperar arranque del servicio gratuito y revisar después los logs |
| Datos ausentes | Confirmar base seleccionada; la base local no se replica automáticamente |

APP_KEY y credenciales se custodian fuera de Git. Conservar una copia segura de la configuración necesaria para recuperar la aplicación, separada de la evidencia pública.
