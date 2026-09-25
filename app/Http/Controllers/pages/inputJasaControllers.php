<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ruangan;
use App\Models\PeriodeJasa;
use App\Models\JasaRuangan;
use Illuminate\Support\Facades\DB;

class inputJasaControllers extends Controller
{
    /**
     * Daftar ruangan penerima jasa TIDAK lagi di-hardcode di sini.
     * Sumbernya adalah kolom `penerima_jasa` di Master Ruangan.
     */

    public function index()
    {
        $ruangan = Ruangan::all();

        $periodes = PeriodeJasa::with([
            'pembagianRuangan.ruangan'
        ])
            ->latest()
            ->get();

        return view('pages.inputJasa', compact(
            'ruangan',
            'periodes'
        ));
    }

    public function storeTotal(Request $request)
    {
        $request->validate([
            'periode'    => 'required',
            'total_jasa' => 'required',
            'keterangan' => 'required|in:REGULER,PENDING'
        ]);

        // Buang semua karakter selain angka (titik ribuan, "Rp", spasi, dll)
        $total = (int) preg_replace('/\D/', '', $request->total_jasa);

        if ($total <= 0) {
            return back()->with('error', 'Total jasa tidak valid.');
        }

        $cek = PeriodeJasa::where('periode', $request->periode)->first();

        if ($cek) {
            return redirect()
                ->route('jasa.index', ['periode' => $cek->id])
                ->with('error', 'Periode ini sudah pernah dibuat.');
        }

        // =========================
        // AMBIL RUANGAN PENERIMA JASA DARI MASTER
        // =========================
        $ruangans = Ruangan::where('penerima_jasa', true)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($ruangans->isEmpty()) {
            return back()->with(
                'error',
                'Gagal generate! Belum ada ruangan aktif yang ditandai sebagai penerima jasa 30% di Master Ruangan.'
            );
        }

        // Ruangan ditandai penerima tapi persentasenya belum diisi
        $belumDiisi = $ruangans
            ->filter(fn($r) => (float) $r->persen_default <= 0)
            ->pluck('nama_ruangan');

        if ($belumDiisi->isNotEmpty()) {
            return back()->with(
                'error',
                'Gagal generate! Ruangan berikut ditandai penerima jasa 30% tetapi persentasenya masih 0%: '
                    . $belumDiisi->implode(', ')
            );
        }

        // Total persen harus tepat 100% SEBELUM dibagi
        $totalPersen = round($ruangans->sum(fn($r) => (float) $r->persen_default), 2);

        if ($totalPersen != 100.00) {
            return back()->with(
                'error',
                'Gagal generate! Total persentase ruangan penerima jasa harus tepat 100%. Saat ini: '
                    . number_format($totalPersen, 2, ',', '.') . '%'
            );
        }

        // =========================
        // HITUNG NOMINAL
        // =========================
        $hasil = $ruangans->map(function ($r) use ($total) {
            $persen = (float) $r->persen_default;
            return [
                'ruangan_id' => $r->id,
                'persen'     => $persen,
                'nominal'    => (int) round(($persen / 100) * $total),
            ];
        })->values()->all();

        // Sisa pembulatan (hanya beberapa rupiah) diberikan ke ruangan
        // dengan persentase terbesar, bukan ke baris terakhir.
        $sisa = $total - array_sum(array_column($hasil, 'nominal'));

        if (abs($sisa) > count($hasil)) {
            return back()->with('error', 'Gagal generate! Selisih pembulatan tidak wajar: Rp ' . $sisa);
        }

        if ($sisa !== 0) {
            $idxTerbesar = array_keys(
                array_column($hasil, 'persen'),
                max(array_column($hasil, 'persen'))
            )[0];
            $hasil[$idxTerbesar]['nominal'] += $sisa;
        }

        // =========================
        // SIMPAN
        // =========================
        DB::beginTransaction();

        try {
            $periode = PeriodeJasa::create([
                'periode'    => $request->periode,
                'total_jasa' => $total,
                'status'     => 'draft',
                'keterangan' => $request->keterangan
            ]);

            foreach ($hasil as $row) {
                JasaRuangan::create([
                    'periode_id' => $periode->id,
                    'ruangan_id' => $row['ruangan_id'],
                    'persen'     => $row['persen'],
                    'nominal'    => $row['nominal'],
                    'status'     => 'draft'
                ]);
            }

            DB::commit();

            return redirect()
                ->route('jasa.index', ['periode' => $periode->id])
                ->with('success', 'Berhasil! Pembagian ' . count($hasil) . ' ruangan berhasil digenerate.');
        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with('error', 'Gagal generate otomatis: ' . $e->getMessage());
        }
    }

    public function selesaiPembagian(Request $request, $periodeId)
    {
        $totalPersen = (float) JasaRuangan::where('periode_id', $periodeId)->sum('persen');

        if (round($totalPersen, 2) != 100.00) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Total master pembagian harus tepat 100%. Saat ini: ' . round($totalPersen, 2) . '%'
            ]);
        }

        $periode      = PeriodeJasa::findOrFail($periodeId);
        $totalNominal = (int) JasaRuangan::where('periode_id', $periodeId)->sum('nominal');

        if ($totalNominal !== (int) $periode->total_jasa) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Total nominal (Rp ' . number_format($totalNominal, 0, ',', '.')
                    . ') tidak sama dengan total jasa (Rp ' . number_format($periode->total_jasa, 0, ',', '.') . ').'
            ]);
        }

        JasaRuangan::where('periode_id', $periodeId)
            ->update(['status' => 'proses_karu']);

        $periode->update(['status' => 'proses_karu']);

        return response()->json(['status' => 'success']);
    }

    public function destroyPeriode($id)
    {
        $periode = PeriodeJasa::findOrFail($id);

        if ($periode->status == 'draft') {
            $periode->delete();

            return redirect()
                ->route('jasa.index')
                ->with('success', 'Draft periode berhasil dihapus.');
        }

        return back()->with('error', 'Data tidak bisa dihapus karena sudah diproses.');
    }
}
