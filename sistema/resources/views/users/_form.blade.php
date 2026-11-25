<div class="mb-3">
    <label for="role_id" class="form-label">Rol</label>
    <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror">
        <option value="">Seleccione un rol</option>
        @foreach($roles as $role)
            <option value="{{ $role->id }}" {{ (string) old('role_id', $user->role_id ?? '') === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
        @endforeach
    </select>
    @error('role_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="name" class="form-label">Nombre completo</label>
    <input type="text" name="name" id="name"
        class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $user->name ?? '') }}" maxlength="255">
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="dni" class="form-label">DNI</label>
    <input type="text" name="dni" id="dni"
        class="form-control @error('dni') is-invalid @enderror"
        value="{{ old('dni', $user->dni ?? '') }}" maxlength="8" inputmode="numeric">
    @error('dni')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Teléfono</label>
    <input type="text" name="phone" id="phone"
        class="form-control @error('phone') is-invalid @enderror"
        value="{{ old('phone', $user->phone ?? '') }}" maxlength="15">
    @error('phone')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Correo electrónico</label>
    <input type="email" name="email" id="email"
        class="form-control @error('email') is-invalid @enderror"
        value="{{ old('email', $user->email ?? '') }}">
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="password" class="form-label">Contraseña @isset($user)<span class="text-muted small">(déjala vacía para no cambiarla)</span>@endisset</label>
    <input type="password" name="password" id="password"
        class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
    @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
    <input type="password" name="password_confirmation" id="password_confirmation"
        class="form-control" autocomplete="new-password">
</div>

<button type="submit" class="btn btn-primary text-white">Guardar</button>
<a href="{{ route('users.index') }}" class="btn btn-secondary">Cancelar</a>
