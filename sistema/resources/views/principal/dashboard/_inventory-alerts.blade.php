<p class="small text-muted mb-3">Stock bajo: 1–{{ $lowStockLimit }} unidades · Agotados: 0</p>
<div class="list-group list-group-flush">
    @forelse($inventoryAlerts as $medicine)
        <a href="{{ route('medicines.index') }}" class="list-group-item list-group-item-action px-0 d-flex justify-content-between align-items-center gap-3">
            <span class="min-w-0">
                <span class="d-block fw-medium text-truncate">{{ $medicine->name }}</span>
                <span class="small text-muted">{{ $medicine->category->name }}</span>
            </span>
            <span class="badge {{ $medicine->stock === 0 ? 'text-bg-danger' : 'text-bg-warning' }} flex-shrink-0">
                {{ $medicine->stock === 0 ? 'Agotado' : $medicine->stock.' unidades' }}
            </span>
        </a>
    @empty
        <p class="small text-muted mb-0">No hay medicamentos con stock bajo.</p>
    @endforelse
</div>
<p class="small text-muted mt-3 mb-0">No se registran fechas de vencimiento en la base de datos.</p>
