<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

// Import Controllers untuk Aplikasi Jasa RSUD
use App\Http\Controllers\pages\inputJasaControllers;
use App\Http\Controllers\pages\jasaRuanganControllers;
use App\Http\Controllers\pages\laporanJasaControllers;
use App\Http\Controllers\pages\masterRuanganControllers;
use App\Http\Controllers\pages\pegawaiControllers;
use App\Http\Controllers\admin\userManagementControllers;
use App\Http\Controllers\pages\dashboardControllers;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Halaman Awal (Landing Page)
// Anda bisa mengubah ini nanti menjadi return redirect()->route('login'); jika tidak butuh halaman depan
Route::get('/', function () {
    return view('/auth/login');
});

/*
|--------------------------------------------------------------------------
| Rute Aplikasi (WAJIB LOGIN & VERIFIKASI EMAIL)
|--------------------------------------------------------------------------
| Semua rute di dalam grup ini benar-benar aman. Hanya pengguna yang
| sudah punya akun dan mengklik link verifikasi di email yang bisa masuk.
*/
Route::middleware(['auth', 'verified', 'active'])->group(function () {

    // ----------------------------------------------------
    // 1. RUTE BERSAMA (Bisa diakses Admin & KARU)
    // ----------------------------------------------------
    // Ubah dari Route::get('/dashboard', function...) menjadi:
    Route::get('/dashboard', [dashboardControllers::class, 'index'])
        ->name('dashboard');

    // -- Rute Isi Jasa (KARU) --
    Route::get('/isi-jasa', [jasaRuanganControllers::class, 'index'])->name('karu.jasa');
    Route::post('/isi-jasa/store', [jasaRuanganControllers::class, 'store'])->name('karu.jasa.store');
    Route::post('/isi-jasa/submit', [jasaRuanganControllers::class, 'submit'])->name('karu.jasa.submit');

    // -- Rute CRUD Master Pegawai --
    Route::get('/pegawai', [pegawaiControllers::class, 'index'])->name('pegawai.index');
    Route::get('/pegawai/create', [pegawaiControllers::class, 'create'])->name('pegawai.create');
    Route::post('/pegawai', [pegawaiControllers::class, 'store'])->name('pegawai.store');
    Route::get('/pegawai/{id}/edit', [pegawaiControllers::class, 'edit'])->name('pegawai.edit');
    Route::put('/pegawai/{id}', [pegawaiControllers::class, 'update'])->name('pegawai.update');
    Route::delete('/pegawai/{id}', [pegawaiControllers::class, 'destroy'])->name('pegawai.destroy');


    // ----------------------------------------------------
    // 2. RUTE KHUSUS ADMIN (KARU tidak bisa masuk)
    // ----------------------------------------------------
    Route::middleware(['role:admin'])->group(function () {


        Route::get('/users-management', [userManagementControllers::class, 'index'])->name('users.index');
        Route::patch('/users/{id}/toggle', [userManagementControllers::class, 'toggleActive'])->name('users.toggle');


        // -- Rute Input Jasa (Master / Admin) --
        Route::get('/jasa', [inputJasaControllers::class, 'index'])->name('jasa.index');
        Route::post('/jasa/store-total', [inputJasaControllers::class, 'storeTotal'])->name('jasa.storeTotal');
        Route::post('/jasa/store-pembagian', [inputJasaControllers::class, 'storePembagian'])->name('jasa.storePembagian');
        Route::delete('/jasa/periode/{id}', [inputJasaControllers::class, 'destroyPeriode'])->name('jasa.destroyPeriode');
        Route::post('/jasa/selesai-pembagian/{periodeId}', [inputJasaControllers::class, 'selesaiPembagian'])->name('jasa.selesaiPembagian');

        // -- Rute Laporan Jasa (Manajemen) --
        Route::get('/laporan/jasa', [laporanJasaControllers::class, 'index'])->name('laporan.jasa.index');
        Route::get('/laporan/jasa/export', [laporanJasaControllers::class, 'exportExcel'])->name('laporan.jasa.export');

        // -- Rute Master Ruangan --
        Route::get('/master-ruangan', [masterRuanganControllers::class, 'index'])->name('master.ruangan.index');
        Route::post('/master-ruangan/store', [masterRuanganControllers::class, 'store'])->name('master.ruangan.store');
        Route::post('/master-ruangan/update-bulk', [masterRuanganControllers::class, 'updateBulk'])->name('master.ruangan.updateBulk');
        Route::delete('/master-ruangan/{id}', [masterRuanganControllers::class, 'destroy'])->name('master.ruangan.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Rute Profile Bawaan Breeze
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| File Auth Routes
|--------------------------------------------------------------------------
| Memanggil semua rute login, register, reset password, dll bawaan Breeze.
*/
require __DIR__ . '/auth.php';
