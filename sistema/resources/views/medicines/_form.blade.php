<div class="mb-3">
    <label for="category_id" class="form-label">Categoría</label>
    <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror">
        <option value="">Seleccione una categoría</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ (string) old('category_id', $medicine->category_id ?? '') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
        @endforeach
    </select>
    @error('category_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="name" class="form-label">Nombre del medicamento</label>
    <input type="text" name="name" id="name"
        class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $medicine->name ?? '') }}" maxlength="120">
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="price" class="form-label">Precio (S/)</label>
    <input type="number" name="price" id="price"
        class="form-control @error('price') is-invalid @enderror"
        value="{{ old('price', $medicine->price ?? '') }}" min="0" step="0.01">
    @error('price')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="stock" class="form-label">Stock</label>
    <input type="number" name="stock" id="stock"
        class="form-control @error('stock') is-invalid @enderror"
        value="{{ old('stock', $medicine->stock ?? '') }}" min="0" step="1">
    @error('stock')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-primary text-white">Guardar</button>
<a href="{{ route('medicines.index') }}" class="btn btn-secondary">Cancelar</a>
