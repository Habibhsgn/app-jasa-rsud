<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JasaRuangan;
use App\Models\Pegawai;
use App\Models\Ruangan;
use Illuminate\Support\Facades\Auth;

class dashboardControllers extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $queryJasa = JasaRuangan::with(['periode', 'ruangan'])
            ->whereIn('status', [
                'proses_karu',
                'verifikasi',
                'selesai'
            ]);

        $pegawaiQuery = Pegawai::query();
        $ruanganCount = Ruangan::count();

        // =========================
        // ROLE FILTER FIX
        // =========================

        if ($user->role?->code === 'koordinator_karu') {

            $queryJasa->where('ruangan_id', $user->ruangan_id);
            $pegawaiQuery->where('ruangan_id', $user->ruangan_id);

            $labelPegawai = "Pegawai di Ruangan Anda";
        }

        elseif ($user->role?->code === 'karu') {

            // 🔥 KOORDINATOR = LIHAT SEMUA RUANGAN (TIDAK DIFILTER)
            $labelPegawai = "Semua Pegawai (Koordinator)";
        }

        else {
            $labelPegawai = "Total Seluruh Pegawai";
        }

        $totalPegawai = $pegawaiQuery->count();

        // =========================
        // STATISTIC STATUS
        // =========================
        $totalProses = (clone $queryJasa)->where('status', 'proses_karu')->count();
        $totalVerifikasi = (clone $queryJasa)->where('status', 'verifikasi')->count();
        $totalSelesai = (clone $queryJasa)->where('status', 'selesai')->count();

        // =========================
        // GROUP BY PERIODE
        // =========================
        $dataJasa = $queryJasa->latest()->get()
            ->groupBy(function ($item) {
                return $item->periode->periode ?? 'unknown';
            });

        return view('dashboard.dashboard', [
            'data' => $dataJasa,
            'totalPegawai' => $totalPegawai,
            'labelPegawai' => $labelPegawai,
            'totalRuangan' => $ruanganCount,
            'totalProses' => $totalProses,
            'totalVerifikasi' => $totalVerifikasi,
            'totalSelesai' => $totalSelesai,
            'koordinator' => $user->role?->code === 'karu'
        ]);
    }
}