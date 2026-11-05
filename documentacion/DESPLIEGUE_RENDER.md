# Render gratuito y Aiven MySQL

Estado 2026-11-03: el encargado confirmó funcionamiento en https://babybu-toys.onrender.com. Se corrigió la consulta de migración para ONLY_FULL_GROUP_BY y el acceso al CA desde Apache. MYSQL_ATTR_SSL_CA conserva `/etc/secrets/ca.pem`; iniciar.sh crea una copia pública legible por www-data antes de cachear la configuración, manteniendo validación SSL.

El Dockerfile compila recursos con Node e instala PHP/Apache y dependencias de producción. El inicio aplica migraciones pendientes sin borrar datos. Usar una base dedicada a Babybu Toys. Los datos locales no se transfieren automáticamente.

## Configuración

1. Crear MySQL Free en Aiven y esperar a que esté Running.
2. Publicar los archivos de despliegue en GitHub después de revisarlos.
3. En Render: New → Web Service → conectar este repositorio, rama main, runtime Docker, plan Free. Dejar Docker Command sin sobrescribir. Health Check Path: `/up`.
4. En Environment → Secret Files, agregar `ca.pem` con el contenido del certificado descargado de Aiven. Render lo expone en `/etc/secrets/ca.pem`. No subir contraseñas, certificados ni `.env` a Git.
5. Agregar las siguientes variables antes de desplegar:

| Variable | Valor |
|---|---|
| APP_NAME | Babybu Toys |
| APP_ENV | production |
| APP_DEBUG | false |
| APP_KEY | Clave nueva y estable, generada privadamente |
| APP_URL | URL HTTPS asignada al servicio |
| APP_TIMEZONE | America/Guatemala |
| APP_LOCALE | es |
| DB_CONNECTION | mysql |
| DB_HOST | Host de Aiven |
| DB_PORT | Puerto de Aiven |
| DB_DATABASE | Base indicada por Aiven |
| DB_USERNAME | Usuario de Aiven |
| DB_PASSWORD | Contraseña de Aiven |
| MYSQL_ATTR_SSL_CA | /etc/secrets/ca.pem |
| SESSION_DRIVER | database |
| SESSION_ENCRYPT | true |
| SESSION_SECURE_COOKIE | true |
| CACHE_STORE | database |
| QUEUE_CONNECTION | sync |
| LOG_CHANNEL | stderr |
| LOG_LEVEL | warning |

Generar APP_KEY desde una terminal privada con `php artisan key:generate --show` y copiar el resultado directamente a Render. No ejecutar `key:generate` sin `--show` para esta tarea: cambiaría la clave local. No regenerar la clave en cada despliegue.

## Primera cuenta y datos

El servicio no incluye usuarios ni contraseñas predeterminados. Render Free no ofrece shell interactiva. Para crear la primera cuenta, configurar una copia local aislada del proyecto con la conexión TLS de Aiven y ejecutar `php artisan usuario:crear`; las migraciones deben estar aplicadas primero. No sustituir el `.env` de trabajo ni ejecutar pruebas de integración contra la base publicada. Este paso debe realizarse antes de dar por listo el despliegue.

Decidir si se inicia con base vacía o se transfieren datos locales mediante una exportación privada compatible y verificada; no importar volcados sin revisión ni guardar archivos SQL en Git.

## Comprobación y límites

Comprobar inicio/cierre de sesión, permisos de los tres roles, inventario, pedido, abono, entrega y reportes/CSV. Confirmar persistencia de datos y sesiones tras reinicio. El health check `/up` comprueba Laravel, no garantiza que todas las operaciones con base estén disponibles.

El almacenamiento local de Render es efímero: no guardar allí datos o archivos de negocio que deban persistir. El servicio gratuito puede dormir por inactividad. Programar respaldos privados de MySQL y comprobar restauración en otra base antes de utilizar datos reales.

La imagen se construyó y desplegó en Render según el registro compartido por el encargado; no se construyó localmente porque no hay Docker instalado. Cada nueva versión debe validarse con sus logs y pruebas. Ver OPERACION_Y_RESPALDO.md y ENTREGA_FINAL.md.
