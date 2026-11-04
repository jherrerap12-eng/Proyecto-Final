# Checklist de cierre

Actualizado: 2026-11-03. Distingue implementación, confirmación del encargado y evidencia adjunta.

## Desarrollo e integración

- [x] Autenticación, cierre, límite de intentos y recuperación técnica.
- [x] Usuarios, roles, desactivación y cambio de contraseña.
- [x] Inventario por pieza con historial y protección de concurrencia.
- [x] Clientes con búsqueda, edición y baja lógica.
- [x] Pedidos, precios históricos y reservas con mínimo verificado del 25 %.
- [x] Pagos, verificación, saldos, corrección y anulación con historial.
- [x] Cancelación, regreso físico y reembolsos parciales/totales.
- [x] Empaque, envío, entrega y responsables.
- [x] Dashboard y reportes con criterio único de venta y CSV.
- [x] Filtros, paginación y búsqueda Fetch en pedidos.
- [x] Ajustes visuales de botones, tablas y dashboard.
- [x] Integración de ramas y publicación del repositorio.

## Verificación y despliegue

- [x] Última suite registrada: 139 pruebas y 1231 aserciones correctas.
- [x] Reportes comprobados en MariaDB tras actualizar el escenario.
- [x] Quince escenarios concurrentes financieros comprobados en MariaDB.
- [x] Revisión manual confirmada por el encargado.
- [x] Render Docker + Aiven MySQL publicados; funcionamiento confirmado.
- [x] Compatibilidad de consulta de migración con ONLY_FULL_GROUP_BY corregida.
- [x] Acceso al CA para Apache corregido sin desactivar SSL.
- [ ] Adjuntar evidencia detallada de pruebas en línea por rol y dispositivo.
- [ ] Adjuntar registro de respaldo/restauración y confirmar responsable/frecuencia.
- [ ] Documentar un volumen objetivo y resultados de rendimiento del dashboard.

El encargado indicó completadas las tareas posteriores al despliegue; no se inventan aquí conteos de cuentas, archivos de respaldo ni resultados de restauración que no están adjuntos.

## Documentación y aceptación

- [x] README y backlog actualizados al estado integrado.
- [x] Manual de usuario redactado.
- [x] DER final basado en las migraciones.
- [x] Guía de despliegue, operación y recuperación redactada.
- [x] Guion de defensa y matriz de evidencias preparados.
- [ ] Adjuntar capturas propias y exportar el DER si lo requiere la entrega.
- [ ] Completar PRD con plantilla del curso y aprobación formal del negocio.
- [ ] Registrar aceptación de matriz de permisos y reglas financieras.
- [ ] Incorporar nombres/carnés, docente, sección y responsabilidades reales.
- [ ] Enlazar tablero, historias, revisiones y participación exigida por la guía.
- [ ] Preparar diapositivas y ensayar la defensa según tiempo del docente.

Ver [ENTREGA_FINAL.md](documentacion/ENTREGA_FINAL.md). No son obligatorios por defecto API independiente, CI/CD, tienda pública o integraciones bancarias; cualquier ampliación requiere alcance acordado.
