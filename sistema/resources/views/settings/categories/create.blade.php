@extends('template.administrador')

@section('title', 'CATEGORIAS')
@section('name-section', 'CATEGORIAS')

@section('description', 'Nuevo registro de categoría')
@section('item-breadcrumb', 'Nuevo')
@section('section-breadcrumb', 'Categorías')
@section('module-breadcrumb', 'Configuraciones')


@section('content')
<div class="card bg-white border-0 shadow-sm border-5 border-top border-secondary">
    <div class="card-body">
        <form action="{{ route('categories.store') }}" method="POST">
            @csrf
            @include('settings.categories._form')
        </form>
    </div>
</div>

@endsection