<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ruangan;
use App\Models\PeriodeJasa;
use App\Models\JasaPegawai;
use App\Models\JasaRuangan;
use Illuminate\Support\Facades\Auth;

class jasaRuanganControllers extends Controller
{

    public function index()
    {
        // 1. Ambil query dasar dengan Eager Loading agar cepat
        $query = JasaRuangan::with(['periode', 'ruangan', 'pegawai'])
            ->whereIn('status', ['proses_karu', 'verifikasi', 'selesai']);

        
        if (Auth::user()->role === 'karu') {
            $query->where('ruangan_id', Auth::user()->ruangan_id);
        }

        $data = $query->get();

        return view('pages.isiJasa', compact('data'));
        return view('dashboard.dashboard', compact('data'));
    }

    public function store(Request $request)
    {
        // Pastikan request array tidak kosong
        if (!$request->has('pegawai_id')) {
            return back()->with('error', 'Tidak ada pegawai untuk disimpan');
        }

        foreach ($request->pegawai_id as $i => $pegawaiId) {
            
            // Bersihkan titik pemisah ribuan dari input nominal
            $nominalBersih = str_replace('.', '', $request->nominal[$i]);
            
            // Ambil data persen dan keterangan
            $persen = $request->persen[$i] ?? 0;
            $keterangan = $request->keterangan[$i] ?? null;

            JasaPegawai::updateOrCreate(
                [
                    'jasa_ruangan_id' => $request->jasa_ruangan_id,
                    'pegawai_id' => $pegawaiId
                ],
                [
                    'persen' => $persen,
                    'nominal' => $nominalBersih,
                    'keterangan' => $keterangan
                ]
            );
        }

        return back()->with('success', 'Draft Jasa Pegawai berhasil disimpan');
    }

    public function submit(Request $request)
    {
        $jasa = JasaRuangan::find($request->jasa_ruangan_id);
        $totalPegawai = JasaPegawai::where('jasa_ruangan_id', $jasa->id)->sum('nominal');

        if ($totalPegawai != $jasa->nominal) {
            return back()->with('error', 'Total pembagian tidak sama dengan alokasi nominal ruangan');
        }

        // Langsung ubah status ke 'selesai'
        $jasa->status = 'selesai';
        $jasa->save();

        return back()->with('success', 'Berhasil disubmit! Data telah permanen dan masuk ke Laporan.');
    }
}
