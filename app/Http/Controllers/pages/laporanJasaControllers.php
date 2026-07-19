<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JasaRuangan;
use App\Models\PeriodeJasa;
use App\Exports\JasaPegawaiExport;
use Maatwebsite\Excel\Facades\Excel;

class laporanJasaControllers extends Controller
{
    public function index(Request $request)
    {
        $periodeId = $request->periode_id;

        $query = JasaRuangan::with(['periode', 'ruangan', 'jasaPegawai.pegawai'])
            ->where('status', 'selesai');

        if ($periodeId) {
            $query->where('periode_id', $periodeId);
        }

        $data = $query->get();

        $periode = PeriodeJasa::orderBy('periode', 'desc')->get();

        return view('pages.laporanJasa', compact(
            'data',
            'periode',
            'periodeId'
        ));
    }

    public function exportExcel(Request $request)
    {
        $periodeId = $request->periode_id;

        $query = JasaRuangan::with(['periode', 'ruangan', 'jasaPegawai.pegawai'])
            ->where('status', 'selesai');

        if ($periodeId) {
            $query->where('periode_id', $periodeId);
        }

        $data = $query->get();

        $namaPeriode = 'Semua_Periode';

        if ($periodeId) {
            $p = PeriodeJasa::find($periodeId);

            if ($p) {
                $namaPeriode = str_replace(' ', '_', $p->periode);
            }
        }

        return Excel::download(
            new JasaPegawaiExport($data),
            'Laporan_Jasa_' . $namaPeriode . '.xlsx'
        );
    }
}
