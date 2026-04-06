<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JasaRuangan;
use App\Exports\JasaPegawaiExport;
use Maatwebsite\Excel\Facades\Excel;

class laporanJasaControllers extends Controller
{
    public function index()
    {
        $data = JasaRuangan::with(['periode', 'ruangan', 'pegawai'])
            ->where('status', 'selesai')
            ->get();

        return view('pages.laporanJasa', compact('data'));
    }

    public function exportExcel()
    {
        $data = JasaRuangan::with(['periode', 'ruangan', 'pegawai'])
            ->where('status', 'selesai')
            ->get();

        return Excel::download(new JasaPegawaiExport($data), 'Laporan_Jasa_Pegawai.xlsx');
    }
}