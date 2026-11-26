<!-- MODAL — Mi Perfil -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="profileModalLabel">Mi Perfil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="text-center mb-4">
            @if(file_exists(public_path('avatars/' . auth()->id() . '.png')))
              <img src="{{ asset('avatars/' . auth()->id() . '.png') }}?v={{ time() }}" alt="Foto de perfil" class="rounded-circle object-fit-cover border shadow-sm mb-2" style="width: 72px; height: 72px;">
            @else
              <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold fs-4 mb-2" style="width: 72px; height: 72px;">
                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
              </div>
            @endif
            <h6 class="fw-bold mb-0">{{ auth()->user()->name }}</h6>
            <span class="badge bg-primary-subtle text-primary rounded-pill mt-1">{{ auth()->user()->role->name }}</span>
          </div>

          <div class="mb-3">
            <label for="profile_avatar" class="form-label small fw-semibold">Foto de perfil</label>
            <input type="file" class="form-control form-control-sm" id="profile_avatar" name="avatar" accept="image/*">
          </div>

          <div class="mb-3">
            <label for="profile_name" class="form-label small fw-semibold">Nombre completo</label>
            <input type="text" class="form-control" id="profile_name" name="name" value="{{ old('name', auth()->user()->name) }}" required>
          </div>

          <div class="mb-3">
            <label for="profile_email" class="form-label small fw-semibold">Correo electrónico</label>
            <input type="email" class="form-control" id="profile_email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

