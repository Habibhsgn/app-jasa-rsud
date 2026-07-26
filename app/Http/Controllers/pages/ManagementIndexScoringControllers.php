<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\IndexScoring;
use Illuminate\Support\Carbon;

class ManagementIndexScoringControllers extends Controller
{
    /**
     * Daftar PERIODE (bulan) yang perlu direview.
     *
     * - "Menunggu Review": periode yang MASIH punya minimal 1 ruangan berstatus 'submit'.
     * - "Riwayat": periode yang SEMUA ruangannya sudah selesai diproses (tidak ada lagi
     *   yang 'submit') dan minimal 1 ruangan berstatus 'selesai' atau 'revisi'.
     *
     * Detail siapa-approve-siapa per ruangan ditangani di halaman show().
     */
    public function index()
    {
        $rows = IndexScoring::select('periode_pengajuan', 'ruangan_id', 'status_pengajuan')->get();

        $grouped = $rows->groupBy(fn($r) => Carbon::parse($r->periode_pengajuan)->format('Y-m'));

        $items = $grouped->map(function ($group, $periodeStr) {
            $periode = Carbon::createFromFormat('Y-m', $periodeStr)->startOfMonth();
            $statuses = $group->pluck('status_pengajuan');

            return (object) [
                'periode' => $periodeStr,
                'periode_label' => $periode->translatedFormat('F Y'),
                'ada_menunggu' => $statuses->contains('submit'),
                'ada_riwayat' => $statuses->contains(fn($s) => in_array($s, ['selesai', 'revisi'])),
                'jumlah_ruangan' => $group->pluck('ruangan_id')->unique()->count(),
                'jumlah_pegawai' => $group->count(),
            ];
        })->values();

        $menunggu = $items->filter(fn($i) => $i->ada_menunggu)
            ->sortByDesc('periode')
            ->values();

        // Periode hanya masuk "riwayat" kalau sudah tidak ada lagi ruangan yang menunggu review.
        $riwayat = $items->filter(fn($i) => $i->ada_riwayat && !$i->ada_menunggu)
            ->sortByDesc('periode')
            ->values();

        return view('pages.management.IndexScoringReview', compact('menunggu', 'riwayat'));
    }

    /**
     * Detail 1 periode: semua ruangan ditampilkan, masing-masing dengan
     * status, catatan revisi, dan tombol Setujui/Revisi SENDIRI-SENDIRI.
     */
    public function show(string $periode)
    {
        $periodeDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        $scoring = IndexScoring::where('periode_pengajuan', $periodeDate)->get();

        if ($scoring->isEmpty()) {
            abort(404, 'Data pengajuan untuk periode ini tidak ditemukan.');
        }

        $ruanganIds = $scoring->pluck('ruangan_id')->unique();

        $ruangans = DB::table('ruangan')
            ->whereIn('id', $ruanganIds)
            ->orderBy('nama_ruangan')
            ->get();

        $pegawaiIds = $scoring->pluck('pegawai_id')->unique();

        $pegawaiMaster = DB::table('pegawai')
            ->whereIn('id', $pegawaiIds)
            ->get()
            ->keyBy('id');

        $scoringByRuangan = $scoring->groupBy('ruangan_id');

        $ruanganData = $ruangans->map(function ($ruangan) use ($scoringByRuangan, $pegawaiMaster) {

            $rows = $scoringByRuangan->get($ruangan->id, collect());
            $statusRuangan = optional($rows->first())->status_pengajuan;
            $catatanRevisi = optional($rows->first())->catatan_revisi;

            return (object) [
                'id' => $ruangan->id,
                'nama_ruangan' => $ruangan->nama_ruangan,
                'status' => $statusRuangan,
                'catatan_revisi' => $catatanRevisi,
                'bisa_direview' => $statusRuangan === 'submit',

                'pegawai' => $rows->map(function ($row) use ($pegawaiMaster) {

                    $master = $pegawaiMaster->get($row->pegawai_id);

                    return (object) [
                        'nama' => $master->nama ?? '-',
                        'id_petugas' => $master->id_petugas ?? '-',
                        'jabatan' => $row->jabatan,
                        'pendidikan_formal' => $row->pendidikan_formal,
                        'pendidikan_non_formal' => $row->pendidikan_non_formal,
                        'gaji_pokok' => number_format((int) $row->gaji_pokok, 0, ',', '.'),
                        'risk' => $row->risk,
                        'emergency' => $row->emergency,
                        'cuti' => $row->cuti,
                        'izin' => $row->izin,
                        'tanpa_izin' => $row->tanpa_izin,
                        'telat' => $row->telat,
                        'sikap' => $row->sikap,
                        'jumlah' => $row->jumlah,
                        'keterangan' => $row->keterangan,
                        'jumlah_akhir' => $row->jumlah_akhir,
                    ];
                })->values(),
            ];
        });

        $periodeInfo = (object) [
            'periode' => $periode,
            'periode_label' => $periodeDate->translatedFormat('F Y'),
        ];

        return view('pages.management.IndexScoringReviewDetail', [
            'periodeInfo' => $periodeInfo,
            'ruangans' => $ruanganData,
        ]);
    }

    /**
     * Setujui 1 ruangan pada 1 periode -> status jadi "selesai".
     * Ruangan lain di periode yang sama TIDAK terpengaruh.
     */
    public function approve(string $periode, int $ruangan)
    {
        $periodeDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        $updated = IndexScoring::where('periode_pengajuan', $periodeDate)
            ->where('ruangan_id', $ruangan)
            ->where('status_pengajuan', 'submit')
            ->update([
                'status_pengajuan' => 'selesai',
                'catatan_revisi' => null,
                'direvisi_oleh' => null,
                'direvisi_at' => null,
            ]);

        if ($updated === 0) {
            return redirect()
                ->route('management.index.scoring.show', $periode)
                ->with('error', 'Ruangan ini tidak dalam status menunggu review, atau sudah diproses pihak lain.');
        }

        return redirect()
            ->route('management.index.scoring.show', $periode)
            ->with('success', 'Pengajuan ruangan telah disetujui.');
    }

    /**
     * Kembalikan 1 ruangan pada 1 periode untuk revisi -> status jadi "revisi".
     * Ruangan lain di periode yang sama TIDAK terpengaruh.
     */
    public function revisi(Request $request, string $periode, int $ruangan)
    {
        $request->validate([
            'catatan_revisi' => ['required', 'string', 'min:5'],
        ], [
            'catatan_revisi.required' => 'Catatan revisi wajib diisi.',
            'catatan_revisi.min' => 'Catatan revisi terlalu singkat, jelaskan bagian yang perlu diperbaiki.',
        ]);

        $periodeDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        $updated = IndexScoring::where('periode_pengajuan', $periodeDate)
            ->where('ruangan_id', $ruangan)
            ->where('status_pengajuan', 'submit')
            ->update([
                'status_pengajuan' => 'revisi',
                'catatan_revisi' => $request->catatan_revisi,
                'direvisi_oleh' => Auth::id(),
                'direvisi_at' => now(),
            ]);

        if ($updated === 0) {
            return redirect()
                ->route('management.index.scoring.show', $periode)
                ->with('error', 'Ruangan ini tidak dalam status menunggu review, atau sudah diproses pihak lain.');
        }

        return redirect()
            ->route('management.index.scoring.show', $periode)
            ->with('success', 'Pengajuan ruangan dikembalikan untuk revisi.');
    }
}
