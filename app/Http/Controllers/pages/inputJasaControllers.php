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
     * Daftar ruangan yang berhak menerima pembagian jasa.
     * Ruangan di luar daftar ini TIDAK akan ikut dibagikan meskipun
     * persen_default di Master Ruangan terisi > 0 (safety net).
     *
     * PENTING: sesuaikan value di sini dengan isi kolom nama ruangan
     * (asumsi kolom: nama_ruangan) persis sama, termasuk huruf besar/kecil
     * dan tanda kurung, karena whereIn() melakukan exact match.
     */
    private const RUANGAN_PENERIMA_JASA = [
        'POLI PENYAKIT TERPADU',
        'IGD',
        'FIRDAUS',
        'DARUSSALAM',
        'RAUDAH',
        'POLI GIGI',
        'KAMAR OPERASI (OK)',
        'PONEK',
        'POLI PARU',
        'MULTAZAM',
        'AN NISA',
        'AR RAYAN',
        'PERINATOLOGI',
        'MADINAH',
        'VK (KAMAR BERSALIN)',
        'ICU',
        'ARAFAH',
        'LABORATORIUM',
        'INSTALASI TRANSFUSI DARAH (UTDRS)',
        'RADIOLOGI',
        'FARMASI',
        'PENATA ANESTESI',
        'GIZI',
        'KESLING',
        'IPSRS',
        'INFORMASI & RUJUKAN',
        'REKAM MEDIK',
        'PEMULASARAN JENAZAH',
        'CSSD',
    ];

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
            'periode' => 'required',
            'total_jasa' => 'required',
            'keterangan' => 'required|in:REGULER,PENDING' // ✅ TAMBAHAN
        ]);

        $total = str_replace('.', '', $request->total_jasa);

        $cek = PeriodeJasa::where('periode', $request->periode)->first();

        if ($cek) {
            return redirect()
                ->route('jasa.index', ['periode' => $cek->id])
                ->with('error', 'Periode ini sudah pernah dibuat.');
        }

        DB::beginTransaction();

        try {
            $ruangans = Ruangan::whereIn('nama_ruangan', self::RUANGAN_PENERIMA_JASA)
                ->whereNotNull('persen_default')
                ->where('persen_default', '>', 0)
                ->get();

            $jumlahRuangan = count($ruangans);

            if ($jumlahRuangan == 0) {
                DB::rollBack();
                return back()->with(
                    'error',
                    'Gagal generate! Anda belum mengatur persentase di Master Ruangan untuk ruangan yang berhak menerima jasa.'
                );
            }

            // =========================
            // SIMPAN PERIODE (UPDATE)
            // =========================
            $periode = PeriodeJasa::create([
                'periode' => $request->periode,
                'total_jasa' => $total,
                'status' => 'draft',
                'keterangan' => $request->keterangan // ✅ BARU
            ]);

            $totalNominalTerbagi = 0;

            foreach ($ruangans as $index => $r) {

                $persen = (float) $r->persen_default;
                $nominal = round(($persen / 100) * $total);

                if ($index === $jumlahRuangan - 1) {
                    $nominal = $total - $totalNominalTerbagi;
                }

                JasaRuangan::create([
                    'periode_id' => $periode->id,
                    'ruangan_id' => $r->id,
                    'persen'     => $persen,
                    'nominal'    => $nominal,
                    'status'     => 'draft'
                ]);

                $totalNominalTerbagi += $nominal;
            }

            DB::commit();

            return redirect()
                ->route('jasa.index', ['periode' => $periode->id])
                ->with('success', 'Berhasil! Pembagian ruangan berhasil digenerate.');
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
                'status' => 'error',
                'message' => 'Total master pembagian harus tepat 100%. Saat ini: ' . round($totalPersen, 2) . '%'
            ]);
        }

        JasaRuangan::where('periode_id', $periodeId)
            ->update(['status' => 'proses_karu']);

        PeriodeJasa::where('id', $periodeId)
            ->update(['status' => 'proses_karu']);

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
