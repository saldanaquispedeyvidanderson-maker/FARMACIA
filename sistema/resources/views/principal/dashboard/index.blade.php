@extends('template.administrador')

@section('title', 'DASHBOARD')
@section('name-section', 'DASHBOARD')
@section('description', $isAdministrator ? 'Resumen general de la farmacia' : ($isCashier ? 'Resumen de tus ventas' : 'Resumen de medicamentos e inventario'))
@section('item-breadcrumb', 'Inicio')
@section('section-breadcrumb', 'Dashboard')
@section('module-breadcrumb', 'Principal')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">Panel de control</h1>
        <p class="text-muted mb-0">{{ now()->format('d/m/Y') }} · Datos actualizados desde el sistema</p>
    </div>
    @unless($isPharmacist)
        <form action="{{ route('dashboard.index') }}" method="GET" class="d-flex align-items-center gap-2 no-print">
            <label for="period" class="form-label small text-muted mb-0">Gráficos:</label>
            <select id="period" name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="7" @selected($period === 7)>Últimos 7 días</option>
                <option value="30" @selected($period === 30)>Últimos 30 días</option>
            </select>
        </form>
    @endunless
</div>

<div class="row g-3 mb-4">
    @foreach($stats as $stat)
        <div class="col-6 col-xl-3">
            <article class="card dashboard-stat-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="small text-muted mb-2">{{ $stat['label'] }}</p>
                            <div class="h4 fw-bold mb-1">
                                @if($stat['format'] === 'currency')
                                    S/ {{ number_format($stat['value'], 2) }}
                                @else
                                    {{ number_format($stat['value']) }}
                                @endif
                            </div>
                        </div>
                        <span class="dashboard-stat-icon"><i class="bi {{ $stat['icon'] }}"></i></span>
                    </div>
                    <p class="small text-muted mb-0">{{ $stat['description'] }}</p>
                    @if($stat['comparison'])
                        <p class="small text-primary-emphasis mb-0 mt-2">{{ $stat['comparison'] }}</p>
                    @endif
                </div>
            </article>
        </div>
    @endforeach
</div>

<div id="dashboard-charts"
     data-chart-data="{{ json_encode($charts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
     data-currency="S/">
    @if($isPharmacist)
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-7">
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-3">
                        <h2 class="h6 fw-semibold mb-0">Unidades disponibles por categoría</h2>
                    </div>
                    <div class="card-body">
                        @if(count($charts['inventoryCategories']))
                            <div class="dashboard-chart-wrap dashboard-chart-wrap--donut">
                                <canvas id="inventory-category-chart" role="img" aria-label="Unidades disponibles por categoría"></canvas>
                            </div>
                        @else
                            <p class="text-muted small mb-0">No hay medicamentos registrados para mostrar.</p>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-5">
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-3">
                        <h2 class="h6 fw-semibold mb-0">Alertas de inventario</h2>
                    </div>
                    <div class="card-body">
                        @include('principal.dashboard._inventory-alerts')
                    </div>
                </section>
            </div>
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-8">
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-3">
                        <h2 class="h6 fw-semibold mb-0">Ingresos por día</h2>
                        <p class="small text-muted mb-0">Suma de ventas registradas</p>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart-wrap"><canvas id="daily-sales-chart" role="img" aria-label="Ingresos por día"></canvas></div>
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-3">
                        <h2 class="h6 fw-semibold mb-0">Ventas por categoría</h2>
                        <p class="small text-muted mb-0">Distribución del periodo seleccionado</p>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart-wrap dashboard-chart-wrap--donut"><canvas id="sales-category-chart" role="img" aria-label="Ventas por categoría"></canvas></div>
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-6">
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-3">
                        <h2 class="h6 fw-semibold mb-0">Ingresos de los últimos 12 meses</h2>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart-wrap"><canvas id="monthly-sales-chart" role="img" aria-label="Ingresos de los últimos doce meses"></canvas></div>
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-6">
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-3">
                        <h2 class="h6 fw-semibold mb-0">Cinco medicamentos más vendidos</h2>
                        <p class="small text-muted mb-0">Unidades vendidas en el periodo seleccionado</p>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart-wrap"><canvas id="top-medicines-chart" role="img" aria-label="Medicamentos más vendidos"></canvas></div>
                    </div>
                </section>
            </div>
        </div>
    @endif
</div>

@unless($isPharmacist)
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 px-3">
                    <h2 class="h6 fw-semibold mb-0">Últimas ventas</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead><tr>
                                <th>Código</th><th>Cliente</th><th>Vendedor</th><th>Fecha</th><th class="text-end">Total</th><th>Estado</th>
                            </tr></thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                    <tr>
                                        <td><a href="{{ route('sales.show', $sale) }}" class="fw-semibold text-decoration-none">#{{ $sale->id }}</a></td>
                                        <td>{{ $sale->customer->name }}</td>
                                        <td>{{ $sale->user->name }}</td>
                                        <td class="text-nowrap">{{ $sale->date->format('d/m/Y H:i') }}</td>
                                        <td class="text-end text-nowrap">S/ {{ number_format($sale->total, 2) }}</td>
                                        <td><span class="badge text-bg-success">Registrada</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No hay ventas registradas todavía.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 pt-0">
                    <a href="{{ route('sales.index') }}" class="small text-decoration-none">Ver mis ventas <i class="bi bi-arrow-right"></i></a>
                </div>
            </section>
        </div>

        @if($isAdministrator)
            <div class="col-12 col-xl-5">
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-3">
                        <h2 class="h6 fw-semibold mb-0">Alertas de inventario</h2>
                    </div>
                    <div class="card-body">
                        @include('principal.dashboard._inventory-alerts')
                    </div>
                </section>
            </div>
        @endif
    </div>
@endunless
@endsection

@push('scripts')
<script src="{{ asset('js/dashboard.js') }}" defer></script>
@endpush
