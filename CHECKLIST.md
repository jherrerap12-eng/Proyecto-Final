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
- [ ] Revisión móvil y PR documentados.

## Sprints 3–6 → pendientes

- [ ] Pedidos, reservas firme/temporal y prevención de sobreventa concurrente MySQL.
- [ ] Cancelación sin doble liberación y devoluciones aprobadas.
- [ ] Pagos, correcciones y saldos; logística completa.
- [ ] Reportes diarios/semanales/mensuales con ventas/cobros/ganancia diferenciados.
- [ ] Filtros, paginación y Fetch útil.
- [ ] Seguridad y pruebas integrales; DER final.
- [ ] Despliegue por Internet y respaldo/restauración.
- [ ] Participación/dominio de cinco estudiantes exigidos por la guía.
- [ ] Defensa y evidencia auténtica de gestión.

## Condicionados y evidencias

API REST independiente y CI/CD no son obligaciones universales de la guía. Trabajar dos inicialmente no elimina el requisito académico de cinco integrantes. Base publicada en el commit inicial `a4c5fed`; el encargado validó inventario y autorizó su commit/publicación en rama. PR, revisión de otro integrante, invitaciones, tablero y despliegue siguen sin acreditarse aquí. Ver `documentacion/VERIFICACION.md` para resultados técnicos reales.
