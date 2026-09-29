<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ruangan;
use App\Models\PeriodeJasa;
use App\Models\JasaRuangan;
use App\Models\JasaPegawai;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class inputJasaControllers extends Controller
{
    /**
     * REGULER : ruangan diambil dari Master Ruangan (penerima_jasa = true, is_active = true).
     * PENDING : ruangan & pegawai disalin dari periode REGULER di bulan yang sama
     *           (snapshot jasa_ruangan & jasa_pegawai), bukan dari master saat ini.
     */

    public function index()
    {
        $ruangan = Ruangan::all();

        $periodes = PeriodeJasa::with(['pembagianRuangan.ruangan', 'referensi'])
            ->latest()
            ->get();

        // Untuk info otomatis di form: ["2026-01" => "selesai", ...]
        $periodeReguler = PeriodeJasa::where('keterangan', 'REGULER')
            ->pluck('status', 'periode');

        return view('pages.inputJasa', compact('ruangan', 'periodes', 'periodeReguler'));
    }

    public function storeTotal(Request $request)
    {
        $request->validate([
            'periode'    => 'required',
            'total_jasa' => 'required',
            'keterangan' => 'required|in:REGULER,PENDING',
        ]);

        // Buang semua karakter selain angka (titik ribuan, "Rp", spasi, dll)
        $total = (int) preg_replace('/\D/', '', $request->total_jasa);

        if ($total <= 0) {
            return back()->withInput()->with('error', 'Total jasa tidak valid.');
        }

        return $request->keterangan === 'PENDING'
            ? $this->generatePending($request, $total)
            : $this->generateReguler($request, $total);
    }

    // =====================================================
    // REGULER: pakai Master Ruangan TERBARU
    // =====================================================
    private function generateReguler(Request $request, int $total)
    {
        $bulan = $this->labelBulan($request->periode);

        $cek = PeriodeJasa::where('periode', $request->periode)
            ->where('keterangan', 'REGULER')
            ->first();

        if ($cek) {
            return redirect()
                ->route('jasa.index', ['periode' => $cek->id])
                ->with('error', 'Jasa REGULER ' . $bulan . ' sudah pernah dibuat.');
        }

        $ruangans = Ruangan::where('penerima_jasa', true)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($ruangans->isEmpty()) {
            return back()->withInput()->with(
                'error',
                'Gagal generate! Belum ada ruangan aktif yang ditandai sebagai penerima jasa 30% di Master Ruangan.'
            );
        }

        $belumDiisi = $ruangans
            ->filter(fn($r) => (float) $r->persen_default <= 0)
            ->pluck('nama_ruangan');

        if ($belumDiisi->isNotEmpty()) {
            return back()->withInput()->with(
                'error',
                'Gagal generate! Ruangan berikut ditandai penerima jasa 30% tetapi persentasenya masih 0%: '
                    . $belumDiisi->implode(', ')
            );
        }

        $totalPersen = round($ruangans->sum(fn($r) => (float) $r->persen_default), 2);

        if ($totalPersen != 100.00) {
            return back()->withInput()->with(
                'error',
                'Gagal generate! Total persentase ruangan penerima jasa harus tepat 100%. Saat ini: '
                    . number_format($totalPersen, 2, ',', '.') . '%'
            );
        }

        $bobot   = $ruangans->mapWithKeys(fn($r) => [$r->id => (float) $r->persen_default])->all();
        $nominal = $this->bagiProporsional($bobot, $total);

        DB::beginTransaction();

        try {
            $periode = PeriodeJasa::create([
                'periode'    => $request->periode,
                'total_jasa' => $total,
                'status'     => 'draft',
                'keterangan' => 'REGULER',
            ]);

            foreach ($ruangans as $r) {
                JasaRuangan::create([
                    'periode_id' => $periode->id,
                    'ruangan_id' => $r->id,
                    'persen'     => $r->persen_default,
                    'nominal'    => $nominal[$r->id],
                    'status'     => 'draft',
                ]);
            }

            DB::commit();

            return redirect()
                ->route('jasa.index', ['periode' => $periode->id])
                ->with('success', 'Berhasil! Jasa REGULER ' . $bulan . ' untuk ' . $ruangans->count() . ' ruangan berhasil digenerate.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal generate otomatis: ' . $e->getMessage());
        }
    }

    // =====================================================
    // PENDING: salin snapshot dari REGULER di bulan yang sama
    // =====================================================
    private function generatePending(Request $request, int $total)
    {
        $bulan = $this->labelBulan($request->periode);

        // Referensi otomatis: REGULER di bulan yang sama
        $referensi = PeriodeJasa::where('periode', $request->periode)
            ->where('keterangan', 'REGULER')
            ->first();

        if (!$referensi) {
            return back()->withInput()->with(
                'error',
                'Belum ada jasa REGULER untuk ' . $bulan . '. Pending hanya bisa dibuat dari periode reguler yang sudah ada.'
            );
        }

        if ($referensi->status === 'draft') {
            return back()->withInput()->with(
                'error',
                'Jasa REGULER ' . $bulan . ' masih draft. Kirim ke KARU dan selesaikan dulu sebelum membuat pending.'
            );
        }

        $sudahAda = PeriodeJasa::where('periode', $request->periode)
            ->where('keterangan', 'PENDING')
            ->first();

        if ($sudahAda) {
            return redirect()
                ->route('jasa.index', ['periode' => $sudahAda->id])
                ->with('error', 'Jasa PENDING ' . $bulan . ' sudah pernah dibuat.');
        }

        // Snapshot ruangan periode reguler (TIDAK difilter is_active / penerima_jasa master)
        $ruanganRef = JasaRuangan::with('ruangan')
            ->where('periode_id', $referensi->id)
            ->orderBy('id')
            ->get();

        if ($ruanganRef->isEmpty()) {
            return back()->withInput()->with('error', 'Jasa REGULER ' . $bulan . ' tidak memiliki data pembagian ruangan.');
        }

        $totalPersen = round($ruanganRef->sum(fn($r) => (float) $r->persen), 2);

        if ($totalPersen != 100.00) {
            return back()->withInput()->with(
                'error',
                'Total persen ruangan pada jasa REGULER ' . $bulan . ' bukan 100% (' . $totalPersen . '%).'
            );
        }

        // Snapshot pegawai periode reguler (TIDAK melihat ruangan/status pegawai saat ini)
        $pegawaiRef = JasaPegawai::whereIn('jasa_ruangan_id', $ruanganRef->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('jasa_ruangan_id');

        $tanpaPegawai = $ruanganRef
            ->filter(fn($jr) => $pegawaiRef->get($jr->id, collect())->sum(fn($p) => (float) $p->persen) <= 0)
            ->map(fn($jr) => $jr->ruangan->nama_ruangan ?? 'ID ' . $jr->ruangan_id);

        if ($tanpaPegawai->isNotEmpty()) {
            return back()->withInput()->with(
                'error',
                'Gagal generate pending! Ruangan berikut belum punya pembagian pegawai di jasa REGULER ' . $bulan . ': '
                    . $tanpaPegawai->implode(', ')
            );
        }

        $bobotRuangan   = $ruanganRef->mapWithKeys(fn($jr) => [$jr->id => (float) $jr->persen])->all();
        $nominalRuangan = $this->bagiProporsional($bobotRuangan, $total);

        DB::beginTransaction();

        try {
            $periode = PeriodeJasa::create([
                'periode'              => $request->periode,
                'total_jasa'           => $total,
                'status'               => 'draft',
                'keterangan'           => 'PENDING',
                'periode_referensi_id' => $referensi->id,
            ]);

            foreach ($ruanganRef as $jrLama) {
                $nomRuangan = $nominalRuangan[$jrLama->id];

                $jrBaru = JasaRuangan::create([
                    'periode_id' => $periode->id,
                    'ruangan_id' => $jrLama->ruangan_id,
                    'persen'     => $jrLama->persen,
                    'nominal'    => $nomRuangan,
                    'status'     => 'draft',
                    'keterangan' => 'Pending dari jasa reguler ' . $bulan,
                ]);

                $listPegawai    = $pegawaiRef->get($jrLama->id);
                $bobotPegawai   = $listPegawai->mapWithKeys(fn($p) => [$p->id => (float) $p->persen])->all();
                $nominalPegawai = $this->bagiProporsional($bobotPegawai, $nomRuangan);

                foreach ($listPegawai as $pLama) {
                    JasaPegawai::create([
                        'jasa_ruangan_id' => $jrBaru->id,
                        'pegawai_id'      => $pLama->pegawai_id,
                        'persen'          => $pLama->persen,
                        'nominal'         => $nominalPegawai[$pLama->id],
                        'keterangan'      => $pLama->keterangan,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('jasa.index', ['periode' => $periode->id])
                ->with('success', 'Berhasil! Jasa PENDING ' . $bulan . ' digenerate untuk ' . $ruanganRef->count() . ' ruangan beserta pegawainya.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal generate pending: ' . $e->getMessage());
        }
    }

    // =====================================================
    // HELPER
    // =====================================================

    /**
     * Bagi $total secara proporsional sesuai bobot.
     * Sisa pembulatan diberikan ke bobot terbesar. Key hasil = key $bobot.
     */
    private function bagiProporsional(array $bobot, int $total): array
    {
        $sumBobot = array_sum($bobot);
        $hasil    = [];

        foreach ($bobot as $key => $b) {
            $hasil[$key] = $sumBobot > 0 ? (int) round(($b / $sumBobot) * $total) : 0;
        }

        $sisa = $total - array_sum($hasil);

        if ($sisa !== 0 && $sumBobot > 0) {
            arsort($bobot);
            $hasil[array_key_first($bobot)] += $sisa;
        }

        return $hasil;
    }

    /** "2026-01" -> "Januari 2026" */
    private function labelBulan(string $periode): string
    {
        // Tambah "-01" agar tidak overflow ke bulan berikutnya di tanggal 29-31
        return Carbon::parse($periode . '-01')->translatedFormat('F Y');
    }

    // =====================================================
    // KUNCI & KIRIM KE KARU
    // =====================================================
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

        DB::transaction(function () use ($periodeId, $periode) {
            JasaRuangan::where('periode_id', $periodeId)->update(['status' => 'proses_karu']);
            $periode->update(['status' => 'proses_karu']);
        });

        return response()->json(['status' => 'success']);
    }

    // =====================================================
    // HAPUS DRAFT
    // =====================================================
    public function destroyPeriode($id)
    {
        $periode = PeriodeJasa::findOrFail($id);

        if ($periode->status !== 'draft') {
            return back()->with('error', 'Data tidak bisa dihapus karena sudah diproses.');
        }

        DB::transaction(function () use ($periode) {
            // Tidak ada FK cascade, jadi hapus anak-anaknya secara eksplisit
            $jrIds = JasaRuangan::where('periode_id', $periode->id)->pluck('id');
            JasaPegawai::whereIn('jasa_ruangan_id', $jrIds)->delete();
            JasaRuangan::where('periode_id', $periode->id)->delete();
            $periode->delete();
        });

        return redirect()
            ->route('jasa.index')
            ->with('success', 'Draft periode berhasil dihapus.');
    }
}
