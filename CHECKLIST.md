# Cumplimiento y estado real

Actualizado: 2026-11-01. Código escrito no equivale a validación del negocio ni a evidencia académica completa.

## Sprint 1

- [x] Lectura de PDF y revisión del entorno/repositorio.
- [x] Estructura oficial Laravel 12 creada.
- [x] PRD, reglas, arquitectura y DER conceptual iniciales redactados.
- [ ] Validación del negocio y dudas críticas resueltas.
- [ ] Incorporar plantilla PRD del curso, no recibida.
- [x] Código inicial de autenticación y plantilla Bootstrap.
- [x] Dependencias, compilación y pruebas verificadas: consultar registro.
- [x] Repositorio local exclusivo inicializado, sin commits ni remoto.
- [x] Conexión local configurada y tres migraciones iniciales verificadas sobre `babybu_toys` (servidor de XAMPP/MariaDB).
- [ ] Tres cuentas reales creadas localmente.
- [x] Primera cuenta creada; el encargado confirmó que el inicio y cierre de sesión funcionan en navegador.
- [ ] Primer estado estable revisado y commit autorizado.
- [ ] Repositorio exclusivo publicado y compañero invitado.
- [ ] Ramas feature/inventario y feature/clientes desde main actualizado.
- [ ] GitHub Projects con responsables, cinco columnas e historial real.
- [ ] Instalación reproducida por el compañero.

## Sprint 2 — inventario implementado; integración y clientes pendientes

- [x] Productos: código manual único por pieza física, costos y precios.
- [x] Existencias 0/1 y movimientos con motivo/responsable; protección transaccional.
- [x] Búsqueda, filtros, paginación y pruebas de inventario.
- [x] Revisión visual de listado, formulario y ficha a 1280/360 px, con vistas ficticias en memoria.
- [ ] Clientes: CRUD, formularios, validaciones, búsquedas y pruebas.
  - Código implementado en `feature/clientes`, con pruebas SQLite correctas. Pendientes: validar campos con el negocio, aplicar migración local, comprobar MySQL/MariaDB y revisar navegador/móvil. Ver `documentacion/CLIENTES.md`.
- [ ] Permisos por operación acordados.
  - Propuesta de tres roles implementada y probada en `feature/usuarios-permisos` (Persona 4): alta/edición/desactivación de cuentas, cambio seguro de contraseña, permisos en servidor. Pendiente: aprobación del negocio y PR. Ver `documentacion/USUARIOS_Y_PERMISOS.md`.
- [ ] Revisión móvil y PR documentados.

## Sprints 3–6 → pendientes

- [x] Pedidos/reservas implementados y probados en feature/pedidos; concurrencia comprobada en MariaDB local. Revisión/PR pendientes; MySQL 8 no probado.
- [x] Cancelación/regreso integrados con empaque/envío/entrega en feature/entregas, con fechas/responsables; pendiente validación manual y PR.
- [x] Interfaz de pagos/verificación integrada en main mediante PR #3 (42fcffb), con pedidos/reservas.
- [x] Migración puente preserva pagos legacy sin verificación automática; pruebas SQLite y MariaDB aprobadas.
- [ ] Pagos, correcciones y saldos; logística completa.
- [x] Reportes diarios/semanales/mensuales con ventas pagadas/cobros/ganancia bruta diferenciados, filtros, paginación y CSV implementados en `feature/reportes`. Revisión manual, PR y aprobación pendientes; ver `documentacion/REPORTES.md`.
- [x] Logística de paquete completo implementada/probada en feature/entregas. Publicación/PR pendientes.
- [ ] Correcciones/anulaciones de pagos, reembolsos y permisos según reglas por acordar.
  - Correcciones y anulaciones con historial y protección del mínimo de reserva firme implementadas y probadas en `feature/correcciones-pagos` (Persona 4). Reembolsos parciales/totales implementados en `feature/reembolsos-ventas`. Concurrencia financiera verificada en MariaDB local en `fix/verificacion-financiera`; lectura actual de reservas corregida. Pendientes: revisión/PR de esta corrección y ejecución en MySQL 8. Ver `documentacion/CORRECCIONES_PAGOS.md`.
- [ ] Reportes diarios/semanales/mensuales con ventas/cobros/ganancia diferenciados.
- [ ] Filtros, paginación y Fetch útil.
- [ ] Seguridad y pruebas integrales; DER final.
- [ ] Despliegue por Internet y respaldo/restauración.
- [ ] Participación/dominio de cinco estudiantes exigidos por la guía.
- [ ] Defensa y evidencia auténtica de gestión.

## Condicionados y evidencias

API REST independiente y CI/CD no son obligaciones universales de la guía. Trabajar dos inicialmente no elimina el requisito académico de cinco integrantes. Base publicada en el commit inicial `a4c5fed`; el encargado validó inventario y autorizó su commit/publicación en rama. PR, revisión de otro integrante, invitaciones, tablero y despliegue siguen sin acreditarse aquí. Ver `documentacion/VERIFICACION.md` para resultados técnicos reales.

## Cierre de pendientes del equipo — 2026-11-02 (Persona 4, rama `feature/reembolsos-ventas`)

- [x] Reembolsos con regla acordada (parcial o total, cancelados, tras regreso físico, solo administrador).
- [x] Criterio único de venta: entregada y pagada, compartido por panel y reportes; cobro neto descuenta reembolsos.
- [x] Pruebas finales de integración por rol (`tests/Feature/FlujoCompletoTest.php`): recorridos completos con rechazos, cancelación tras envío, regreso físico, reembolso, corrección y desactivación de cuenta.
- [ ] PR revisado por otro integrante y prueba manual en navegador.

## Revisión — 2026-11-03

- [x] Verificación de reportes actualizada para entregas y reembolsos; aprobada en MariaDB local sin datos de prueba en tablas reales.
- [x] Quince escenarios financieros concurrentes con conexiones independientes y contención demostrada en MariaDB; correcciones, anulaciones, reembolsos y reenvíos sin duplicados ni sobredevolución.
- [x] Lecturas de reservas protegidas contra snapshots antiguos tras verificación/cancelación concurrente; suite funcional aprobada.
- [ ] Revisar e integrar `fix/verificacion-financiera` mediante PR.

