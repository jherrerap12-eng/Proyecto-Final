@extends('plantillas.principal')
@section('titulo', 'Reportes del negocio')
@section('contenido')
@php($dinero = fn ($centavos) => 'Q '.\App\Soporte\ImporteReporte::decimal((int) $centavos))
<div class="mb-4">
    <span class="insignia-marca mb-3">REPORTES DEL NEGOCIO</span>
    <h1 class="fw-bold">Ventas, ganancia y cobros</h1>
    <p class="text-secondary">Una venta cuenta cuando el pedido está entregado y pagado por completo con abonos verificados; es el mismo criterio del panel. Los cobros se muestran por la fecha de cada abono y los reembolsos por la fecha de la devolución.</p>
</div>
<form action="{{ route('reportes.index') }}" method="GET" class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h2 class="h5">Seleccionar período</h2>
        @if($errors->any())<div class="alert alert-danger" role="alert">Revisa las fechas y la agrupación del reporte.</div>@endif
        <div class="row g-3 align-items-end">
            <div class="col-sm-6 col-lg-3">
                <label for="desde" class="form-label">Fecha inicial</label>
                <input id="desde" name="desde" type="date" required value="{{ is_string(old('desde')) ? old('desde') : $filtros['desde'] }}" class="form-control @error('desde') is-invalid @enderror" @error('desde') aria-invalid="true" aria-describedby="error-desde" @enderror>
                @error('desde')<div id="error-desde" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6 col-lg-3">
                <label for="hasta" class="form-label">Fecha final</label>
                <input id="hasta" name="hasta" type="date" required value="{{ is_string(old('hasta')) ? old('hasta') : $filtros['hasta'] }}" class="form-control @error('hasta') is-invalid @enderror" @error('hasta') aria-invalid="true" aria-describedby="error-hasta" @enderror>
                @error('hasta')<div id="error-hasta" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6 col-lg-3">
                <label for="agrupacion" class="form-label">Agrupación</label>
                <select id="agrupacion" name="agrupacion" required class="form-select @error('agrupacion') is-invalid @enderror" @error('agrupacion') aria-invalid="true" aria-describedby="error-agrupacion" @enderror>
                    @foreach(['diaria' => 'Diaria', 'semanal' => 'Semanal', 'mensual' => 'Mensual'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(old('agrupacion', $filtros['agrupacion']) === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('agrupacion')<div id="error-agrupacion" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6 col-lg-3 d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">Consultar</button>
                <a class="btn btn-outline-secondary" href="{{ route('reportes.index') }}">Mes actual</a>
            </div>
        </div>
        <p class="form-text mb-0">Fechas inclusivas · Hora de Guatemala · Semanas de lunes a domingo</p>
    </div>
</form>
<div class="row g-3 mb-4">
    @foreach(['ventas' => 'Ventas (entregadas y pagadas)', 'costos' => 'Costo histórico', 'ganancia' => 'Ganancia bruta', 'cobros' => 'Cobros verificados', 'reembolsos' => 'Reembolsos', 'cobro_neto' => 'Cobro neto'] as $campo => $etiqueta)
        <div class="col-sm-6 col-xl-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><h2 class="h6 text-secondary">{{ $etiqueta }}</h2><p class="h4 text-break mb-0">{{ $dinero($resumen['totales'][$campo]) }}</p></div></div></div>
    @endforeach
</div>
<p><strong>{{ $resumen['totales']['pedidos'] }}</strong> pedido(s) vendido(s) en el período. La ganancia bruta descuenta el costo histórico de las piezas; no incluye gastos operativos.</p>
<div class="alert alert-info" role="note">Se excluyen ventas canceladas, pedidos aún no entregados y pagos pendientes o anulados. Los cobros de pedidos cancelados se conservan; lo devuelto aparece como reembolso y se resta en el cobro neto. Estos reportes se recalculan con el estado actual y pueden cambiar al verificar, anular o cancelar operaciones.</div>
<section class="mb-5" aria-labelledby="titulo-resumen">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 id="titulo-resumen" class="h4 mb-0">Resumen por período</h2>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('reportes.exportar', array_merge($filtros, ['tipo' => 'resumen'])) }}">Descargar resumen CSV</a>
    </div>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table align-middle mb-0">
            <caption class="px-3">Solo se muestran períodos con ventas, cobros o reembolsos. Los extremos se limitan al rango seleccionado.</caption>
            <thead><tr><th scope="col">Desde</th><th scope="col">Hasta</th><th scope="col">Pedidos vendidos</th><th scope="col">Ventas</th><th scope="col">Costo histórico</th><th scope="col">Ganancia bruta</th><th scope="col">Cobros</th><th scope="col">Reembolsos</th><th scope="col">Cobro neto</th></tr></thead>
            <tbody>
                @forelse($resumen['periodos'] as $periodo)
                    <tr><th scope="row">{{ $periodo['desde'] }}</th><td>{{ $periodo['hasta'] }}</td><td>{{ $periodo['pedidos'] }}</td>@foreach(['ventas', 'costos', 'ganancia', 'cobros', 'reembolsos', 'cobro_neto'] as $campo)<td class="text-nowrap">{{ $dinero($periodo[$campo]) }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="9" class="p-4 text-center text-secondary">No hay ventas, cobros ni reembolsos en este período.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><th scope="row" colspan="2">Total del período</th><td>{{ $resumen['totales']['pedidos'] }}</td>@foreach(['ventas', 'costos', 'ganancia', 'cobros', 'reembolsos', 'cobro_neto'] as $campo)<td class="text-nowrap fw-bold">{{ $dinero($resumen['totales'][$campo]) }}</td>@endforeach</tr></tfoot>
        </table>
    </div></div>
</section>
<section id="ventas" class="mb-5" aria-labelledby="titulo-ventas">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 id="titulo-ventas" class="h4 mb-0">Detalle de ventas</h2>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('reportes.exportar', array_merge($filtros, ['tipo' => 'ventas'])) }}">Descargar ventas CSV</a>
    </div>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table align-middle mb-0">
            <caption class="px-3">{{ $ventas->total() }} venta(s). La descarga incluye todos los resultados filtrados.</caption>
            <thead><tr><th scope="col">Pedido</th><th scope="col">Cliente histórico</th><th scope="col">Fecha de venta</th><th scope="col">Total</th><th scope="col">Costo histórico</th><th scope="col">Ganancia bruta</th></tr></thead>
            <tbody>
                @forelse($ventas as $venta)
                    <tr><th scope="row">#{{ $venta->pedido_id }}</th><td>{{ $venta->cliente_nombre }}</td><td class="text-nowrap">{{ $venta->fecha_venta }}</td><td class="text-nowrap">{{ $dinero($venta->total_centavos) }}</td><td class="text-nowrap">{{ $dinero($venta->costo_centavos) }}</td><td class="text-nowrap">{{ $dinero($venta->ganancia_centavos) }}</td></tr>
                @empty
                    <tr><td colspan="6" class="p-4 text-center text-secondary">No hay ventas entregadas y pagadas en este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $ventas->fragment('ventas')->links('pagination::bootstrap-5') }}</div>
</section>
<section id="cobros" aria-labelledby="titulo-cobros">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 id="titulo-cobros" class="h4 mb-0">Detalle de cobros</h2>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('reportes.exportar', array_merge($filtros, ['tipo' => 'cobros'])) }}">Descargar cobros CSV</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('reportes.exportar', array_merge($filtros, ['tipo' => 'reembolsos'])) }}">Descargar reembolsos CSV</a>
    </div>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table align-middle mb-0">
            <caption class="px-3">{{ $cobros->total() }} abono(s) verificado(s), incluidos los de pedidos cancelados.</caption>
            <thead><tr><th scope="col">Fecha de pago</th><th scope="col">Pedido</th><th scope="col">Cliente histórico</th><th scope="col">Monto</th><th scope="col">Método</th><th scope="col">Estado del pedido</th></tr></thead>
            <tbody>
                @forelse($cobros as $cobro)
                    <tr><td class="text-nowrap">{{ $cobro->fecha_pago }}</td><th scope="row">#{{ $cobro->pedido_id }}</th><td>{{ $cobro->cliente_nombre }}</td><td class="text-nowrap">{{ $dinero($cobro->monto_centavos) }}</td><td>{{ ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito'][$cobro->metodo] ?? $cobro->metodo }}</td><td>@if($cobro->estado_pedido === 'cancelado')<span class="badge text-bg-warning">Cancelado</span>@else{{ ucfirst($cobro->estado_pedido) }}@endif</td></tr>
                @empty
                    <tr><td colspan="6" class="p-4 text-center text-secondary">No hay cobros verificados en este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $cobros->fragment('cobros')->links('pagination::bootstrap-5') }}</div>
</section>
@endsection
