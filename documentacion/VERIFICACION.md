# Verificación real

## Integración de pedidos y pagos — 2026-11-02

Ramas origen: pedidos 62b3070 y pagos e545106. Unión local preparada en integracion/pedidos-pagos, con main intacta y commit/publicación pendientes. Se resolvieron conflictos en modelo Pago, menú, panel, rutas y checklist. Esquema canónico y migración puente conservan datos originales y no auto-verifican abonos.

52 pruebas/517 comprobaciones aprobadas. Migraciones 000200 y 000300 aplicadas localmente. Prueba real MariaDB: sobrepago, reenvío y cancelación concurrentes; promoción a firme tras verificar; conversión legacy repetible y sin pérdida. Los escenarios usan tablas clonadas aisladas y las eliminan al finalizar.

La prueba encontró una lectura antigua de saldo bajo REPEATABLE READ; se corrigió usando lecturas bloqueantes también sobre pagos. Ficha/listado de pagos revisados a 360/1280 px con datos en memoria; sin desbordamiento de página. No se generaron abonos ficticios en tablas de negocio. Falta revisión manual del encargado, aprobación del commit/publicación y PR; reembolsos/anulaciones y roles diferenciados siguen pendientes.

## Pedidos y reservas — 2026-11-02

Rama feature/pedidos desde main 5b0de65 (inventario/clientes integrados); base limpia antes de trabajar. Suite inicial 25 pruebas/258 comprobaciones. Regla validada: 25 % verificado para firme y regreso físico antes de liberar piezas enviadas.

Resultados: 38 pruebas/396 comprobaciones aprobadas sobre SQLite en memoria; migración nueva aplicada en MariaDB; scripts concurrentes de pedidos/inventario aprobados sobre clones aislados eliminados al terminar. En pedidos: dos conexiones, una confirmación, un rechazo y una reserva bloqueante. Sin fixtures insertados en tablas reales.

Vite compiló 61 módulos; Pint aplicado. Listado/formulario/detalle revisados en Chrome a 360/1280 px sin desbordamiento de página. Fetch probado con respuestas ficticias, selección preservada y total correcto. El encargado confirmó el funcionamiento y autorizó commit/publicación. Integración del módulo de pagos del compañero, entregas completas y PR siguen pendientes.

## Registro inicial — 2026-11-01

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

Estado posterior: primera cuenta y acceso confirmados; base publicada en `jherrerap12-eng/Proyecto-Final`, commit inicial `a4c5fed`, rama `feature/inventario` publicada. Invitación/clonación del compañero, revisión conjunta y despliegue siguen sin acreditarse. Clientes y sprints posteriores siguen pendientes. La conexión local es MariaDB, no una validación de MySQL 8.

## Inventario — verificación de la rama

- Regla confirmada por el encargado: un código por pieza física aunque sean iguales; existencia 0/1.
- Migración `2026_11_01_000100_crear_tablas_inventario`: aplicada, lote 2. Crea productos/movimientos con InnoDB y restricciones; tablas de usuarios conservadas.
- `php artisan test --compact`: 20 pruebas aprobadas y 181 comprobaciones (acceso e inventario), SQLite en memoria.
- `php tests/Integracion/verificar_concurrencia_inventario.php`: aprobado en MariaDB local, dos conexiones compitiendo por la misma pieza; una salida, otro rechazo, existencia final 0, CHECK activo. Solo tablas clonadas con prefijo aleatorio; eliminadas al finalizar. No se demuestra todavía concurrencia de pedidos/reservas.
- Pint sobre archivos propios: aprobado. `git diff --check`: sin errores de espacios.
- `npm run build`: aprobado, 60 módulos y manifiesto/CSS/JS generados.
- Capturas de listado/alta/detalle en Chrome invisible a 1280/360 px: sin desbordamiento de página; tablas desplazables. Datos ficticios en memoria, sin insertar en inventario real. Se corrigió traducción de paginación detectada en revisión.
- Ocho rutas de inventario dentro de autenticación; controles CSRF en formularios.
- El encargado confirmó el funcionamiento manual y autorizó commit/publicación de la rama. Pendientes: revisión de otro integrante, Pull Request e integración. Permisos por rol no definidos; acceso inicial igual para cuentas autenticadas.
