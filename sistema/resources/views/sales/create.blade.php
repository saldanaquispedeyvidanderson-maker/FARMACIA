@extends('template.administrador')

@section('title', 'VENTAS')
@section('name-section', 'VENTAS')

@section('description', 'Nueva venta')
@section('item-breadcrumb', 'Nuevo')
@section('section-breadcrumb', 'Ventas')
@section('module-breadcrumb', 'Gestión')


@section('content')
<div class="card bg-white border-0 shadow-sm border-5 border-top border-secondary">
    <div class="card-body">
        <form action="{{ route('sales.store') }}" method="POST" id="sale-form">
            @csrf

            <div class="row">
                <div class="col-12 col-md-4 mb-3">
                    <label for="customer_id" class="form-label">Cliente</label>
                    <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                        <option value="">Seleccione un cliente</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }} - DNI {{ $customer->dni }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-4 mb-3">
                    <label for="user_id" class="form-label">Vendedor</label>
                    <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror">
                        <option value="">Seleccione un vendedor</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string) old('user_id') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-4 mb-3">
                    <label for="date" class="form-label">Fecha y hora</label>
                    <input type="datetime-local" name="date" id="date"
                        class="form-control @error('date') is-invalid @enderror"
                        value="{{ old('date', now()->format('Y-m-d\TH:i')) }}">
                    @error('date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <h6 class="text-uppercase small text-muted mt-2">Medicamentos</h6>

            @if($errors->has('items') || $errors->has('items.*'))
                <div class="alert alert-danger py-2 small">
                    @foreach($errors->get('items') as $message)<div>{{ $message }}</div>@endforeach
                    @foreach($errors->get('items.*') as $messages)
                        @foreach($messages as $message)<div>{{ $message }}</div>@endforeach
                    @endforeach
                </div>
            @endif

            <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th class="small text-uppercase">Medicamento</th>
                        <th class="small text-uppercase text-end" style="width:130px">Precio</th>
                        <th class="small text-uppercase" style="width:130px">Cantidad</th>
                        <th class="small text-uppercase text-end" style="width:130px">Subtotal</th>
                        <th style="width:50px"></th>
                    </tr>
                </thead>
                <tbody id="items-body"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end text-muted">IGV incluido</td>
                        <td class="text-end">S/ <span id="sale-igv">0.00</span></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">TOTAL</td>
                        <td class="text-end fw-bold">S/ <span id="sale-total">0.00</span></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            </div>

            <button type="button" id="add-item" class="btn btn-outline-primary btn-sm mb-4">
                <i class="bi bi-plus-lg me-1"></i>Agregar medicamento
            </button>

            <div>
                <button type="submit" class="btn btn-primary text-white">Registrar venta</button>
                <a href="{{ route('sales.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<template id="item-template">
    <tr>
        <td>
            <select name="items[__INDEX__][medicine_id]" class="form-select form-select-sm item-medicine" required>
                <option value="">Seleccione</option>
                @foreach($medicines as $medicine)
                    <option value="{{ $medicine->id }}" data-price="{{ $medicine->price }}" data-stock="{{ $medicine->stock }}">{{ $medicine->name }} (stock: {{ $medicine->stock }})</option>
                @endforeach
            </select>
        </td>
        <td class="text-end">S/ <span class="item-price">0.00</span></td>
        <td><input type="number" name="items[__INDEX__][amount]" class="form-control form-control-sm item-amount" min="1" step="1" value="1" required></td>
        <td class="text-end">S/ <span class="item-subtotal">0.00</span></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item" aria-label="Quitar"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
(function () {
    const IGV = {{ \App\Models\Sale::IGV_RATE }};
    const body = document.getElementById('items-body');
    const tpl = document.getElementById('item-template');
    const oldItems = @json(array_values(old('items', [])));
    let index = 0;

    const money = (n) => (Math.round(n * 100) / 100).toFixed(2);

    function recalc() {
        let total = 0;
        body.querySelectorAll('tr').forEach((tr) => {
            const select = tr.querySelector('.item-medicine');
            const option = select.options[select.selectedIndex];
            const price = parseFloat(option && option.dataset.price ? option.dataset.price : 0);
            const stock = parseInt(option && option.dataset.stock ? option.dataset.stock : 0, 10);
            const amountInput = tr.querySelector('.item-amount');
            const amount = parseInt(amountInput.value || 0, 10);

            if (stock) { amountInput.max = stock; } else { amountInput.removeAttribute('max'); }

            const subtotal = price * amount;
            tr.querySelector('.item-price').textContent = money(price);
            tr.querySelector('.item-subtotal').textContent = money(subtotal);
            total += subtotal;
        });
        document.getElementById('sale-total').textContent = money(total);
        document.getElementById('sale-igv').textContent = money(total - total / (1 + IGV));
    }

    function addRow(item) {
        const holder = document.createElement('tbody');
        holder.innerHTML = tpl.innerHTML.split('__INDEX__').join(index++);
        const tr = holder.firstElementChild;
        body.appendChild(tr);
        if (item) {
            tr.querySelector('.item-medicine').value = item.medicine_id || '';
            tr.querySelector('.item-amount').value = item.amount || 1;
        }
        recalc();
    }

    document.getElementById('add-item').addEventListener('click', () => addRow());
    body.addEventListener('input', recalc);
    body.addEventListener('change', recalc);
    body.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-item');
        if (btn) { btn.closest('tr').remove(); recalc(); }
    });

    document.getElementById('sale-form').addEventListener('submit', (e) => {
        if (!body.querySelector('tr')) {
            e.preventDefault();
            alert('Agrega al menos un medicamento a la venta.');
        }
    });

    if (oldItems.length) { oldItems.forEach(addRow); } else { addRow(); }
})();
</script>
@endpush
