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
        // Query dasar jasa
        $queryJasa = JasaRuangan::with(['periode', 'ruangan'])
            ->whereIn('status', ['proses_karu', 'verifikasi', 'selesai']);

        $pegawaiQuery = Pegawai::query();
        $ruanganCount = Ruangan::count();

        // Filter berdasarkan role
        if (Auth::user()->role === 'karu') {
            $ruanganId = Auth::user()->ruangan_id;

            $queryJasa->where('ruangan_id', $ruanganId);
            $pegawaiQuery->where('ruangan_id', $ruanganId);

            $labelPegawai = "Pegawai di Ruangan Anda";
        } else {
            $labelPegawai = "Total Seluruh Pegawai";
        }

        $totalPegawai = $pegawaiQuery->count();

        // Statistik berdasarkan status
        $totalProses = (clone $queryJasa)->where('status', 'proses_karu')->count();
        $totalVerifikasi = (clone $queryJasa)->where('status', 'verifikasi')->count();
        $totalSelesai = (clone $queryJasa)->where('status', 'selesai')->count();

        // Data terbaru untuk tabel
        $dataJasa = $queryJasa->latest()->get();

        return view('dashboard.dashboard', [
            'data' => $dataJasa,
            'totalPegawai' => $totalPegawai,
            'labelPegawai' => $labelPegawai,
            'totalRuangan' => $ruanganCount,
            'totalProses' => $totalProses,
            'totalVerifikasi' => $totalVerifikasi,
            'totalSelesai' => $totalSelesai
        ]);
    }
}