<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

// Rutas públicas de autenticación.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Actualizar perfil del usuario autenticado
    Route::put('/perfil', function (Request $request) {
        $user = $request->user();
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $request->file('avatar')->move(public_path('avatars'), $user->id . '.png');
        }

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        return back()->with('ok', 'Perfil actualizado correctamente.');
    })->name('profile.update');

    // Cambiar contraseña del usuario autenticado
    Route::put('/perfil/password', function (Request $request) {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'min:8', 'confirmed'],
        ]);
        $request->user()->update(['password' => bcrypt($request->password)]);
        return back()->with('ok', 'Contraseña actualizada.');
    })->name('password.update');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    // Gestión de medicamentos y reportes para los roles autorizados.
    Route::middleware('role:Administrador,Químico farmacéutico')->group(function () {
        Route::resource('medicines', MedicineController::class)->except('show');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    });

    // La administración del sistema queda reservada al Administrador.
    Route::middleware('role:Administrador')->group(function () {
        Route::resource('roles', RoleController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::delete('sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
    });

    // Clientes y operaciones de venta siguen disponibles para todo usuario autenticado.
    Route::resource('customers', CustomerController::class)->except('show');
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
});
