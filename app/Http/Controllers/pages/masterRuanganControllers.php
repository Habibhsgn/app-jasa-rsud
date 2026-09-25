<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ruangan;
use App\Models\MasterBidang;

class masterRuanganControllers extends Controller
{
    public function index()
    {
        $ruangan = Ruangan::with('bidang')
            ->orderByDesc('penerima_jasa')
            ->orderByDesc('is_active')
            ->orderBy('nama_ruangan')
            ->get();

        $bidang = MasterBidang::where('is_active', true)
            ->orderBy('nama_bidang')
            ->get();

        $totalPersen = $this->hitungTotalPenerima();

        return view('pages.masterRuangan', compact(
            'ruangan',
            'bidang',
            'totalPersen'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_ruangan' => 'required|string|max:100',
            'bidang_id'    => 'required|exists:master_bidang,id',
            'resiko'       => 'required|numeric|in:1,2,4,6',
            'emergency'    => 'required|numeric|in:1,2,4,6',
        ]);

        $ruangan = new Ruangan();
        $ruangan->nama_ruangan   = strtoupper($request->nama_ruangan);
        $ruangan->bidang_id      = $request->bidang_id;
        $ruangan->persen_default = 0;
        $ruangan->penerima_jasa  = false; // dijadikan penerima lewat toggle
        $ruangan->resiko         = $request->resiko;
        $ruangan->emergency      = $request->emergency;
        $ruangan->is_active      = true;
        $ruangan->save();

        return back()->with(
            'success',
            'Ruangan berhasil ditambahkan.'
        );
    }

    public function updateBulk(Request $request)
    {
        $request->validate([
            'ruangan_id'        => 'required|array',
            'nama_ruangan.*'    => 'required|string|max:100',
            'persen_default.*'  => 'required|numeric|min:0|max:100',
            'resiko.*'          => 'required|numeric|in:1,2,4,6',
            'emergency.*'       => 'required|numeric|in:1,2,4,6',
            'bidang_id.*'       => 'required|exists:master_bidang,id',
        ]);

        $master = Ruangan::whereIn('id', $request->ruangan_id)->get()->keyBy('id');

        // Total hanya dari ruangan penerima jasa yang aktif
        $totalPersen = 0;

        foreach ($request->ruangan_id as $index => $id) {
            $r = $master->get($id);

            if ($r && $r->penerima_jasa && $r->is_active) {
                $totalPersen += (float) $request->persen_default[$index];
            }
        }

        if (round($totalPersen, 2) != 100) {
            return back()->with(
                'error',
                'Total persentase ruangan penerima jasa harus tepat 100%. Saat ini: '
                    . round($totalPersen, 2) . '%'
            );
        }

        foreach ($request->ruangan_id as $index => $id) {

            $ruangan = $master->get($id) ?? Ruangan::findOrFail($id);

            $ruangan->nama_ruangan = strtoupper($request->nama_ruangan[$index]);
            $ruangan->bidang_id    = $request->bidang_id[$index];
            $ruangan->resiko       = $request->resiko[$index];
            $ruangan->emergency    = $request->emergency[$index];

            // Persentase hanya diubah untuk ruangan penerima jasa.
            // Ruangan non-penerima tetap menyimpan nilai lamanya.
            if ($ruangan->penerima_jasa) {
                $ruangan->persen_default = $request->persen_default[$index];
            }

            $ruangan->save();
        }

        return back()->with(
            'success',
            'Data ruangan berhasil diperbarui.'
        );
    }

    public function toggleStatus($id)
    {
        $ruangan = Ruangan::findOrFail($id);

        $ruangan->is_active = !$ruangan->is_active;
        $ruangan->save();

        return back()->with(
            'success',
            'Status ruangan berhasil diperbarui.'
        );
    }

    /**
     * Jadikan ruangan penerima jasa 30% sekaligus set alokasi persennya.
     */
    public function aktifkanPenerima(Request $request, $id)
    {
        $request->validate([
            'persen_default' => 'required|numeric|min:0.01|max:100',
        ], [
            'persen_default.required' => 'Alokasi persentase wajib diisi.',
            'persen_default.min'      => 'Alokasi persentase harus lebih dari 0%.',
            'persen_default.max'      => 'Alokasi persentase maksimal 100%.',
        ]);

        $ruangan = Ruangan::findOrFail($id);

        if (!$ruangan->is_active) {
            return back()->with('error', "{$ruangan->nama_ruangan} sedang nonaktif. Aktifkan ruangan terlebih dahulu.");
        }

        if ($ruangan->penerima_jasa) {
            return back()->with('error', "{$ruangan->nama_ruangan} sudah menjadi penerima jasa 30%.");
        }

        // ✅ PENCEGAHAN: total tidak boleh melebihi 100%
        $totalSaatIni = round($this->hitungTotalPenerima(), 2);
        $sisa         = round(100 - $totalSaatIni, 2);
        $persenBaru   = round((float) $request->persen_default, 2);

        if ($sisa <= 0) {
            return back()->with(
                'error',
                "Gagal menambahkan {$ruangan->nama_ruangan}. Total persentase penerima jasa sudah "
                    . $this->fmt($totalSaatIni) . '%. Kurangi alokasi ruangan lain atau keluarkan ruangan lain '
                    . 'dari penerima 30% terlebih dahulu.'
            );
        }

        if ($persenBaru > $sisa) {
            return back()->with(
                'error',
                "Gagal menambahkan {$ruangan->nama_ruangan}. Alokasi " . $this->fmt($persenBaru)
                    . '% melebihi sisa yang tersedia (' . $this->fmt($sisa) . '%).'
            );
        }

        $ruangan->penerima_jasa  = true;
        $ruangan->persen_default = $persenBaru;
        $ruangan->save();

        $total = $this->hitungTotalPenerima();

        return back()->with(
            round($total, 2) == 100 ? 'success' : 'error',
            "{$ruangan->nama_ruangan} sekarang menerima jasa 30% dengan alokasi "
                . $this->fmt($ruangan->persen_default) . '%. '
                . $this->pesanTotal($total)
        );
    }

    /**
     * Keluarkan ruangan dari penerima jasa 30%.
     * Persentase TIDAK direset, supaya bisa dipakai lagi saat diaktifkan kembali.
     */
    public function nonaktifkanPenerima($id)
    {
        $ruangan = Ruangan::findOrFail($id);

        $ruangan->penerima_jasa = false;
        $ruangan->save();

        $total = $this->hitungTotalPenerima();

        return back()->with(
            round($total, 2) == 100 ? 'success' : 'error',
            "{$ruangan->nama_ruangan} tidak lagi menerima jasa 30%. "
                . $this->pesanTotal($total)
        );
    }

    // =========================
    // HELPER
    // =========================

    private function hitungTotalPenerima(): float
    {
        return (float) Ruangan::where('penerima_jasa', true)
            ->where('is_active', true)
            ->sum('persen_default');
    }

    private function fmt($angka): string
    {
        return number_format((float) $angka, 2, ',', '.');
    }

    private function pesanTotal(float $total): string
    {
        if (round($total, 2) == 100) {
            return 'Total persentase sekarang 100%.';
        }

        return 'Total persentase sekarang ' . $this->fmt($total)
            . '%, sesuaikan alokasi ruangan lain agar tepat 100%.';
    }
}
