<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

// Controllers
use App\Http\Controllers\pages\dashboardControllers;
use App\Http\Controllers\pages\inputJasaControllers;
use App\Http\Controllers\pages\jasaRuanganControllers;
use App\Http\Controllers\pages\laporanJasaControllers;
use App\Http\Controllers\pages\masterRuanganControllers;
use App\Http\Controllers\pages\pegawaiControllers;
use App\Http\Controllers\admin\userManagementControllers;
use GuzzleHttp\Middleware;
use App\Http\Controllers\pages\IndexScoringControllers;
use App\Http\Controllers\pages\ManagementIndexScoringControllers;

/*
|--------------------------------------------------------------------------
| ROOT
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('auth.login');
});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD (ALL ROLE)
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [dashboardControllers::class, 'index'])
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | KARU + KOORDINATOR KARU (READ ONLY AREA)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:karu,koordinator_karu,admin'])->group(function () {

        Route::get('/isi-jasa', [jasaRuanganControllers::class, 'index'])
            ->name('karu.jasa');

        // Isi Jasa
        Route::post('/isi-jasa/store', [jasaRuanganControllers::class, 'store'])
            ->name('karu.jasa.store');

        Route::post('/isi-jasa/submit', [jasaRuanganControllers::class, 'submit'])
            ->name('karu.jasa.submit');

        Route::delete('/jasa-pegawai/{id}/hapus-draft', [jasaRuanganControllers::class, 'hapusDariDraft'])
            ->name('jasa.pegawai.hapusDraft');
    });




    /*
    |--------------------------------------------------------------------------
    | MASTER INPUT JASA (ADMIN ONLY)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {

        Route::get('/jasa', [inputJasaControllers::class, 'index'])
            ->name('jasa.index');

        Route::post('/jasa/store-total', [inputJasaControllers::class, 'storeTotal'])
            ->name('jasa.storeTotal');

        Route::post('/jasa/store-pembagian', [inputJasaControllers::class, 'storePembagian'])
            ->name('jasa.storePembagian');

        Route::post('/jasa/selesai-pembagian/{periodeId}', [inputJasaControllers::class, 'selesaiPembagian'])
            ->name('jasa.selesaiPembagian');

        Route::delete('/jasa/periode/{id}', [inputJasaControllers::class, 'destroyPeriode'])
            ->name('jasa.destroyPeriode');
    });


    /*
    |--------------------------------------------------------------------------
    | LAPORAN (ADMIN ONLY)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {

        Route::get('/laporan/jasa', [laporanJasaControllers::class, 'index'])
            ->name('laporan.jasa.index');

        Route::get('/laporan/jasa/export', [laporanJasaControllers::class, 'exportExcel'])
            ->name('laporan.jasa.export');
    });


    /*
    |--------------------------------------------------------------------------
    | MASTER RUANGAN (ADMIN ONLY)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {

        Route::get('/master-ruangan', [masterRuanganControllers::class, 'index'])
            ->name('master.ruangan.index');

        Route::post('/master-ruangan/store', [masterRuanganControllers::class, 'store'])
            ->name('master.ruangan.store');

        Route::post('/master-ruangan/update-bulk', [masterRuanganControllers::class, 'updateBulk'])
            ->name('master.ruangan.updateBulk');

        Route::delete('/master-ruangan/{id}', [masterRuanganControllers::class, 'destroy'])
            ->name('master.ruangan.destroy');

        Route::get('/master-ruangan/{id}/toggle-status', [masterRuanganControllers::class, 'toggleStatus'])
            ->name('master.ruangan.toggleStatus');
    });

    Route::middleware(['role:admin,manajemen'])->group(function () {

        Route::get('/review-index-scoring', [ManagementIndexScoringControllers::class, 'index'])
            ->name('management.index.scoring.index');

        Route::get('/review-index-scoring/{periode}', [ManagementIndexScoringControllers::class, 'show'])
            ->name('management.index.scoring.show');

        Route::post('/review-index-scoring/{periode}/approve', [ManagementIndexScoringControllers::class, 'approve'])
            ->name('management.index.scoring.approve');

        Route::post('/review-index-scoring/{periode}/revisi', [ManagementIndexScoringControllers::class, 'revisi'])
            ->name('management.index.scoring.revisi');
    });



    Route::middleware(['role:karu,koordinator_karu,admin'])->group(function () {

        Route::get('/index-scoring', [IndexScoringControllers::class, 'index'])
            ->name('index.scoring.index');

        Route::post('/index-scoring/store', [IndexScoringControllers::class, 'store'])
            ->name('index.scoring.store');

        Route::get('/index-scoring/create', [IndexScoringControllers::class, 'create'])
            ->name('index.scoring.create');

        Route::post('/index-scoring/update-bulk', [IndexScoringControllers::class, 'updateBulk'])
            ->name('index.scoring.updateBulk');

        Route::post('/index-scoring/submit', [IndexScoringControllers::class, 'submit'])
            ->name('index.scoring.submit');

        Route::delete('/index-scoring/{id}', [IndexScoringControllers::class, 'destroy'])
            ->name('index.scoring.destroy');
    });
    /*
    |--------------------------------------------------------------------------
    | USER MANAGEMENT (ADMIN ONLY)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {

        Route::get('/users-management', [userManagementControllers::class, 'index'])
            ->name('users.index');

        Route::patch('/users/{id}/toggle', [userManagementControllers::class, 'toggleActive'])
            ->name('users.toggle');

        Route::patch('/users/{id}/role', [userManagementControllers::class, 'updateRole'])
            ->name('users.updateRole');
    });


    /*
    |--------------------------------------------------------------------------
    | PEGAWAI (ADMIN + KARU READ)
    |--------------------------------------------------------------------------
    */
    // Route::get('/pegawai', [pegawaiControllers::class, 'index'])->name('pegawai.index');
    // Route::get('/pegawai/create', [pegawaiControllers::class, 'create'])->name('pegawai.create');
    // Route::post('/pegawai', [pegawaiControllers::class, 'store'])->name('pegawai.store');
    // Route::get('/pegawai/{id}/edit', [pegawaiControllers::class, 'edit'])->name('pegawai.edit');
    // Route::put('/pegawai/{id}', [pegawaiControllers::class, 'update'])->name('pegawai.update');
    // Route::delete('/pegawai/{id}', [pegawaiControllers::class, 'destroy'])->name('pegawai.destroy');

    // ===============================
    // MASTER PEGAWAI
    // ===============================

    Route::get('/pegawai', [pegawaiControllers::class, 'index'])
        ->name('pegawai.index');

    Route::post('/pegawai', [pegawaiControllers::class, 'store'])
        ->name('pegawai.store');

    Route::put('/pegawai/{id}', [pegawaiControllers::class, 'update'])
        ->name('pegawai.update');

    Route::delete('/pegawai/{id}', [pegawaiControllers::class, 'destroy'])
        ->name('pegawai.destroy');

    // IMPORT EXCEL
    Route::post('/pegawai/import', [pegawaiControllers::class, 'import'])
        ->name('pegawai.import');

    Route::get('/pegawai/export', [pegawaiControllers::class, 'export'])
        ->name('pegawai.export');

    Route::post(
        '/pegawai/{id}/id-petugas',
        [pegawaiControllers::class, 'updateIdPetugas']
    )->name('pegawai.update-id-petugas');

    Route::post('/ruangan/{id}/resiko-emergency', [pegawaiControllers::class, 'updateRuanganResikoEmergency'])
        ->name('ruangan.updateResikoEmergency');

    Route::post('/pegawai/{id}/pindah', [pegawaiControllers::class, 'ajukanPindah'])->name('pegawai.pindah');
    Route::post('/pegawai/{id}/pindah/batal', [pegawaiControllers::class, 'batalkanPindah'])->name('pegawai.pindah.batal');

    Route::get('/ruang-tunggu', [pegawaiControllers::class, 'ruangTunggu'])->name('ruang-tunggu.index');
    Route::post('/ruang-tunggu/{id}/terima', [pegawaiControllers::class, 'terimaPindah'])->name('ruang-tunggu.terima');
    Route::post('/ruang-tunggu/{id}/tolak', [pegawaiControllers::class, 'tolakPindah'])->name('ruang-tunggu.tolak');
});


/*
|--------------------------------------------------------------------------
| PROFILE (DEFAULT BREEZE)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__ . '/auth.php';
