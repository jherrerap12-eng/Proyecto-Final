# Módulo de clientes

Implementa RF03 del PRD: registro, consulta, modificación, búsqueda por nombre/teléfono/correo, paginación de 10 y baja lógica. Reutiliza sesión, CSRF y plantilla Bootstrap. No crea cuentas de acceso para compradores.

## Datos y decisiones iniciales

- Nombre obligatorio, hasta 150 caracteres.
- Teléfono obligatorio, entre 8 y 15 dígitos; admite formato internacional, espacios, guiones y paréntesis. Se almacena como texto para conservar el formato.
- Correo opcional válido, hasta 254 caracteres.
- Dirección opcional, hasta 500 caracteres.
- Se permiten contactos compartidos: teléfono y correo no tienen restricción de unicidad.
- Cualquier usuario interno autenticado puede gestionar clientes. Permisos diferenciados siguen pendientes de acuerdo con el equipo.
- La baja usa `deleted_at`: oculta al cliente, conserva sus datos y no ofrece eliminación física. No hay interfaz de restauración en esta entrega.

Los campos y la política de baja son decisiones iniciales pendientes de validación del negocio. No se acredita lectura completa de los PDF en esta implementación; el DOCX estuvo bloqueado por otro proceso. Fuentes utilizadas: imágenes aportadas y PRD, arquitectura y backlog del repositorio.

## Archivos y responsabilidades

`app/Models/Cliente.php` representa la tabla y aplica la baja lógica. La migración `2026_11_02_000000_create_clientes_table.php` crea la tabla. `SolicitudCliente` valida en servidor. `ControladorClientes` coordina operaciones y búsquedas parametrizadas. Las vistas en `resources/views/clientes/` presentan listado, formulario y detalle. Las rutas resource están dentro del grupo `auth` de `routes/web.php`.

## Puesta en marcha local

Seguir primero la instalación del README si se acaba de clonar. Configurar `.env` y generar la clave solo si faltan, sin sobrescribir una configuración existente.

Desde la raíz, ejecutar cada comando por separado:

```powershell
php artisan migrate --pretend
php artisan migrate
npm ci
npm run build
php artisan serve
```

El primer comando permite revisar el SQL pendiente. El segundo aplica todas las migraciones pendientes a la base de `.env`; comprobar antes que sea la base local correcta. Los comandos npm instalan y compilan los recursos, y el último inicia el servidor local. Abrir `/ingresar`, acceder con una cuenta interna y entrar a Clientes desde el panel.

## Verificación

```powershell
php artisan test
php artisan route:list --path=clientes
```

Las pruebas usan SQLite en memoria e incluyen protección de todas las operaciones, registro, edición, validación, baja lógica, búsqueda, paginación y escape de HTML. No sustituyen la comprobación sobre MySQL/MariaDB ni la revisión visual en móvil.

Verificación de esta entrega: 13 pruebas y 113 aserciones correctas (suite completa); siete rutas de clientes registradas; formato Pint correcto. En esta copia faltaba `.env` y el PHP de terminal no cargaba SQLite. Se añadió una clave exclusivamente de pruebas a `phpunit.xml` y se ejecutó la suite con `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit`. Este comando habilita la extensión solo para ese proceso. No se aplicó la migración a una base local.

En PowerShell, si `npm` queda bloqueado por la política de scripts, usar `npm.cmd ci` y `npm.cmd run build`.

Compilación de recursos verificada con `npm.cmd run build` (Vite correcto). Revisión visual y conexión a MySQL/MariaDB pendientes.

En navegador: registrar un cliente, buscarlo, consultar su detalle, editar su teléfono y confirmar su baja desde el detalle. Comprobar errores al omitir nombre o introducir un correo inválido. Registrar más de diez clientes ficticios para revisar paginación; no usar datos reales como evidencia pública.

## Integración con pedidos

El compañero deberá crear `pedidos.cliente_id` como clave foránea a `clientes.id`, conservar el historial y acordar la consulta de clientes dados de baja (`withTrashed()` en la relación histórica). No se crea el modelo Pedido ni se modifica inventario en esta entrega.
