# PRD inicial → Babybu Toys

> Estado de cierre (2026-11-03): este documento conserva el levantamiento inicial. RF01–RF12 tienen implementación; roles, devolución física, correcciones/reembolsos y despliegue se ampliaron posteriormente. Consultar CHECKLIST.md, MANUAL_USUARIO.md y ENTREGA_FINAL.md. La plantilla del curso y aprobación formal del negocio siguen por adjuntar.

Borrador pendiente de validación del negocio. No sustituye la plantilla del curso aún no proporcionada.

## Problema y objetivo

Ventas mediante lives y WhatsApp, capturas y registros manuales. Falta control consistente de existencias, costos, ganancias, pagos y entregas; muchas piezas únicas. Objetivo: registrar operaciones y evitar vender existencias comprometidas.

## Usuarios y alcance

Tres cuentas internas. Juguetes con código manual, inventario, clientes, pedidos, reservas, costos/precios, pagos, logística y reportes. Autenticación, autorización por definir, validaciones, búsquedas, filtros, paginación y pruebas.

Fuera de alcance: tienda pública, cuentas de clientes, cobros automáticos, integración WhatsApp/Forza, facturación electrónica, app nativa y microservicios; no añadir sin validación.

## Requisitos y aceptación

| ID | Requisito | Aceptación inicial |
|---|---|---|
| RF01 | Acceso interno | Visitante no ve panel; credenciales válidas ingresan; salida invalida sesión; intentos limitados |
| RF02 | Productos | Código manual único; costos/precios válidos; errores rechazados en servidor |
| RF03 | Clientes | Datos acordados; alta/consulta/modificación; búsqueda y paginación; baja condicionada al historial |
| RF04 | Inventario | Disponibilidad no negativa; entradas/ajustes con responsable y motivo |
| RF05 | Pedidos | Cliente/detalles válidos; total servidor; precio/costo históricos |
| RF06 | Reservas | Depósito verificado: firme; contra entrega: temporal; ambas bloquean disponibilidad |
| RF07 | Concurrencia | Dos transacciones por última pieza, solo una confirma y no hay cambios parciales |
| RF08 | Cancelación | Una sola liberación según regla aprobada; envío/entrega pendiente de resolver |
| RF09 | Pagos | Pendiente/parcial/pagado según saldo; correcciones y reembolsos por definir |
| RF10 | Logística | Empaque, Forza/mensajería propia, entrega, fecha y responsable |
| RF11 | Reportes | Día/semana/mes contrastados; ventas, cobros y ganancia bruta diferenciados |
| RF12 | Consultas | Filtros/paginación conservan parámetros; Fetch maneja errores sin sustituir validación servidor |

## No funcionales

Seguridad: hash, CSRF, regeneración de sesión, permisos servidor, secretos fuera de Git. Integridad: transacciones y restricciones; concurrencia sobre MySQL real. Usabilidad: español, etiquetas/errores, teclado y móvil; comprobar 360/1280 px. Rendimiento: paginación e índices; objetivo cuantitativo pendiente de volumen/alojamiento. Mantenibilidad: MVC, nombres propios en español, locks, migraciones y revisiones. Disponibilidad: despliegue por Internet, respaldo/recuperación por definir.

## Fuente y cumplimiento académico

Definición del negocio p. 1: códigos, tres usuarios, reservas, cancelación, pagos, logística y reportes. Guía pp. 1–5: negocio real, levantamiento, PRD previo, cinco integrantes y Scrum, tablero con responsables/evidencias, frontend responsivo, backend, BD relacional/DER, Git, participación y despliegue. Sección 6 condicionada: no exige todas las tecnologías; API independiente/CI-CD no universales.

La base técnica se inició con este PRD sin validación formal del negocio. Registrar la limitación; no inventar aprobación, entrevistas ni evidencia retroactiva.
