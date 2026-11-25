<div class="mb-3">
    <label for="name" class="form-label">Nombre completo</label>
    <input type="text" name="name" id="name"
        class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $customer->name ?? '') }}" maxlength="50">
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="dni" class="form-label">DNI</label>
    <input type="text" name="dni" id="dni"
        class="form-control @error('dni') is-invalid @enderror"
        value="{{ old('dni', $customer->dni ?? '') }}" maxlength="8" inputmode="numeric">
    @error('dni')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Teléfono</label>
    <input type="text" name="phone" id="phone"
        class="form-control @error('phone') is-invalid @enderror"
        value="{{ old('phone', $customer->phone ?? '') }}" maxlength="9" inputmode="numeric">
    @error('phone')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="age" class="form-label">Edad</label>
    <input type="number" name="age" id="age"
        class="form-control @error('age') is-invalid @enderror"
        value="{{ old('age', $customer->age ?? '') }}" min="0" max="120">
    @error('age')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-primary text-white">Guardar</button>
<a href="{{ route('customers.index') }}" class="btn btn-secondary">Cancelar</a>
