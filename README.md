# Babybu Toys

Aplicación administrativa interna de juguetes vendidos mediante lives y WhatsApp. Proyecto de Desarrollo Web, Universidad Mariano Gálvez de Guatemala.

## Estado real

Base Laravel 12, inicio/cierre de sesión, panel protegido y plantilla Blade/Bootstrap. Inventario implementado en la rama `feature/inventario`: una pieza por código, datos comerciales, entradas/salidas, historial, desactivación, filtros y paginación. Clientes todavía no implementado en esta rama. Sin cuentas predeterminadas ni registro público. Ver [CHECKLIST.md](CHECKLIST.md) y [guía de inventario](documentacion/INVENTARIO.md).

## Herramientas

PHP 8.2 compatible con Laravel 12, Composer 2, Node.js 22/24 y npm, Git, MySQL 8 recomendado. Extensiones PHP: PDO MySQL, mbstring, openssl, tokenizer, XML, ctype, fileinfo, curl y zip. SQLite (`pdo_sqlite`) únicamente para pruebas aisladas.

XAMPP local incluye MariaDB 10.4, no MySQL; conexión real pendiente. Laravel 12 se eligió por PHP 8.2 disponible. Revisar actualización de PHP/Laravel y soporte antes de operación prolongada.

## Instalación después de clonar

Abrir la raíz en VS Code y ejecutar los comandos por separado en PowerShell.

```powershell
composer install
```

Instala dependencias PHP exactas de `composer.lock` en `vendor/`. No modifica la base.

```powershell
Copy-Item .env.example .env
```

Crea configuración privada. Ejecutar solo si `.env` no existe; nunca sobrescribir configuración existente sin revisarla. Completar usuario y contraseña locales; no publicar este archivo.

```powershell
php artisan key:generate
```

Genera la clave privada en `.env`. No regenerarla si hay información cifrada en uso.

Crear base vacía `babybu_toys` con cotejamiento `utf8mb4_unicode_ci` desde el administrador MySQL y usuario con permisos únicamente sobre ella. Completar `DB_USERNAME` y `DB_PASSWORD` localmente; no guardar contraseñas en código, capturas o argumentos.

```powershell
php artisan migrate
```

Crea tablas pendientes en la base configurada. Revisar `DB_DATABASE` antes: modifica esa base. No usar `migrate:fresh` sobre datos que deban conservarse.

```powershell
php artisan usuario:crear
```

Solicita nombre, correo y contraseña oculta; guarda hash de contraseña. Repetir por cada usuario autorizado; no hay credenciales universales.

```powershell
npm ci
```

Instala dependencias frontend exactas de `package-lock.json` en `node_modules/`.

```powershell
npm run build
```

Compila Bootstrap/CSS/JavaScript en `public/build/`; debe finalizar sin errores.

```powershell
php artisan serve
```

Inicia servidor local; abrir `http://127.0.0.1:8000/ingresar`. Verificar ingreso, panel y salida. Detener con Ctrl+C. No es servidor de producción. Para recarga de estilos usar `npm run dev` en otra terminal.

## Verificación

```powershell
php artisan test
```

Ejecuta pruebas en SQLite en memoria, sin usar MySQL de `.env`. Incluye acceso protegido, autenticación, cierre, validación y límite de intentos. No demuestra concurrencia de inventario ni compatibilidad real MySQL.

```powershell
php artisan route:list
```

Comprobar rutas `/ingresar`, `/panel`, `/salir`. `php artisan list` debe incluir `usuario:crear`.

```powershell
vendor\bin\pint --test
```

Comprueba formato PHP sin modificar archivos. `vendor\bin\pint` aplica formato; revisar después los cambios.

## Documentación

[Colaboración](CONTRIBUTING.md), [PRD](documentacion/PRD.md), [Reglas](documentacion/REGLAS_NEGOCIO.md), [Arquitectura y DER](documentacion/ARQUITECTURA.md), [Backlog](documentacion/BACKLOG.md), [GitHub](documentacion/GITHUB.md), [Verificación](documentacion/VERIFICACION.md).

## Producción pendiente

Falta alojamiento, conexión MySQL real, HTTPS, respaldos y restauración. Raíz pública `public/`; `APP_ENV=production`, `APP_DEBUG=false`, URL real y `SESSION_SECURE_COOKIE=true`. Compilar recursos y verificar permisos de `storage/` y `bootstrap/cache/`. No publicar secretos, volcados, `vendor/` ni `node_modules/`.
