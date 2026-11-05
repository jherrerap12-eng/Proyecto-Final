# Entrega y defensa — Babybu Toys

Fecha de actualización: 2026-11-03. Sistema: https://babybu-toys.onrender.com. Repositorio: https://github.com/jherrerap12-eng/Proyecto-Final.

## Estado y procedencia de evidencias

| Elemento | Estado documentado |
|---|---|
| Módulos y roles | Implementados e integrados en main |
| Pruebas funcionales | Última ejecución registrada: 139 pruebas, 1231 aserciones correctas |
| Reportes MariaDB | Verificación aprobada después de actualizar escenarios de entregas/reembolsos |
| Concurrencia financiera | 15 escenarios aprobados con conexiones independientes y tablas aisladas |
| Despliegue | Render Docker Free y Aiven MySQL; funcionamiento confirmado por el encargado |
| Revisión manual | El encargado confirmó su realización; adjuntar acta/casos/capturas |
| Cierre posterior al despliegue | El encargado indicó «ya está» después de las indicaciones; desglosar evidencia de cuentas, pruebas y respaldo/restauración |
| Validación formal del negocio | Adjuntar aprobación real del PRD, permisos y reglas; no se presume a partir de las pruebas técnicas |

Estos resultados son registros de ejecuciones previas; actualizar fecha, commit y resultados si se ejecuta una nueva versión. No equivalen a certificación de ausencia de defectos.

## Paquete de entrega

- [README](../README.md): alcance, instalación y acceso público.
- [PRD](PRD.md) y [reglas](REGLAS_NEGOCIO.md): requisitos y decisiones.
- [DER final](DER_FINAL.md) y [arquitectura](ARQUITECTURA.md).
- [Manual de usuario](MANUAL_USUARIO.md).
- [Despliegue](DESPLIEGUE_RENDER.md) y [operación y respaldo](OPERACION_Y_RESPALDO.md).
- [Verificación](VERIFICACION.md), [checklist](../CHECKLIST.md) y [backlog](BACKLOG.md).
- Evidencias reales de tablero, PR, pruebas manuales, funcionamiento publicado y restauración.

## Datos que debe completar el equipo

| Dato | Completar |
|---|---|
| Curso, sección y docente | Pendiente de incorporar |
| Nombres y carnés de integrantes | Pendiente de incorporar |
| Responsabilidad real de cada integrante | Pendiente de incorporar con PR/commits o evidencia verificable |
| Enlace al tablero e historias cerradas | Pendiente de incorporar |
| Fecha/persona que validó reglas con el negocio | Pendiente de incorporar |
| Responsable y ubicación privada de respaldos | Pendiente de incorporar |
| Fecha, base destino y resultado de restauración | Pendiente de incorporar |

La guía resumida en el PRD menciona cinco integrantes. Confirmar composición y participación exigidas con el docente. No inventar integrantes, entrevistas, fechas o ceremonias Scrum.

## Evidencias sugeridas

| Código | Evidencia |
|---|---|
| E01 | Render Live, commit desplegado y URL; sin variables secretas |
| E02 | Ingreso y panel publicado con datos de demostración |
| E03 | Pieza con código y movimiento; rechazo de doble reserva |
| E04 | Pedido con varias piezas, reserva y mínimo de depósito |
| E05 | Abono pendiente/verificado, corrección y saldo resultante |
| E06 | Empaque, guía, envío, entrega y responsable |
| E07 | Cancelación enviada, regreso físico y reembolso |
| E08 | Reporte por fechas y CSV contrastado con operaciones |
| E09 | Operador rechazado en una operación reservada al administrador |
| E10 | Resultado de pruebas automáticas con fecha y commit |
| E11 | Restauración en otra base y comprobación de integridad |
| E12 | Issues, revisiones y PR con responsables auténticos |

No adjuntar `.env`, APP_KEY, contraseñas, URLs con credenciales ni volcados de datos reales al repositorio o a diapositivas.

## Guion de demostración (10–12 minutos, ajustar a la guía)

1. Problema del negocio y objetivo (1 minuto).
2. Arquitectura y DER (1 minuto).
3. Ingreso y permisos, cliente e inventario por pieza (2 minutos).
4. Pedido, depósito verificado y bloqueo de reserva (2 minutos).
5. Pago, entrega y reporte con criterio compartido (2 minutos).
6. Cancelación/regreso/reembolso y conservación del historial (1–2 minutos).
7. Pruebas, despliegue, respaldo y limitaciones (1–2 minutos).

Repartir la exposición según trabajo real. Utilizar cuentas individuales autorizadas y datos ficticios. Abrir la URL antes de la defensa para detectar el arranque por inactividad; disponer de capturas de respaldo, sin presentarlas como operaciones en vivo.

## Límites que deben poder explicar

No incluye tienda pública, cobros bancarios automáticos, API de WhatsApp/Forza, facturación electrónica, envíos parciales ni utilidad neta. Reportes reflejan registros vigentes y no cierres contables inmutables. El plan gratuito puede dormir y el disco de la aplicación es efímero. Las sesiones y datos comerciales se conservan en Aiven; respaldos independientes siguen siendo necesarios.
