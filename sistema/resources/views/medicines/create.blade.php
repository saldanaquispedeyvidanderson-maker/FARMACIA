@extends('template.administrador')

@section('title', 'MEDICAMENTOS')
@section('name-section', 'MEDICAMENTOS')

@section('description', 'Nuevo registro de medicamento')
@section('item-breadcrumb', 'Nuevo')
@section('section-breadcrumb', 'Medicamentos')
@section('module-breadcrumb', 'Gestión')


@section('content')
<div class="card bg-white border-0 shadow-sm border-5 border-top border-secondary">
    <div class="card-body">
        <form action="{{ route('medicines.store') }}" method="POST">
            @csrf
            @include('medicines._form')
        </form>
    </div>
</div>

@endsection
