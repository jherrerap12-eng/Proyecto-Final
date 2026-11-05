@extends('plantillas.principal')
@section('titulo', 'Inicio')
@section('contenido')
@php
    $totalLogistica = $pedidosPorPreparar + $pedidosEmpacados + $pedidosEnviados + $pedidosEntregados;
@endphp
<div class="store-dashboard">
    <header class="store-welcome">
        <div class="store-welcome-text">
            <span class="store-kicker"><svg class="ui-icon"><use href="#ui-heart" /></svg> BABYBU · CENTRO DE OPERACIONES</span>
            <h1>Tu tienda, <span>en movimiento.</span></h1>
            <p>¡Buen día, {{ auth()->user()->name }}! Aquí tienes lo importante para atender pedidos y cuidar cada juguete.</p>
        </div>
        <div class="store-welcome-tools"><a href="{{ route('pedidos.crear') }}" class="store-btn-primary"><svg class="ui-icon"><use href="#ui-plus" /></svg> Crear pedido</a><a href="{{ route('inventario.listar') }}" class="store-btn-secondary">Ver juguetes <svg class="ui-icon"><use href="#ui-arrow" /></svg></a></div>
    </header>

    <section aria-label="Indicadores principales de Babybu" class="store-kpi-bar">
        <a href="{{ route('pedidos.listar') }}" class="store-kpi store-kpi-coral"><span class="store-kpi-glyph"><svg class="ui-icon"><use href="#ui-bag" /></svg></span><span class="store-kpi-label">Por preparar</span><strong>{{ $pedidosPorPreparar }}</strong><span class="store-kpi-footer">Pedidos que te esperan</span></a>
        <a href="{{ route('inventario.listar') }}" class="store-kpi store-kpi-teal"><span class="store-kpi-glyph"><svg class="ui-icon"><use href="#ui-box" /></svg></span><span class="store-kpi-label">Juguetes disponibles</span><strong>{{ $piezasDisponibles }}</strong><span class="store-kpi-footer">Piezas listas para vender</span></a>
        <a href="{{ route('reportes.index') }}" class="store-kpi store-kpi-yellow"><span class="store-kpi-glyph"><svg class="ui-icon"><use href="#ui-chart" /></svg></span><span class="store-kpi-label">Ventas completadas</span><strong>Q {{ number_format($resumenVentas->total_ventas ?? 0, 2) }}</strong><span class="store-kpi-footer">Entregadas y pagadas</span></a>
        <a href="{{ route('pagos.index') }}" class="store-kpi store-kpi-blue"><span class="store-kpi-glyph"><svg class="ui-icon"><use href="#ui-wallet" /></svg></span><span class="store-kpi-label">Pendiente de cobro</span><strong>Q {{ number_format($saldosPorCobrar ?? 0, 2) }}</strong><span class="store-kpi-footer">Saldos por gestionar</span></a>
    </section>

    <div class="store-workspace">
        <div class="store-workspace-main">
            <section class="store-board store-orders" aria-labelledby="store-orders-title">
                <div class="store-board-heading"><div><span class="store-board-tag">01 / PEDIDOS</span><h2 id="store-orders-title">Del pedido a la entrega</h2><p>Controla cada etapa sin perder de vista lo que sigue.</p></div><span class="store-chip">{{ $totalLogistica }} en seguimiento</span></div>
                <div class="store-order-flow">
                    <a href="{{ route('pedidos.listar') }}" class="store-flow-step store-flow-coral"><span class="store-flow-number">01</span><span><strong>Por preparar</strong><small>Por revisar y reunir</small></span><b>{{ $pedidosPorPreparar }}</b></a>
                    <a href="{{ route('pedidos.listar') }}" class="store-flow-step store-flow-yellow"><span class="store-flow-number">02</span><span><strong>Empacados</strong><small>Listos para salir</small></span><b>{{ $pedidosEmpacados }}</b></a>
                    <a href="{{ route('entregas.listar') }}" class="store-flow-step store-flow-blue"><span class="store-flow-number">03</span><span><strong>Enviados</strong><small>En camino a su destino</small></span><b>{{ $pedidosEnviados }}</b></a>
                    <a href="{{ route('entregas.listar') }}" class="store-flow-step store-flow-teal"><span class="store-flow-number">04</span><span><strong>Entregados</strong><small>Sonrisas que llegaron</small></span><b>{{ $pedidosEntregados }}</b></a>
                </div>
                <a class="store-text-action" href="{{ route('pedidos.listar') }}">Ir a todos los pedidos <svg class="ui-icon"><use href="#ui-arrow" /></svg></a>
            </section>
            <section class="store-board store-actions" aria-labelledby="store-actions-title">
                <div class="store-board-heading"><div><span class="store-board-tag">02 / ACCESOS DIRECTOS</span><h2 id="store-actions-title">¿Qué necesitas hacer?</h2><p>Todo a un clic, sin buscar entre menús.</p></div></div>
                <div class="store-action-grid">
                    <a href="{{ route('clientes.create') }}" class="store-action-tile"><span class="store-action-icon tile-coral"><svg class="ui-icon"><use href="#ui-users" /></svg></span><span><strong>Agregar cliente</strong><small>Nuevo comprador</small></span><svg class="ui-icon store-action-arrow"><use href="#ui-arrow" /></svg></a>
                    <a href="{{ route('inventario.listar') }}" class="store-action-tile"><span class="store-action-icon tile-teal"><svg class="ui-icon"><use href="#ui-box" /></svg></span><span><strong>Ver inventario</strong><small>Juguetes y existencias</small></span><svg class="ui-icon store-action-arrow"><use href="#ui-arrow" /></svg></a>
                    <a href="{{ route('pagos.index') }}" class="store-action-tile"><span class="store-action-icon tile-yellow"><svg class="ui-icon"><use href="#ui-wallet" /></svg></span><span><strong>Registrar cobro</strong><small>Abonos y saldos</small></span><svg class="ui-icon store-action-arrow"><use href="#ui-arrow" /></svg></a>
                    <a href="{{ route('entregas.listar') }}" class="store-action-tile"><span class="store-action-icon tile-blue"><svg class="ui-icon"><use href="#ui-truck" /></svg></span><span><strong>Preparar entrega</strong><small>Organiza los envíos</small></span><svg class="ui-icon store-action-arrow"><use href="#ui-arrow" /></svg></a>
                </div>
                <a class="store-text-action" href="{{ route('clientes.index') }}">Ver todos los clientes <svg class="ui-icon"><use href="#ui-arrow" /></svg></a>
            </section>
        </div>
        <aside class="store-workspace-side" aria-label="Información adicional de la tienda">
            <section class="store-stock-panel" aria-labelledby="store-stock-title"><div class="store-stock-head"><span class="store-stock-illustration"><svg class="ui-icon"><use href="#ui-gift" /></svg></span><span class="store-board-tag">TU INVENTARIO</span><h2 id="store-stock-title">En los estantes</h2><p>Las piezas que puedes vender y las que ya tienen una familia esperando.</p></div>
                <div class="store-stock-counter"><div><span>Disponibles</span><strong>{{ $piezasDisponibles }}</strong></div><div><span>Reservadas</span><strong>{{ $piezasReservadas }}</strong></div></div>
                <a href="{{ route('inventario.listar') }}" class="store-stock-link">Gestionar inventario <svg class="ui-icon"><use href="#ui-arrow" /></svg></a>
            </section>
            <section class="store-side-note"><span class="store-note-mark"><svg class="ui-icon"><use href="#ui-chart" /></svg></span><div><span class="store-board-tag">RESULTADO DEL NEGOCIO</span><h3>Ganancia bruta</h3><strong>Q {{ number_format($resumenVentas->total_ganancia ?? 0, 2) }}</strong><p>Resultado antes de otros gastos.</p><a href="{{ route('reportes.index') }}">Consultar reportes <svg class="ui-icon"><use href="#ui-arrow" /></svg></a></div></section>
            @if($totalLogistica === 0)
            <div class="store-empty-tip"><svg class="ui-icon"><use href="#ui-smile" /></svg><span>Todo está listo. Registra un cliente y crea el primer pedido para empezar.</span></div>
            @endif
        </aside>
    </div>
</div>
@endsection
