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
    public function index(Request $request)
    {
        $ruangan = Ruangan::all();

        if ($request->periode) {
            // Jika ada parameter ID di URL, tampilkan data tersebut (untuk lihat riwayat)
            $periode = PeriodeJasa::find($request->periode);
        } else {
            // Ambil periode yang paling terakhir dibuat
            $periode = PeriodeJasa::latest()->first();

            if ($periode) {
                // Cek apakah seluruh ruangan di periode ini sudah berstatus 'selesai'
                $totalRuangan = JasaRuangan::where('periode_id', $periode->id)->count();
                $ruanganSelesai = JasaRuangan::where('periode_id', $periode->id)
                    ->where('status', 'selesai')
                    ->count();

                // Jika ADA ruangan dan SEMUANYA sudah selesai, baru buka form untuk bulan baru
                if ($totalRuangan > 0 && $totalRuangan == $ruanganSelesai) {
                    // Update status master periode jadi selesai (jika belum)
                    if ($periode->status != 'selesai') {
                        $periode->update(['status' => 'selesai']);
                    }
                    // Kosongkan $periode agar form input jasa kembali aktif
                    $periode = null;
                }
            }
        }

        $dataPembagian = collect();

        if ($periode) {
            $dataPembagian = JasaRuangan::where('periode_id', $periode->id)
                ->with('ruangan')
                ->get();
        }

        return view('pages.inputJasa', compact('ruangan', 'periode', 'dataPembagian'));
    }

    public function storeTotal(Request $request)
    {
        $request->validate([
            'periode' => 'required',
            'total_jasa' => 'required'
        ]);

        // Bersihkan titik ribuan
        $total = str_replace('.', '', $request->total_jasa);

        // Cek apakah bulan ini sudah pernah diinput sebelumnya
        $cek = PeriodeJasa::where('periode', $request->periode)->first();

        if ($cek) {
            return redirect()->route('jasa.index', ['periode' => $cek->id])->with('error', 'Periode ini sudah pernah dibuat.');
        }

        DB::beginTransaction();
        try {
            // 1. Cek Master Ruangan dulu sebelum bikin periode
            $ruangans = Ruangan::whereNotNull('persen_default')->where('persen_default', '>', 0)->get();
            $jumlahRuangan = count($ruangans);

            if ($jumlahRuangan == 0) {
                DB::rollBack();
                return back()->with('error', 'Gagal generate! Anda belum mengatur persentase di Master Ruangan (semuanya masih 0%).');
            }

            // 2. Simpan Periode
            $periode = PeriodeJasa::create([
                'periode' => $request->periode,
                'total_jasa' => $total,
                'status' => 'draft'
            ]);

            $totalNominalTerbagi = 0;

            // 3. Auto-Generate Pembagian
            foreach ($ruangans as $index => $r) {
                $persen = (float) $r->persen_default;
                $nominal = round(($persen / 100) * $total);

                // Trick akuntansi: sisa pembulatan dilempar ke ruangan terakhir agar pas 100% dan sisa Rp 0
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
            return redirect()->route('jasa.index', ['periode' => $periode->id])->with('success', 'Berhasil! Pembagian ruangan digenerate otomatis berdasarkan Master.');
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

        // Bulk update status
        JasaRuangan::where('periode_id', $periodeId)->update(['status' => 'proses_karu']);
        PeriodeJasa::where('id', $periodeId)->update(['status' => 'proses_karu']);

        return response()->json(['status' => 'success']);
    }

    // Fitur Reset/Hapus Draft
    public function destroyPeriode($id)
    {
        $periode = PeriodeJasa::findOrFail($id);

        if ($periode->status == 'draft') {
            $periode->delete(); // Pastikan Anda sudah menambahkan fungsi "booted" cascade di model PeriodeJasa
            return redirect()->route('jasa.index')->with('success', 'Draft periode berhasil dibatalkan dan dihapus. Silakan input ulang.');
        }

        return back()->with('error', 'Data tidak bisa dihapus karena sudah diproses oleh KARU.');
    }
}
