@extends('template.administrador')

@section('title', 'USUARIOS')
@section('name-section', 'USUARIOS')

@section('description', 'Nuevo registro de usuario')
@section('item-breadcrumb', 'Nuevo')
@section('section-breadcrumb', 'Usuarios')
@section('module-breadcrumb', 'Configuraciones')


@section('content')
<div class="card bg-white border-0 shadow-sm border-5 border-top border-secondary">
    <div class="card-body">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            @include('users._form')
        </form>
    </div>
</div>

@endsection
