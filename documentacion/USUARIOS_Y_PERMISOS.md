# Usuarios y permisos — Persona 4

Rama `feature/usuarios-permisos`, desde main `42fcffb`. Responsable: Persona 4 (usuarios, permisos y pagos).

## Propuesta de roles para validar con el negocio

Las tres cuentas internas se reparten en tres roles. **Es una propuesta**: el encargado debe confirmarla con el negocio. Cambiarla solo requiere editar `app/Soporte/Permisos.php`; rutas, vistas y pruebas la consultan.

| Operación | Administrador | Supervisor | Operador |
|---|:-:|:-:|:-:|
| Consultar todo; registrar clientes, pedidos, abonos pendientes y entregas | ✔ | ✔ | ✔ |
| Verificar pagos (`verificar-pagos`) | ✔ | ✔ | — |
| Corregir o anular abonos (`corregir-pagos`, rama de correcciones) | ✔ | ✔ | — |
| Ajustar inventario manualmente (`ajustar-inventario`) | ✔ | ✔ | — |
| Cancelar pedidos (`cancelar-pedidos`) | ✔ | ✔ | — |
| Administrar usuarios (`administrar-usuarios`) | ✔ | — | — |

Sugerencia de asignación: dueña → Administrador; encargado de pagos/inventario → Supervisor; quien atiende lives/WhatsApp → Operador. Razón: quien registra un abono no debería ser quien confirma que el dinero llegó.

Preguntas abiertas para el negocio: ¿el supervisor puede anular abonos ya verificados o solo el administrador? ¿desactivar piezas de inventario también debe restringirse? (hoy solo el ajuste manual +1/−1 está restringido).

## Administración de cuentas

- **Alta**: `/usuarios/create` (solo administrador) o `php artisan usuario:crear`. La primera cuenta del sistema se crea siempre como administrador. Contraseña mínima de 12 caracteres con letras y números, guardada con hash.
- **Edición**: nombre, correo y rol. Rol, estado y fechas nunca se aceptan por asignación masiva desde el navegador.
- **Desactivación**: no se borra la cuenta (pagos, eventos y movimientos guardan su responsable). La cuenta desactivada no puede ingresar y sus sesiones abiertas se cierran en la siguiente solicitud. Se puede reactivar.
- **Protecciones**: nadie puede desactivarse ni quitarse el rol de administrador a sí mismo; siempre debe quedar al menos un administrador activo (comprobado con bloqueo de filas para cambios simultáneos).

## Cambio seguro de contraseña

- **Propia** (`/cuenta/contrasena`, enlace con tu nombre en el menú): exige la contraseña actual, la nueva debe ser distinta y robusta. La sesión actual sigue abierta y se cierran las de otros dispositivos.
- **Restablecer la de otro** (administrador, desde Editar usuario): exige la contraseña del administrador y cierra todas las sesiones de esa persona. Para la propia cuenta se redirige al cambio con contraseña actual.
- Ambas rutas tienen límite de 6 intentos por minuto.
- Desde la consola sigue disponible `php artisan usuario:restablecer-contrasena` (ver [RECUPERAR_ACCESO.md](RECUPERAR_ACCESO.md)) para quien administra la instalación, por ejemplo si se olvida la contraseña del único administrador. Al cambiar la contraseña por cualquiera de las vías, las sesiones abiertas con la anterior se cierran. Restablecer la contraseña no reactiva una cuenta desactivada: eso se hace desde `/usuarios`.

## Cómo se aplican los permisos en el servidor

- `Gate` por permiso en `AppServiceProvider`; las rutas sensibles usan el middleware `can:` (`routes/pagos.php`, `routes/pedidos.php`, `routes/inventario.php`, `routes/usuarios.php`). Un intento no autorizado recibe 403 sin modificar datos, aunque se envíe el formulario a mano.
- Las vistas ocultan botones no permitidos (`@can`), pero la seguridad no depende de ocultarlos.
- `VerificarUsuarioActivo` y `AuthenticateSession` (grupo web) cierran sesiones de cuentas desactivadas o cuya contraseña cambió.
- El ingreso solo acepta cuentas activas; el mensaje de error no revela si la cuenta existe.

## Migración

`2026_11_03_000100_agregar_rol_y_estado_a_usuarios` agrega `rol` (por defecto operador), `activo` y `desactivado_en`. Si no hay administradores, la cuenta más antigua pasa a administrador para no quedar sin acceso a usuarios. Después de migrar, entrar con esa cuenta y asignar los roles de las otras dos desde `/usuarios`.

## Pruebas

`tests/Feature/UsuariosTest.php` (alta, validaciones, edición, desactivación/reactivación, cierre de sesiones, último administrador, contraseñas, comando) y `tests/Feature/PermisosTest.php` (matriz permitido/denegado por rol para verificar pagos, ajustar inventario, cancelar pedidos y administrar usuarios; operador conserva operaciones comunes; interfaz oculta acciones; cuenta desactivada pierde permisos).

`UserFactory` crea administradores por defecto para que las suites existentes sigan recorriendo flujos completos; las pruebas de permisos usan `->operador()`, `->supervisor()` e `->inactivo()`.
