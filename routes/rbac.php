<?php

use App\Http\Controllers\admin\MenuController;
use App\Http\Controllers\admin\ModuleController;
use App\Http\Controllers\admin\PermissionController;
use App\Http\Controllers\admin\RoleController;
use App\Http\Controllers\admin\UserOverrideController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RBAC ADMIN (modules, permissions, roles, menus, user overrides)
|--------------------------------------------------------------------------
| File ini di-require terpisah dari routes/web.php supaya rapi.
| Cara pasang: tambahkan baris berikut di paling bawah routes/web.php,
| DI LUAR closure group ['auth','active','verified'] yang sudah ada
| (file ini sudah bawa middleware sendiri):
|
|   require __DIR__ . '/rbac.php';
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'verified', 'permission:rbac.manage'])
    ->prefix('rbac')
    ->name('rbac.')
    ->group(function () {

        // Modules
        Route::get('modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::post('modules', [ModuleController::class, 'store'])->name('modules.store');
        Route::put('modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
        Route::delete('modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');

        // Permissions
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        // Roles (+ assign permission dalam satu form edit)
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Menus
        Route::get('menus', [MenuController::class, 'index'])->name('menus.index');
        Route::post('menus', [MenuController::class, 'store'])->name('menus.store');
        Route::put('menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
        Route::delete('menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');

        // Override permission per-user
        Route::get('overrides', [UserOverrideController::class, 'index'])->name('overrides.index');
        Route::get('overrides/{user}', [UserOverrideController::class, 'edit'])->name('overrides.edit');
        Route::put('overrides/{user}', [UserOverrideController::class, 'update'])->name('overrides.update');
    });
