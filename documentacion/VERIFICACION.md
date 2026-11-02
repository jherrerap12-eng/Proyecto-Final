# Verificación real → 2026-11-01

PHP 8.2.12, Composer 2.10.3, Node 24.13.1, npm 10.2.4, Git 2.53. Extensiones pdo_mysql/pdo_sqlite, mbstring, openssl, curl, zip y XML disponibles. Cliente XAMPP MariaDB 10.4.32; servidor no verificado.

Carpeta inicial vacía; repositorio superior de clases y su ignore no modificados. Esqueleto oficial laravel/laravel v12.0.0 descargado por Composer.

## Resultados ejecutados

- `composer install --no-interaction`: completado, Laravel framework 12.70.2, composer.lock generado.
- `npm install --no-audit --no-fund`: completado, package-lock.json generado.
- `composer validate --strict`: válido.
- `npm run build`: aprobado, Vite 6.4.5 y 60 módulos; manifiesto/CSS/JS generados. El primer intento fue bloqueado por permisos de lectura del entorno; la repetición autorizada terminó correctamente.
- `php artisan test --compact`: 8 pruebas aprobadas, 36 comprobaciones, después de aplicar formato.
- Pint en archivos PHP propios: aprobado.
- `php artisan route:list --except-vendor`: cinco rutas, ingreso/panel/salida registrados.
- HTTP GET `/ingresar`: 200, formulario y token CSRF presentes. No se realizó inspección visual en navegador ni prueba de usuario real.
- `.env` creado desde ejemplo y clave privada generada; no expuestos en resultados ni versionados.
- Puerto TCP 3306 activo; no demuestra acceso de usuario ni base creada.
- Git exclusivo inicializado con rama main sin commits y sin remoto; raíz exacta Proyecto Final.
- Git excluye `.env`, vendor, node_modules y public/build.

## Verificación de base local

El encargado confirmó la creación de su primera cuenta y el funcionamiento del acceso en navegador. Se comprobó una cuenta registrada mediante conteo, sin mostrar datos personales. Las otras dos cuentas y la instalación de un colaborador siguen pendientes.

Tras completar y guardar `.env`, `php artisan config:clear` permitió leer la configuración actual. `php artisan migrate:status` conectó y detectó que faltaba la tabla de migraciones. `php artisan migrate --no-interaction` creó la tabla de control y aplicó correctamente las tres migraciones estándar de usuarios/sesiones, cache y jobs. La consulta posterior mostró las tres como `Ran`, lote 1. No se registraron credenciales en este documento.

Pendientes: crear cuentas de aplicación, probar acceso real, publicar con autorización, invitar compañero, reproducir instalación y desplegar. No se han implementado inventario/clientes ni sprints posteriores. La conexión local no acredita todavía pruebas contra MySQL 8 ni concurrencia de inventario.
