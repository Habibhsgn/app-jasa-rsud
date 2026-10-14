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
    /**
     * Tentukan jenis yang tersedia di bulan terpilih dan jenis yang dipakai.
     * return [$periode, $tersedia, $jenis]
     */
    private function resolveFilter(Request $request): array
    {
        $periode  = $request->input('periode');
        $tersedia = collect();
        $jenis    = null;

        if ($periode) {
            $tersedia = JasaRuangan::with('periode')
                ->where('status', 'selesai')
                ->whereHas('periode', fn($q) => $q->where('periode', $periode))
                ->get()
                ->map(fn($jr) => strtoupper($jr->periode?->keterangan ?: 'REGULER'))
                ->unique()
                ->values();

            if ($tersedia->count() === 1) {
                $jenis = $tersedia->first();                       // otomatis
            } elseif ($tersedia->count() > 1) {
                $pilihan = strtoupper((string) $request->input('jenis'));
                $jenis   = $tersedia->contains($pilihan) ? $pilihan : null; // harus dipilih
            }
        }

        return [$periode, $tersedia, $jenis];
    }

    private function buildQuery(string $periode, string $jenis)
    {
        return JasaRuangan::with([
            'periode',
            'ruangan',
            // sembunyikan baris kosong tanpa pegawai
            'jasaPegawai' => fn($q) => $q->where(function ($w) {
                $w->whereNotNull('pegawai_id')->orWhere('nominal', '>', 0);
            }),
            'jasaPegawai.pegawai.ruangan',
        ])
            ->where('status', 'selesai')
            ->whereHas('periode', function ($q) use ($periode, $jenis) {
                $q->where('periode', $periode)->where('keterangan', $jenis);
            })
            ->orderBy('ruangan_id');
    }

    public function index(Request $request)
    {
        [$periodeDipilih, $tersedia, $jenis] = $this->resolveFilter($request);

        $data = ($periodeDipilih && $jenis)
            ? $this->buildQuery($periodeDipilih, $jenis)->get()
            : collect();

        // Dropdown bulan: hanya bulan yang punya data selesai
        $periodeIds = JasaRuangan::where('status', 'selesai')->distinct()->pluck('periode_id');
        $periode = PeriodeJasa::whereIn('id', $periodeIds)
            ->orderBy('periode', 'desc')
            ->pluck('periode')
            ->unique()
            ->values();

        return view('pages.laporanJasa', compact(
            'data',
            'periode',
            'periodeDipilih',
            'tersedia',
            'jenis'
        ));
    }

    public function exportExcel(Request $request)
    {
        [$periodeDipilih, $tersedia, $jenis] = $this->resolveFilter($request);

        if (!$periodeDipilih || !$jenis) {
            return redirect()
                ->route('laporan.jasa.index', $request->only('periode', 'jenis'))
                ->with('error', 'Pilih periode (dan jenis) terlebih dahulu sebelum download.');
        }

        $data = $this->buildQuery($periodeDipilih, $jenis)->get();

        if ($data->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk diexport.');
        }

        $bulan = Carbon::parse($periodeDipilih . '-01')->translatedFormat('F_Y');
        $namaFile = 'Laporan_Jasa_' . ucfirst(strtolower($jenis)) . '_' . $bulan . '.xlsx';

        return Excel::download(new JasaPegawaiExport($data, $jenis), $namaFile);
    }
}
