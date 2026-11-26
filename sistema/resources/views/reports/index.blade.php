@extends('template.administrador')

@section('title', 'REPORTES')
@section('name-section', 'REPORTES')
@section('description', $type === 'medicines' ? 'Estado actual de medicamentos e inventario' : 'Consulta y análisis de información registrada')
@section('item-breadcrumb', 'Inicio')
@section('section-breadcrumb', 'Reportes')
@section('module-breadcrumb', 'Principal')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@php
    $exportFilters = [
        'type' => $type,
        'period' => $period,
        'from' => $from->format('Y-m-d'),
        'to' => $to->format('Y-m-d'),
        'user_id' => $filters['user_id'],
        'customer_id' => $filters['customer_id'],
    ];
@endphp

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">Reportes</h1>
        <p class="small text-muted mb-0">Información consultada desde los registros del sistema · Generado {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2 no-print">
        <a href="{{ route('reports.export', $exportFilters) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Descargar Excel (.xlsx)
        </a>
        <a href="{{ route('reports.pdf', $exportFilters) }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
        </a>
    </div>
</div>

<nav class="nav nav-pills gap-2 mb-4 no-print" aria-label="Tipo de reporte">
    @if(auth()->user()->hasRole('Administrador'))
        <a class="nav-link {{ $type === 'sales' ? 'active' : '' }}" href="{{ route('reports.index', array_merge($exportFilters, ['type' => 'sales'])) }}">Ventas</a>
        <a class="nav-link {{ $type === 'medicines' ? 'active' : '' }}" href="{{ route('reports.index', array_merge($exportFilters, ['type' => 'medicines'])) }}">Medicamentos</a>
        <a class="nav-link {{ $type === 'sellers' ? 'active' : '' }}" href="{{ route('reports.index', array_merge($exportFilters, ['type' => 'sellers'])) }}">Vendedores</a>
    @else
        <span class="nav-link active">Medicamentos e inventario</span>
    @endif
</nav>

@if(in_array($type, ['sales', 'sellers'], true))
    <section class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body">
            <form action="{{ route('reports.index') }}" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="col-12 col-md-3">
                    <label for="period" class="form-label">Periodo</label>
                    <select id="period" name="period" class="form-select">
                        <option value="today" @selected($period === 'today')>Hoy</option>
                        <option value="week" @selected($period === 'week')>Esta semana</option>
                        <option value="month" @selected($period === 'month')>Este mes</option>
                        <option value="custom" @selected($period === 'custom')>Periodo personalizado</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="from" class="form-label">Desde</label>
                    <input type="date" name="from" id="from" class="form-control @error('from') is-invalid @enderror" value="{{ $from->format('Y-m-d') }}">
                    @error('from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-2">
                    <label for="to" class="form-label">Hasta</label>
                    <input type="date" name="to" id="to" class="form-control @error('to') is-invalid @enderror" value="{{ $to->format('Y-m-d') }}">
                    @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-2">
                    <label for="user_id" class="form-label">Vendedor</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label for="customer_id" class="form-label">Cliente</label>
                    <select name="customer_id" id="customer_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) $filters['customer_id'] === (string) $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-1 d-grid">
                    <button class="btn btn-primary text-white" type="submit" aria-label="Aplicar filtros"><i class="bi bi-search"></i></button>
                </div>
            </form>
            <p class="small text-muted mt-3 mb-0">Periodo consultado: {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }} · Los registros no almacenan un estado de venta; las ventas existentes figuran como registradas.</p>
        </div>
    </section>
@endif

@if($type === 'sales')
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><article class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Operaciones</div><div class="h4 fw-bold mb-0">{{ number_format($summary['count']) }}</div></div></article></div>
        <div class="col-6 col-xl-3"><article class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Ingresos acumulados</div><div class="h4 fw-bold mb-0">S/ {{ number_format($summary['total'], 2) }}</div></div></article></div>
        <div class="col-6 col-xl-3"><article class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Ticket promedio</div><div class="h4 fw-bold mb-0">S/ {{ number_format($summary['average'], 2) }}</div></div></article></div>
        <div class="col-6 col-xl-3"><article class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Subtotal / IGV</div><div class="fw-semibold">S/ {{ number_format($summary['subtotal'], 2) }} / {{ number_format($summary['igv'], 2) }}</div></div></article></div>
    </div>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Ventas del periodo</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr>
                        <th>Código</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th>
                        <th class="text-end">Subtotal</th><th class="text-end">IGV</th><th class="text-end">Total</th><th>Estado</th>
                    </tr></thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr>
                                <td><a href="{{ route('sales.show', $sale) }}" class="text-decoration-none fw-semibold">#{{ $sale->id }}</a></td>
                                <td class="text-nowrap">{{ $sale->date->format('d/m/Y H:i') }}</td>
                                <td>{{ $sale->customer->name }}</td>
                                <td>{{ $sale->user->name }}</td>
                                <td class="text-end text-nowrap">S/ {{ number_format($sale->total - $sale->igv, 2) }}</td>
                                <td class="text-end text-nowrap">S/ {{ number_format($sale->igv, 2) }}</td>
                                <td class="text-end text-nowrap">S/ {{ number_format($sale->total, 2) }}</td>
                                <td><span class="badge text-bg-success">Registrada</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No hay ventas para los filtros seleccionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $sales->links() }}</div>
        </div>
    </section>
@elseif($type === 'medicines')
    <div class="alert alert-info py-2">
        <i class="bi bi-info-circle me-1"></i>
        El esquema actual no almacena precio de compra, stock mínimo ni fecha de vencimiento. El umbral de stock bajo usado aquí es {{ $lowStockLimit }} unidades.
    </div>
    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Estado de medicamentos e inventario</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr>
                        <th>Medicamento</th><th>Categoría</th><th class="text-end">Precio de compra</th>
                        <th class="text-end">Precio de venta</th><th class="text-center">Stock</th>
                        <th class="text-center">Stock mínimo</th><th>Vencimiento</th><th>Estado</th>
                    </tr></thead>
                    <tbody>
                        @forelse($medicines as $medicine)
                            @php
                                $stockState = $medicine->stock === 0 ? 'Agotado' : ($medicine->stock <= $lowStockLimit ? 'Stock bajo' : 'Suficiente');
                                $stockBadge = $medicine->stock === 0 ? 'text-bg-danger' : ($medicine->stock <= $lowStockLimit ? 'text-bg-warning' : 'text-bg-success');
                            @endphp
                            <tr>
                                <td>{{ $medicine->name }}</td>
                                <td>{{ $medicine->category->name }}</td>
                                <td class="text-end text-muted">No registrado</td>
                                <td class="text-end text-nowrap">S/ {{ number_format($medicine->price, 2) }}</td>
                                <td class="text-center">{{ $medicine->stock }}</td>
                                <td class="text-center text-muted">No registrado</td>
                                <td class="text-muted">No registrada</td>
                                <td><span class="badge {{ $stockBadge }}">{{ $stockState }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No hay medicamentos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $medicines->links() }}</div>
        </div>
    </section>
@else
    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Rendimiento por vendedor</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Vendedor</th><th class="text-center">Ventas realizadas</th><th class="text-end">Total vendido</th><th class="text-end">Promedio por venta</th></tr></thead>
                    <tbody>
                        @forelse($bySeller as $seller)
                            <tr>
                                <td>{{ $seller->name }}</td>
                                <td class="text-center">{{ number_format($seller->sales_count) }}</td>
                                <td class="text-end text-nowrap">S/ {{ number_format($seller->total, 2) }}</td>
                                <td class="text-end text-nowrap">S/ {{ number_format($seller->average, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No hay ventas para el periodo seleccionado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $bySeller->links() }}</div>
        </div>
    </section>
@endif
@endsection
