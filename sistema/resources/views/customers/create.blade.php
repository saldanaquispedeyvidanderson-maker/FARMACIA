@extends('template.administrador')

@section('title', 'CLIENTES')
@section('name-section', 'CLIENTES')

@section('description', 'Nuevo registro de cliente')
@section('item-breadcrumb', 'Nuevo')
@section('section-breadcrumb', 'Clientes')
@section('module-breadcrumb', 'Gestión')


@section('content')
<div class="card bg-white border-0 shadow-sm border-5 border-top border-secondary">
    <div class="card-body">
        <form action="{{ route('customers.store') }}" method="POST">
            @csrf
            @include('customers._form')
        </form>
    </div>
</div>

@endsection
