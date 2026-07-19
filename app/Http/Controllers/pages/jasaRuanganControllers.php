<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ruangan;
use App\Models\Pegawai;
use App\Models\PeriodeJasa;
use App\Models\JasaPegawai;
use App\Models\JasaRuangan;
use Illuminate\Support\Facades\Auth;

class jasaRuanganControllers extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = JasaRuangan::with(['periode', 'ruangan', 'jasaPegawai.pegawai'])
            ->whereIn('status', [
                'proses_karu',
                'verifikasi',
                'selesai',
                'revisi'
            ]);

        switch ($user->role) {
            case 'karu':
            case 'koordinator_karu':
                $query->where('ruangan_id', $user->ruangan_id);
                break;

            case 'admin':
            default:
                break;
        }

        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));

        $periodeString = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

        $periodeIds = PeriodeJasa::where('periode', $periodeString)
            ->pluck('id');

        $query->whereIn('periode_id', $periodeIds);

        $data = $query->latest()->get();

        // =========================
        // SYNC PEGAWAI BARU KE DRAFT
        // =========================
        // Hanya untuk status yang MASIH BISA DIEDIT (belum dikunci/verifikasi).
        // Kalau statusnya verifikasi/selesai, data histori TIDAK BOLEH diubah otomatis.
        foreach ($data as $item) {
            if (in_array($item->status, ['verifikasi', 'selesai'])) {
                continue;
            }

            $currentPegawaiIds = Pegawai::where('ruangan_id', $item->ruangan_id)
                ->pluck('id');

            $existingPegawaiIds = $item->jasaPegawai->pluck('pegawai_id');

            $pegawaiBaru = $currentPegawaiIds->diff($existingPegawaiIds);

            foreach ($pegawaiBaru as $pegawaiId) {
                JasaPegawai::firstOrCreate(
                    [
                        'jasa_ruangan_id' => $item->id,
                        'pegawai_id' => $pegawaiId,
                    ],
                    [
                        'persen' => 0,
                        'nominal' => 0,
                        'keterangan' => '-',
                    ]
                );
            }
        }

        // Reload relasi jasaPegawai setelah sinkronisasi supaya data terbaru
        if ($data->isNotEmpty()) {
            $data->load('jasaPegawai.pegawai');
        }

        return view('pages.isiJasa', compact(
            'data',
            'bulan',
            'tahun'
        ));
    }

    public function store(Request $request)
    {
        if (!$request->has('pegawai_id')) {
            return back()->with('error', 'Tidak ada pegawai untuk disimpan');
        }

        foreach ($request->pegawai_id as $i => $pegawaiId) {

            $nominalBersih = str_replace('.', '', $request->nominal[$i] ?? 0);
            $persen = $request->persen[$i] ?? 0;

            $keterangan = $request->keterangan[$i] ?? '-';

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

        return back()->with('success', 'Draft berhasil disimpan');
    }

    public function submit(Request $request)
    {
        $jasa = JasaRuangan::with(['jasaPegawai', 'periode'])->find($request->jasa_ruangan_id);

        if (!$jasa) {
            return back()->with('error', 'Data tidak ditemukan');
        }

        $pegawaiCount = JasaPegawai::where('jasa_ruangan_id', $jasa->id)->count();

        if ($pegawaiCount == 0) {
            return back()->with('error', 'Belum ada draft. Simpan dulu sebelum submit.');
        }

        $totalPegawai = JasaPegawai::where('jasa_ruangan_id', $jasa->id)
            ->sum('nominal');

        $selisih = abs($totalPegawai - $jasa->nominal);

        if ($selisih > 1) {
            return back()->with('error', 'Total belum sesuai, selisih Rp ' . $selisih);
        }

        if ($jasa->periode && $jasa->periode->keterangan) {
            $jasa->keterangan = $jasa->periode->keterangan;
        }

        $jasa->status = 'selesai';
        $jasa->save();

        return back()->with('success', 'Berhasil disubmit dan dikunci!');
    }

    public function hapusDariDraft($jasaPegawaiId)
    {
        $jp = JasaPegawai::with('jasaRuangan')->findOrFail($jasaPegawaiId);

        abort_unless(Auth::user()->role === 'admin', 403, 'Hanya admin yang boleh menghapus baris ini.');

        if (in_array($jp->jasaRuangan->status, ['verifikasi', 'selesai'])) {
            return back()->with('error', 'Data sudah terkunci, tidak bisa dihapus.');
        }

        $jp->delete();

        return back()->with('success', 'Baris pegawai berhasil dihapus dari draft.');
    }
}