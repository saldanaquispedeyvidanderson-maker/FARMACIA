<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de {{ $title }}</title>
    <style>
        @page {
            margin: 28px 30px 48px;
        }

        body {
            color: #243746;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9px;
            line-height: 1.4;
        }

        .brand {
            border-bottom: 2px solid #315b72;
            margin-bottom: 16px;
            padding-bottom: 10px;
        }

        .brand-name {
            color: #24495f;
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }

        .brand-subtitle {
            color: #627784;
            font-size: 9px;
            margin: 3px 0 0;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 3px;
        }

        .generated {
            color: #627784;
            margin: 0;
        }

        .section-title {
            color: #24495f;
            font-size: 10px;
            font-weight: bold;
            margin: 14px 0 6px;
        }

        .metadata,
        .summary,
        .report-table {
            border-collapse: collapse;
            width: 100%;
        }

        .metadata td {
            border: 1px solid #dce3e8;
            padding: 6px 7px;
            vertical-align: top;
        }

        .metadata .label {
            background: #f1f5f7;
            color: #526977;
            font-weight: bold;
            width: 17%;
        }

        .summary td {
            background: #f1f5f7;
            border: 1px solid #dce3e8;
            padding: 8px;
            width: 25%;
        }

        .summary-label {
            color: #627784;
            display: block;
            font-size: 8px;
            margin-bottom: 4px;
        }

        .summary-value {
            color: #24495f;
            font-size: 12px;
            font-weight: bold;
        }

        .report-table {
            table-layout: fixed;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table th {
            background: #315b72;
            color: #fff;
            font-size: 8px;
            font-weight: bold;
            padding: 7px 5px;
            text-align: left;
        }

        .report-table td {
            border-bottom: 1px solid #dce3e8;
            padding: 6px 5px;
            vertical-align: top;
            overflow-wrap: break-word;
        }

        .report-table tbody tr:nth-child(even) {
            background: #f5f8fa;
        }

        .numeric {
            text-align: right !important;
            white-space: nowrap;
        }

        .center {
            text-align: center !important;
        }

        .status {
            color: #2f6b4f;
            font-weight: bold;
        }

        .empty {
            color: #627784;
            padding: 12px !important;
            text-align: center;
        }
    </style>
</head>
<body>
    <header class="brand">
        <p class="brand-name">FARM SJB</p>
        <p class="brand-subtitle">Sistema de Venta de Medicamentos</p>
    </header>

    <h1 class="report-title">Reporte de {{ $title }}</h1>
    <p class="generated">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>

    <h2 class="section-title">Periodo y filtros</h2>
    <table class="metadata">
        <tbody>
            <tr>
                <td class="label">Periodo consultado</td>
                <td>{{ $period }}</td>
                <td class="label">Fecha inicial</td>
                <td>{{ $from->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Fecha final</td>
                <td>{{ $to->format('d/m/Y') }}</td>
                @if($type !== 'medicines')
                    <td class="label">Vendedor</td>
                    <td>{{ $userName ?? 'Todos' }}</td>
                @else
                    <td class="label">Categoría</td>
                    <td>Todas</td>
                @endif
            </tr>
            @if($type !== 'medicines')
                <tr>
                    <td class="label">Cliente</td>
                    <td colspan="3">{{ $customerName ?? 'Todos' }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if($type === 'sales')
        <h2 class="section-title">Resumen estadístico</h2>
        <table class="summary">
            <tbody>
                <tr>
                    <td><span class="summary-label">Cantidad de operaciones</span><span class="summary-value">{{ number_format($summary['count']) }}</span></td>
                    <td><span class="summary-label">Ingresos acumulados</span><span class="summary-value">S/ {{ number_format($summary['total'], 2) }}</span></td>
                    <td><span class="summary-label">IGV acumulado</span><span class="summary-value">S/ {{ number_format($summary['igv'], 2) }}</span></td>
                    <td><span class="summary-label">Ticket promedio</span><span class="summary-value">S/ {{ number_format($summary['average'], 2) }}</span></td>
                </tr>
            </tbody>
        </table>

        <h2 class="section-title">Detalle de ventas</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 7%">Código</th>
                    <th style="width: 12%">Fecha</th>
                    <th style="width: 18%">Cliente</th>
                    <th style="width: 18%">Vendedor</th>
                    <th class="numeric" style="width: 12%">Subtotal</th>
                    <th class="numeric" style="width: 9%">IGV</th>
                    <th class="numeric" style="width: 12%">Total</th>
                    <th style="width: 12%">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td>#{{ $sale->id }}</td>
                        <td>{{ $sale->date->format('d/m/Y H:i') }}</td>
                        <td>{{ $sale->customer?->name ?? 'Sin cliente' }}</td>
                        <td>{{ $sale->user?->name ?? 'Sin vendedor' }}</td>
                        <td class="numeric">S/ {{ number_format((float) $sale->total - (float) $sale->igv, 2) }}</td>
                        <td class="numeric">S/ {{ number_format((float) $sale->igv, 2) }}</td>
                        <td class="numeric">S/ {{ number_format((float) $sale->total, 2) }}</td>
                        <td class="status">Registrada</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="8">No hay ventas para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif($type === 'medicines')
        <h2 class="section-title">Estado de medicamentos e inventario</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 27%">Medicamento</th>
                    <th style="width: 21%">Categoría</th>
                    <th class="numeric" style="width: 16%">Precio de compra</th>
                    <th class="numeric" style="width: 16%">Precio de venta</th>
                    <th class="center" style="width: 9%">Stock</th>
                    <th style="width: 11%">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($medicines as $medicine)
                    <tr>
                        <td>{{ $medicine->name }}</td>
                        <td>{{ $medicine->category?->name ?? 'Sin categoría' }}</td>
                        <td class="numeric">No registrado</td>
                        <td class="numeric">S/ {{ number_format((float) $medicine->price, 2) }}</td>
                        <td class="center">{{ $medicine->stock }}</td>
                        <td>{{ $medicine->stock === 0 ? 'Agotado' : ($medicine->stock <= $lowStockLimit ? 'Stock bajo' : 'Suficiente') }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="6">No hay medicamentos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <h2 class="section-title">Rendimiento por vendedor</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Vendedor</th>
                    <th class="center">Cantidad de ventas</th>
                    <th class="numeric">Total vendido</th>
                    <th class="numeric">Promedio por venta</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bySeller as $seller)
                    <tr>
                        <td>{{ $seller->name }}</td>
                        <td class="center">{{ number_format($seller->sales_count) }}</td>
                        <td class="numeric">S/ {{ number_format((float) $seller->total, 2) }}</td>
                        <td class="numeric">S/ {{ number_format((float) $seller->average, 2) }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="4">No hay ventas para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
