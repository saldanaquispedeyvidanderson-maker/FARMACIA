@extends('template.administrador')

@section('title', 'VENTAS')
@section('name-section', 'VENTAS')

@section('description', 'Listado de ventas')
@section('item-breadcrumb', 'Inicio')
@section('section-breadcrumb', 'Ventas')
@section('module-breadcrumb', 'Gestión')


@section('content')
<div class="card bg-white border-0 shadow-sm border-5 border-top border-secondary">
    <div class="card-header bg-white">
        <div class="row">
            <div class="col-12 col-md-3">
                <a href="{{ route('sales.create') }}" class="btn btn-primary rounded-3 text-uppercase text-white w-100">
                    <i class="bi bi-plus-lg me-2"></i><span class="small fw-medium">Nueva venta</span>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <h6 class="text-uppercase small text-muted mb-3">Total de registros encontrados: {{ $sales->count() }}</h6>
        <div class="table-responsive">
        <table id="display" class="table table-sm table-hover w-100 bg-white">
            <thead>
                <tr>
                    <th class="text-center align-middle text-uppercase small">Nº</th>
                    <th class="text-center align-middle text-uppercase small">Fecha</th>
                    <th class="text-center align-middle text-uppercase small">Cliente</th>
                    <th class="text-center align-middle text-uppercase small">Vendedor</th>
                    <th class="text-center align-middle text-uppercase small">IGV</th>
                    <th class="text-center align-middle text-uppercase small">Total</th>
                    <th class="text-center align-middle text-uppercase small">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sales as $sale)
                    <tr>
                        <td class="text-center align-middle">{{ $sale->id }}</td>
                        <td class="text-center align-middle">{{ $sale->date->format('d/m/Y H:i') }}</td>
                        <td class="text-start align-middle">{{ $sale->customer->name }}</td>
                        <td class="text-start align-middle">{{ $sale->user->name }}</td>
                        <td class="text-end align-middle">S/ {{ number_format($sale->igv, 2) }}</td>
                        <td class="text-end align-middle fw-semibold">S/ {{ number_format($sale->total, 2) }}</td>
                        <td class="text-center align-middle">
                            <div class="dropdown">
                                <button class="btn btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('sales.show', $sale->id) }}">Ver detalle</a></li>
                                    <li>
                                        <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" onsubmit="return confirm('¿Anular esta venta? El stock de los medicamentos será devuelto.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">Anular venta</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>

@endsection
