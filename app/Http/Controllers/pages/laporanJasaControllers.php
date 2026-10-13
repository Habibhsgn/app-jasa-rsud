<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JasaRuangan;
use App\Models\PeriodeJasa;
use App\Exports\JasaPegawaiExport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class laporanJasaControllers extends Controller
{
    private function buildQuery(Request $request)
    {
        $query = JasaRuangan::with(['periode', 'ruangan', 'jasaPegawai.pegawai.ruangan'])
            ->where('status', 'selesai');

        // Filter bulan, contoh "2026-01"
        if ($request->filled('periode')) {
            $query->whereHas('periode', fn($q) => $q->where('periode', $request->periode));
        }

        // Filter jenis: REGULER / PENDING
        if ($request->filled('jenis')) {
            $query->whereHas('periode', fn($q) => $q->where('keterangan', strtoupper($request->jenis)));
        }

        return $query->orderBy('periode_id')->orderBy('ruangan_id');
    }

    public function index(Request $request)
    {
        $data = $this->buildQuery($request)->get();

        $periode = PeriodeJasa::select('periode')
            ->distinct()
            ->orderBy('periode', 'desc')
            ->pluck('periode');

        return view('pages.laporanJasa', compact('data', 'periode'));
    }

    public function exportExcel(Request $request)
    {
        $data = $this->buildQuery($request)->get();

        if ($data->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk diexport.');
        }

        $jenis = $request->filled('jenis') ? strtoupper($request->jenis) : null;

        $namaPeriode = 'Semua_Periode';
        if ($request->filled('periode')) {
            $namaPeriode = str_replace(
                ' ',
                '_',
                Carbon::parse($request->periode . '-01')->translatedFormat('F_Y')
            );
        }

        $namaFile = 'Laporan_Jasa_' . ($jenis ? ucfirst(strtolower($jenis)) . '_' : 'Semua_Jenis_') . $namaPeriode . '.xlsx';

        return Excel::download(new JasaPegawaiExport($data, $jenis), $namaFile);
    }
}
