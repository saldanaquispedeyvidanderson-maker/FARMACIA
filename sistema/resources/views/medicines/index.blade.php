@extends('template.administrador')

@section('title', 'MEDICAMENTOS')
@section('name-section', 'MEDICAMENTOS')

@section('description', 'Listado de medicamentos')
@section('item-breadcrumb', 'Inicio')
@section('section-breadcrumb', 'Medicamentos')
@section('module-breadcrumb', 'Gestión')


@section('content')
<div class="card bg-white border-0 shadow-sm border-5 border-top border-secondary">
    <div class="card-header bg-white">
        <div class="row">
            <div class="col-12 col-md-3">
                <a href="{{ route('medicines.create') }}" class="btn btn-primary rounded-3 text-uppercase text-white w-100">
                    <i class="bi bi-plus-lg me-2"></i><span class="small fw-medium">Nuevo registro</span>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <h6 class="text-uppercase small text-muted mb-3">Total de registros encontrados: {{ $medicines->count() }}</h6>
        <div class="table-responsive">
        <table id="display" class="table table-sm table-hover w-100 bg-white">
            <thead>
                <tr>
                    <th class="text-center align-middle text-uppercase small">Nº</th>
                    <th class="text-center align-middle text-uppercase small">Nombre</th>
                    <th class="text-center align-middle text-uppercase small">Categoría</th>
                    <th class="text-center align-middle text-uppercase small">Precio</th>
                    <th class="text-center align-middle text-uppercase small">Stock</th>
                    <th class="text-center align-middle text-uppercase small">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($medicines as $medicine)
                    <tr>
                        <td class="text-center align-middle">{{ $loop->iteration }}</td>
                        <td class="text-start align-middle">{{ $medicine->name }}</td>
                        <td class="text-start align-middle">{{ $medicine->category->name }}</td>
                        <td class="text-end align-middle">{{ 'S/ ' . number_format($medicine->price, 2) }}</td>
                        <td class="text-center align-middle">{{ $medicine->stock }}</td>
                        <td class="text-center align-middle">
                            <div class="dropdown">
                                <button class="btn btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('medicines.edit', $medicine->id) }}">Editar</a></li>
                                    <li>
                                        <form action="{{ route('medicines.destroy', $medicine->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este registro?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item">Eliminar</button>
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
