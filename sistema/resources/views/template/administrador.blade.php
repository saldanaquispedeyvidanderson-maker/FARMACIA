<!doctype html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="San Juan – Sistema de Venta de Medicamentos">
  <title>FARM SJB | @yield('title')</title>
  <meta name="theme-color" content="#e91e63">
  <link rel="manifest" href="{{ asset('manifest.json') }}">
  <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
  <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ time()}}">
  @stack('styles')
</head>

<body>

<div id="app-wrapper" class="d-flex">

  <!-- sidebar -->
   @include('partials.sidebar')
   

  <div class="main-wrapper d-flex flex-column flex-grow-1 overflow-hidden">
  <!-- header -->
   @include('partials.header')


    <main class="app-content flex-grow-1 overflow-y-auto p-3 p-lg-4">
      <div class="container mx-auto" style="max-width: 1100px;">
        <div class="d-flex align-items-center justify-content-between flex-column flex-sm-row gap-2 mb-4">

          <p class="text-secondary mb-0">@yield('description')</p>

          <nav style="--bs-breadcrumb-divider: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://w3.org' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&quot;);" aria-label="Ruta de navegación">
            <ol class="breadcrumb mb-0 small">
              <li class="breadcrumb-item">
                  <span>@yield('item-breadcrumb')</span>
              </li>
               <li class="breadcrumb-item text-muted fw-medium" aria-current="page">
                <a href="" class="text-decoration-none text-secondary d-flex align-items-center gap-1">
                  @yield('section-breadcrumb')
                </a>
              </li>
              <li class="breadcrumb-item">
                <span class="text-secondary">@yield('module-breadcrumb')</span>
              </li>
            </ol>
          </nav>
        </div>


        <!-- 
            CONTENIDO DE LA VISTA
            Aquí va el contenido específico de cada página
            (KPIs, gráficos, tablas, formularios, etc.)
            En Laravel: 
        -->
            @if(session('ok'))
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('ok') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
              </div>
            @endif
            @if(session('error'))
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
              </div>
            @endif

            @yield('content')

      </div>
    </main>

  </div>
</div>

<!-- MODAL — Confirmar Cierre de Sesión -->
@include('partials.modal-logout')

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
          <div class="text-center mb-3">
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

          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small text-muted fw-semibold">Datos personales</span>
            <button type="button" id="btnEnableProfileEdit" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="enableProfileFields()">
              <i class="bi bi-pencil-square me-1"></i>Editar
            </button>
          </div>

          <div class="mb-3">
            <label for="profile_name" class="form-label small fw-semibold">Nombre completo</label>
            <input type="text" class="form-control bg-light text-muted" id="profile_name" name="name" value="{{ old('name', auth()->user()->name) }}" readonly required>
          </div>

          <div class="mb-3">
            <label for="profile_email" class="form-label small fw-semibold">Correo electrónico</label>
            <input type="email" class="form-control bg-light text-muted" id="profile_email" name="email" value="{{ old('email', auth()->user()->email) }}" readonly required>
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

<script>
function enableProfileFields() {
  const nameInput = document.getElementById('profile_name');
  const emailInput = document.getElementById('profile_email');
  const btn = document.getElementById('btnEnableProfileEdit');

  nameInput.removeAttribute('readonly');
  emailInput.removeAttribute('readonly');
  nameInput.classList.remove('bg-light', 'text-muted');
  emailInput.classList.remove('bg-light', 'text-muted');
  nameInput.focus();

  btn.classList.replace('btn-outline-primary', 'btn-success');
  btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Editando';
}

document.getElementById('profileModal')?.addEventListener('hidden.bs.modal', () => {
  const nameInput = document.getElementById('profile_name');
  const emailInput = document.getElementById('profile_email');
  const btn = document.getElementById('btnEnableProfileEdit');

  nameInput.setAttribute('readonly', true);
  emailInput.setAttribute('readonly', true);
  nameInput.classList.add('bg-light', 'text-muted');
  emailInput.classList.add('bg-light', 'text-muted');

  btn.classList.replace('btn-success', 'btn-outline-primary');
  btn.innerHTML = '<i class="bi bi-pencil-square me-1"></i>Editar';
});
</script>



<!-- MODAL PREFERENCIAS -->
<div class="modal fade" id="preferencesModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow rounded-4 p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Preferencias</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <label class="form-label small text-muted fw-semibold mb-1">Ambiente visual</label>
      <div class="d-flex gap-2 mb-3">
        <button type="button" class="btn btn-outline-primary btn-sm w-50" onclick="setPref('theme','normal')">✨ Normal</button>
        <button type="button" class="btn btn-outline-warning text-dark btn-sm w-50" onclick="setPref('theme','sunny')">☀️ Soleado</button>
      </div>

      <label class="form-label small text-muted fw-semibold mb-1">Tamaño de letra</label>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="setPref('font','14px')">Pequeña</button>
        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="setPref('font','16px')">Mediana</button>
        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="setPref('font','18px')">Grande</button>
      </div>
    </div>
  </div>
</div>

<script>
function setPref(k, v) {
  localStorage.setItem('farm_' + k, v);
  if (k === 'theme') {
    document.body.classList.toggle('theme-sunny', v === 'sunny');
  }
  if (k === 'font') {
    document.documentElement.style.fontSize = v;
  }
}
setPref('theme', localStorage.getItem('farm_theme') || 'normal');
setPref('font', localStorage.getItem('farm_font') || '16px');
</script>

<script src="https://jquery.com"></script>
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js');
    });
  }
</script>

</body>
</html>
