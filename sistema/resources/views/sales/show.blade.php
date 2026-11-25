@extends('template.administrador')

@section('title', 'VENTAS')
@section('name-section', 'VENTAS')

@section('description', 'Detalle de la venta')
@section('item-breadcrumb', 'Detalle')
@section('section-breadcrumb', 'Ventas')
@section('module-breadcrumb', 'Gestión')


@section('content')
<div class="card bg-white border-0 shadow-sm border-5 border-top border-secondary">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-12 col-md-3 mb-2"><div class="small text-muted text-uppercase">Venta Nº</div><div class="fw-semibold">{{ $sale->id }}</div></div>
            <div class="col-12 col-md-3 mb-2"><div class="small text-muted text-uppercase">Fecha</div><div class="fw-semibold">{{ $sale->date->format('d/m/Y H:i') }}</div></div>
            <div class="col-12 col-md-3 mb-2"><div class="small text-muted text-uppercase">Cliente</div><div class="fw-semibold">{{ $sale->customer->name }} <span class="text-muted small">(DNI {{ $sale->customer->dni }})</span></div></div>
            <div class="col-12 col-md-3 mb-2"><div class="small text-muted text-uppercase">Vendedor</div><div class="fw-semibold">{{ $sale->user->name }}</div></div>
        </div>

        <div class="table-responsive">
        <table class="table table-sm table-hover w-100 bg-white">
            <thead>
                <tr>
                    <th class="text-center align-middle text-uppercase small">Nº</th>
                    <th class="text-center align-middle text-uppercase small">Medicamento</th>
                    <th class="text-center align-middle text-uppercase small">Cantidad</th>
                    <th class="text-center align-middle text-uppercase small">Precio unit.</th>
                    <th class="text-center align-middle text-uppercase small">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->details as $detail)
                    <tr>
                        <td class="text-center align-middle">{{ $loop->iteration }}</td>
                        <td class="text-start align-middle">{{ $detail->medicine->name }}</td>
                        <td class="text-center align-middle">{{ $detail->amount }}</td>
                        <td class="text-end align-middle">S/ {{ number_format($detail->price_unit, 2) }}</td>
                        <td class="text-end align-middle">S/ {{ number_format($detail->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end text-muted">IGV incluido ({{ (int) (\App\Models\Sale::IGV_RATE * 100) }}%)</td>
                    <td class="text-end">S/ {{ number_format($sale->igv, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end fw-bold">TOTAL</td>
                    <td class="text-end fw-bold">S/ {{ number_format($sale->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
        </div>

        <a href="{{ route('sales.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>

@endsection
