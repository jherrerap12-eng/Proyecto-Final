# Entregas — Sprint 4

Rama feature/entregas desde main 42fcffb (PR #3 integra pedidos/pagos). Implementación probada localmente; el encargado confirmó que funciona y autorizó commit/publicación. Revisión de otro integrante e integración del PR pendientes.

## Uso y reglas

1. Confirmar pedido → abrir Entregas → marcar empacado. Se registra fecha/usuario, sin retirar stock.
2. Completar destinatario, teléfono, dirección y transportista: Forza requiere guía/referencia; mensajería propia la admite opcional.
3. Confirmar envío cuando todas las piezas salgan. En una transacción se bloquean pedido/envío/piezas/reservas, se valida coherencia y se registra salida -1 por pieza. Queda existencia física 0 y reserva bloqueante.
4. Confirmar entrega con casilla explícita de recepción física. Fecha, usuario y notas opcionales; no vuelve a retirar piezas ni registra/verifica cobros. Pago y entrega independientes.
5. Cancelar antes de envío libera reservas, sin sumar una pieza que seguía físicamente presente. Después del envío/entrega, se conserva bloqueo hasta confirmar regreso desde Pedidos.
6. Regreso físico: entrada +1 solo si existía 0, liberación y fecha/actor devuelto_en/devuelto_por una sola vez. Conserva fechas originales de envío y entrega. No reembolsa automáticamente.

Estados: por_preparar → empacado → enviado → entregado. Un pedido entregado sigue comercialmente confirmado para registrar saldo pendiente o cancelación/regreso. Contra entrega admite saldo pendiente; depósito revalida el mínimo verificado del 25 % al enviar.

Un paquete completo por pedido. No hay envíos, entregas ni devoluciones parciales. Reenvío idéntico no duplica efectos; datos diferentes después de registrar envío/entrega se rechazan. Versión de pedido protege formularios obsoletos. Contacto histórico se copia al empacar y se revisa antes de enviar; baja del cliente no elimina historial.

Seguimiento manual: no consulta una API de Forza ni inventa estados externos. No se crean envíos históricos ficticios para estados que pudieran haberse cambiado manualmente antes.

## Archivos

- Migración 2026_11_02_000400_crear_tabla_envios: tabla nueva con FK y pedido_id único. No modifica tablas existentes al aplicar.
- Modelo Envio y relación Pedido::envio(): destino, transporte/guía, fechas y responsables.
- SolicitudEnvio: validación servidor, guía condicional, teléfono y dirección. Actores/fechas se establecen en servidor.
- GestionarEntregas: transacciones, estados y movimientos; GestionarPedidos::confirmarDevolucion actualiza el regreso en envios.
- ControladorEntregas, routes/entregas.php y resources/views/entregas. Rutas auth, formularios CSRF, filtros/búsqueda/paginación.
- Compartidos: menú/panel, enlace Ver entrega en pedido y hook de regreso. Conservarlos al integrar reportes/usuarios.

## Contrato para reportes

Pedido tiene cero o un Envio. Fechas: empacado_en, enviado_en, entregado_en, devuelto_en. Responsables: empacado_por, enviado_por, entregado_por, devuelto_por; referencias a users. Estado logístico: pedidos.estado_logistico. Cancelación: pedidos.estado/cancelado_en. No se borran fechas de entrega al cancelar.

Revisar estado comercial/devoluciones antes de contar entregas como ventas vigentes. La fecha contable de venta y el tratamiento de devoluciones aún deben acordarse para reportes. Cobros verificados no equivalen automáticamente a ventas. Usar precio/costo de detalles_pedido, no valores actuales del catálogo.

## Evidencia

Base: 52 pruebas/517 comprobaciones. Suite con entregas: 66/654 aprobadas sobre SQLite en memoria. Cubre acceso, validación, orden de estados, Forza/mensajería, actor servidor, reenvíos, stock, cancelación/regreso, filtros/paginación, escape HTML y pago después de entrega.

Migración aplicada en MariaDB local, lote 6. `php tests/Integracion/verificar_concurrencia_entregas.php`: cuatro escenarios aprobados con procesos independientes (doble envío, cancelación antes de enviar, cancelación con formulario obsoleto y doble entrega). Comprueba salidas únicas, stock correcto y regreso una sola vez. Usa clones con prefijo aleatorio y los elimina al terminar; no inserta fixtures en tablas reales. Solo entorno local/mysql; requiere permisos de crear/eliminar tablas, no usar en producción.

Vistas ficticias en memoria de listado y seis estados a 360/1280 px sin desbordamiento de página; tablas desplazables en móvil. Capturas en .tmp ignorado por Git.

## Comprobar y compartir

`php artisan serve` inicia servidor; abrir /entregas. Probar con un pedido confirmado: empacar, enviar y entregar. Estas acciones sí modifican datos de la instalación.

Después de integrar el PR, cada colaborador actualiza main y ejecuta `php artisan migrate` (crea envios en su propia base) y `php artisan test` (SQLite aislado). No usar migrate:fresh para actualizar ni copiar .env/datos de otros.

Pendientes: revisión de otro integrante e integración del PR. El encargado validó el funcionamiento manual y autorizó commit/publicación. Permisos diferenciados, reembolsos, reportes y dashboard continúan en sus módulos. No se añadieron etiquetas PDF, API, paquetes parciales o reintentos de envío.
