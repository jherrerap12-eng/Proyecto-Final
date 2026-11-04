# Babybu Toys

Sistema administrativo interno para juguetes vendidos mediante lives y WhatsApp. Proyecto de Desarrollo Web, Universidad Mariano Gálvez de Guatemala.

**Aplicación publicada:** https://babybu-toys.onrender.com

## Estado actual

Los módulos están integrados en main: autenticación, usuarios/roles, dashboard, inventario por pieza, clientes, pedidos/reservas, pagos/verificación/correcciones, reembolsos, entregas y reportes con CSV. Frontend Blade/Bootstrap con búsqueda Fetch en pedidos. No existe registro público ni cuentas predeterminadas.

El encargado confirmó el funcionamiento publicado y la revisión manual. Las últimas verificaciones registradas son 139 pruebas/1231 aserciones, reportes en MariaDB y 15 escenarios concurrentes financieros aprobados. Ver [entrega final](documentacion/ENTREGA_FINAL.md) para procedencia, evidencias y datos pendientes de completar.

## Arquitectura y reglas

Laravel 12, PHP 8.2, Blade, Bootstrap 5, JavaScript y Vite. MariaDB de XAMPP en desarrollo; MySQL de Aiven en despliegue. Render ejecuta PHP/Apache mediante Docker y almacena sesiones y caché en la base.

Cada código identifica una pieza física con existencia 0/1. Reservar bloquea disponibilidad; enviar registra salida física. La reserva firme requiere 25 % verificado, redondeado al centavo superior. Cancelar después del envío mantiene el bloqueo hasta el regreso físico. Venta reconocida: pedido confirmado, entregado y completamente pagado. Ganancia bruta y cobros se distinguen; cobro neto descuenta reembolsos.

## Instalación local

Requisitos: PHP 8.2 con PDO MySQL, mbstring, OpenSSL, XML/DOM, fileinfo, ctype y zip; Composer 2, Node 22/24, npm y MySQL/MariaDB. Las pruebas requieren PDO SQLite. Ejecutar desde la raíz:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm ci
npm run build
```

Copiar `.env.example` solo en una instalación nueva. Crear una base vacía y un usuario dedicado; completar DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME y DB_PASSWORD en el `.env` privado. Después:

```powershell
php artisan migrate
php artisan usuario:crear
php artisan serve
```

Abrir http://127.0.0.1:8000. La primera cuenta creada será administradora; las contraseñas se introducen de forma oculta. En Windows, si PowerShell bloquea npm.ps1, usar `npm.cmd`.

Al actualizar una copia existente: instalar dependencias de los lockfiles si cambiaron, ejecutar `php artisan migrate` y recompilar recursos. No sobrescribir su `.env`, regenerar APP_KEY sin motivo ni ejecutar migrate:fresh sobre datos que deban conservarse.

## Verificación

```powershell
php vendor/phpunit/phpunit/phpunit --no-progress
php artisan route:list
php tests/Integracion/verificar_reportes.php
php tests/Integracion/verificar_concurrencia_financiera.php
```

PHPUnit usa SQLite en memoria según phpunit.xml. Los scripts de integración requieren entorno local y MySQL/MariaDB, crean tablas temporales o clones aislados y no deben ejecutarse contra la base publicada. Existen verificaciones adicionales de concurrencia para inventario, pedidos, pagos y entregas en tests/Integracion. No confundir pruebas MariaDB con certificación completa para todas las versiones MySQL.

## Despliegue y operación

[Render + Aiven](documentacion/DESPLIEGUE_RENDER.md). Configurar secretos en Render, certificado CA, APP_URL HTTPS, APP_DEBUG=false, sesiones de base y clave estable. El arranque aplica migraciones pendientes. Render Free puede dormir y su disco es efímero; archivos de negocio persistentes requieren almacenamiento externo si se incorporan posteriormente.

No subir `.env`, claves, credenciales ni volcados. [Respaldo y recuperación](documentacion/OPERACION_Y_RESPALDO.md). Los datos de XAMPP no se copian automáticamente a Aiven.

## Documentación de entrega

- [Manual de usuario](documentacion/MANUAL_USUARIO.md)
- [DER final](documentacion/DER_FINAL.md) y [arquitectura](documentacion/ARQUITECTURA.md)
- [PRD](documentacion/PRD.md) y [reglas](documentacion/REGLAS_NEGOCIO.md)
- [Entrega, evidencias y defensa](documentacion/ENTREGA_FINAL.md)
- [Checklist](CHECKLIST.md), [backlog](documentacion/BACKLOG.md) y [verificación histórica](documentacion/VERIFICACION.md)
- [Colaboración](CONTRIBUTING.md) y [usuarios/permisos](documentacion/USUARIOS_Y_PERMISOS.md)

Los registros históricos conservan el contexto de cada entrega. Para el estado de cierre consultar el checklist actual. La validación formal del negocio, los nombres del equipo, el tablero y las evidencias deben completarse con información auténtica.
