@extends('template.administrador')

@section('tittle', 'CATEGORIAS')
@section('name-section', 'CATEGORIAS')

@section('description', 'Listado de categorias')
@section('items-breadcrumb', 'Inicio')
@section('section-breadcrumb', 'Categorias')
@section('module-breadcrumb', 'Configuraciones')


@section('content')
 <div class="card bg-white border-0 shadow-sm border-5 border-top border-secondary">
                <div class="card-header bg-white">
                    <div class="row">
                        <div class="col-12 col-md-3">
                            <a href="{{ route('categories.create') }}" class="btn btn-primary rounded-3 text-uppercase text-white w-100">
                                <i class="bi bi-plus-lg me-2"></i><span class="small fw-medium">Nuevo registro</span> 
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="text-uppercase small text-muted mb-3">Total de registros encontrados: 1</h6>
                    <table id="display" class="table table-sm table-hover w-100 bg-white">
                        <thead>
                            <tr>
                                <th class="text-center align-middle text-uppercase small">Nº</th>
                                <th class="text-center align-middle text-uppercase small">Nombre</th>
                                <th class="text-center align-middle text-uppercase small">Descripción</th>
                                <th class="text-center align-middle text-uppercase small">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center align-middle">1</td>
                                <td class="text-start align-middle">Lorem, ipsum dolor.</td>
                                <td class="text-start align-middle">lorem ipsum dolor sit amet</td>
                                <td class="text-center align-middle">
                                    <div class="dropdown">
                                        <button class="btn btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="">Editar</a></li>
                                            <li>
                                                <button type="submit" class="dropdown-item">Eliminar</button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
@endsection