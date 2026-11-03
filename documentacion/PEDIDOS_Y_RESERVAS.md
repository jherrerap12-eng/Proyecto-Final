# Pedidos y reservas — Sprint 3

Rama feature/pedidos desde main 5b0de65, con inventario y clientes integrados. El encargado confirmó el funcionamiento y autorizó commit/publicación. Revisión de integración con pagos y PR pendientes.

## Reglas confirmadas — 2026-11-02

Un código por pieza física. Reserva firme exige al menos 25 % verificado; tras cancelar un pedido enviado/entregado, liberar solo después del regreso físico. No se asumió vencimiento de la reserva temporal.

## Flujo implementado

- Crear borrador con cliente activo y 1–50 piezas diferentes. Búsquedas Fetch muestran 20 resultados y conservan selección; sin JS se pueden usar opciones iniciales.
- Total calculado en servidor, costo/precio/código/nombre históricos por detalle y contacto histórico del cliente. El borrador no aparta piezas.
- Editar solo borradores sin pagos. Si cambia una pieza antes de confirmar, revisar y guardar otra vez. Con pagos registrados se conserva el precio acordado; siempre se revalida disponibilidad actual.
- Confirmar mediante transacción y bloqueos: contra entrega sin abono suficiente produce temporal; depósito exige mínimo verificado y produce firme. Si una pieza falla, ninguna se reserva.
- Disponibilidad = activo + existencia física 1 + sin reserva bloqueante. Reservar no resta existencia física. El índice único de producto_bloqueado_id impide dos reservas bloqueantes sobre la misma pieza.
- Inventario muestra reserva y rechaza movimientos manuales/desactivación de piezas apartadas. El pedido usa versión para evitar ediciones obsoletas.
- Cancelar antes de envío libera sin sumar otra pieza. Después de envío/entrega marca devolucion_pendiente, manteniendo bloqueo. Confirmar regreso de todas las piezas libera y registra entrada si la existencia física era 0.
- Reenvíos de alta, confirmación, cancelación y regreso no duplican operaciones. Cancelar conserva los pagos; no reembolsa automáticamente.

## Datos y archivos

Migración nueva: pedidos, detalles_pedido, reservas, eventos_pedido y base pagos; no borra tablas existentes al aplicar. Modelos con nombres propios en español; SolicitudPedido valida; GestionarPedidos concentra reglas; ControladorPedidos y routes/pedidos.php reciben solicitudes. Vistas en resources/views/pedidos y búsqueda en resources/js/pedidos.js.

`Importes` opera en centavos enteros; DECIMAL(12,2) para total. Mínimo = techo(total_centavos × porcentaje / 100), Q 100.01 requiere Q 25.01. Porcentaje histórico por pedido, no campo libre enviado por navegador.

Cliente 1:N Pedido; Pedido 1:N Detalle/Reserva/Evento/Pago; Producto 1:N reservas históricas, máximo una bloqueante. producto_bloqueado_id nullable y único mantiene el bloqueo; al liberar pasa a null, conservando producto_id histórico.

## Verificación real

Base integrada: 25 pruebas/258 comprobaciones. Suite posterior: 38/396 aprobadas sobre SQLite en memoria. Migración aplicada en MariaDB local. Dos procesos reales compitieron por una pieza: un pedido confirmado, otro rechazado y una reserva bloqueante. La prueba concurrente de inventario también pasó. Los scripts tests/Integracion usan clones con prefijo aleatorio y los eliminan al terminar, sin insertar fixtures en tablas de negocio.

Vite compiló 61 módulos. Listado/formulario/detalle comprobados a 360/1280 px sin desbordamiento de página; tablas desplazables en móvil. Fetch probado con respuestas ficticias: conserva selección y suma Q 65 + Q 75 = Q 140. Capturas/datos de revisión solo en .tmp ignorado por Git.

## Probar localmente

`php artisan serve` inicia servidor, no cambia datos. Entrar en /pedidos, crear borrador contra entrega con una pieza disponible y confirmar. Debe aparecer temporal en pedido/inventario y desaparecer de opciones disponibles. Cancelar antes de enviar debe liberarla una sola vez. Estas operaciones sí modifican los datos de la instalación.

## Pendientes y coordinación

Registro/verificación de pagos ya está integrado en main mediante PR #3. La confirmación firme exige pagos reales verificados; las pruebas usan fixtures exclusivamente en bases de prueba. Ver INTEGRACION_PEDIDOS_PAGOS.md.

Feature/entregas implementa la interfaz y ciclo por_preparar, empacado, enviado y entregado. Envío registra salida física atómica y regreso registra entrada una sola vez. Está probado y pendiente de revisión/PR; ver ENTREGAS.md. No se implementaron devoluciones/cancelaciones parciales.

Usuarios autenticados mantienen acceso interno común; permisos diferenciados y reembolsos requieren definición. Navegación/panel y comprobaciones de inventario son los cambios compartidos a conservar al integrar.
