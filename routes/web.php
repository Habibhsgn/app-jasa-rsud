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
use App\Http\Controllers\pages\InacbgController;
use App\Http\Controllers\admin\SettingController;

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
    | DASHBOARD (ALL ROLE — tidak perlu permission, siapa saja yang login boleh)
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [dashboardControllers::class, 'index'])
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | ISI JASA RUANGAN (karu, koordinator_karu, admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:karu.jasa'])->group(function () {

        Route::get('/isi-jasa', [jasaRuanganControllers::class, 'index'])
            ->name('karu.jasa');

        Route::post('/isi-jasa/store', [jasaRuanganControllers::class, 'store'])
            ->name('karu.jasa.store');

        Route::post('/isi-jasa/submit', [jasaRuanganControllers::class, 'submit'])
            ->name('karu.jasa.submit');

        Route::delete('/jasa-pegawai/{id}/hapus-draft', [jasaRuanganControllers::class, 'hapusDariDraft'])
            ->name('jasa.pegawai.hapusDraft');
    });


    /*
    |--------------------------------------------------------------------------
    | MASTER INPUT JASA (admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:jasa.index'])->group(function () {

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
    | LAPORAN (admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:laporan.jasa.index'])->group(function () {

        Route::get('/laporan/jasa', [laporanJasaControllers::class, 'index'])
            ->name('laporan.jasa.index');

        Route::get('/laporan/jasa/export', [laporanJasaControllers::class, 'exportExcel'])
            ->name('laporan.jasa.export');
    });


    /*
    |--------------------------------------------------------------------------
    | MASTER RUANGAN (admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:master.ruangan.index'])->group(function () {

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

    /*
    |--------------------------------------------------------------------------
    | VERIFIKASI / REVIEW INDEX SCORING (admin, manajemen)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:management.index.scoring.index'])->group(function () {

        Route::get('/review-index-scoring', [ManagementIndexScoringControllers::class, 'index'])
            ->name('management.index.scoring.index');

        Route::get('/review-index-scoring/{periode}', [ManagementIndexScoringControllers::class, 'show'])
            ->name('management.index.scoring.show');

        Route::post('/review-index-scoring/{periode}/{ruangan}/approve', [ManagementIndexScoringControllers::class, 'approve'])
            ->name('management.index.scoring.approve');

        Route::post('/review-index-scoring/{periode}/{ruangan}/revisi', [ManagementIndexScoringControllers::class, 'revisi'])
            ->name('management.index.scoring.revisi');
    });


    /*
    |--------------------------------------------------------------------------
    | INDEX SCORING (karu, koordinator_karu, admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:index.scoring.index'])->group(function () {

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
    | USER MANAGEMENT (admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:users.index'])->group(function () {

        Route::get('/users-management', [userManagementControllers::class, 'index'])
            ->name('users.index');

        Route::patch('/users/{id}/toggle', [userManagementControllers::class, 'toggleActive'])
            ->name('users.toggle');

        Route::patch('/users/{id}/role', [userManagementControllers::class, 'updateRole'])
            ->name('users.updateRole');
    });


    /*
    |--------------------------------------------------------------------------
    | MASTER PEGAWAI
    |--------------------------------------------------------------------------
    | NOTE: sebelumnya grup ini TIDAK punya middleware role sama sekali —
    | siapa saja yang login (termasuk role apa pun) bisa store/update/delete.
    | Saya kunci ke permission 'pegawai.index'. Kalau memang mau karu cuma
    | bisa BACA (index) tapi tidak boleh store/update/delete, kabari saya,
    | nanti dipecah jadi permission terpisah (pegawai.store, pegawai.update, dst).
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:pegawai.index'])->group(function () {

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

        Route::post('/pegawai/{id}/pindah', [pegawaiControllers::class, 'ajukanPindah'])
            ->name('pegawai.pindah');

        Route::post('/pegawai/{id}/pindah/batal', [pegawaiControllers::class, 'batalkanPindah'])
            ->name('pegawai.pindah.batal');
    });

    /*
    |--------------------------------------------------------------------------
    | RUANG TUNGGU PINDAH PEGAWAI
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:ruang-tunggu.index'])->group(function () {

        Route::get('/ruang-tunggu', [pegawaiControllers::class, 'ruangTunggu'])
            ->name('ruang-tunggu.index');

        Route::post('/ruang-tunggu/{id}/terima', [pegawaiControllers::class, 'terimaPindah'])
            ->name('ruang-tunggu.terima');

        Route::post('/ruang-tunggu/{id}/tolak', [pegawaiControllers::class, 'tolakPindah'])
            ->name('ruang-tunggu.tolak');
    });

    /*
    |--------------------------------------------------------------------------
    | INA-CBG
    |--------------------------------------------------------------------------
    | NOTE: sama seperti Pegawai — sebelumnya tanpa middleware role sama
    | sekali. Sekarang dikunci ke permission 'inacbg.index'.
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:inacbg.index'])->prefix('inacbg')->name('inacbg.')->group(function () {
        Route::get('/', [InacbgController::class, 'index'])->name('index');
        Route::post('/import', [InacbgController::class, 'import'])->name('import');
        Route::get('/{inacbgClaim}', [InacbgController::class, 'show'])->name('show');
        Route::put('/{inacbgClaim}/status', [InacbgController::class, 'updateStatus'])->name('update-status');
    });

    /*
    |--------------------------------------------------------------------------
    | SETTING (admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:setting.index'])->prefix('setting')->name('setting.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
    });
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
require __DIR__ . '/rbac.php'; 
