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
     * Daftar periode yang menunggu review manajemen (status = submit),
     * plus riwayat periode yang sudah diproses (selesai/revisi) untuk referensi.
     */
    public function index()
    {
        $periodeMenunggu = IndexScoring::where('status_pengajuan', 'submit')
            ->distinct()
            ->orderByDesc('periode_pengajuan')
            ->pluck('periode_pengajuan')
            ->map(fn ($p) => Carbon::parse($p)->format('Y-m'));

        $periodeRiwayat = IndexScoring::whereIn('status_pengajuan', ['selesai', 'revisi'])
            ->distinct()
            ->orderByDesc('periode_pengajuan')
            ->pluck('periode_pengajuan')
            ->map(fn ($p) => Carbon::parse($p)->format('Y-m'));

        $summary = function ($periodeList) {
            return $periodeList->map(function ($periodeStr) {
                $periode = Carbon::createFromFormat('Y-m', $periodeStr)->startOfMonth();

                $rows = IndexScoring::where('periode_pengajuan', $periode)->get();

                return (object) [
                    'periode' => $periodeStr,
                    'periode_label' => $periode->translatedFormat('F Y'),
                    'status' => optional($rows->first())->status_pengajuan,
                    'jumlah_ruangan' => $rows->pluck('ruangan_id')->unique()->count(),
                    'jumlah_pegawai' => $rows->count(),
                ];
            });
        };

        $menunggu = $summary($periodeMenunggu);
        $riwayat = $summary($periodeRiwayat);

        return view('pages.management.IndexScoringReview', compact('menunggu', 'riwayat'));
    }

    /**
     * Detail 1 periode untuk direview: semua ruangan & pegawai, read-only.
     */
    public function show(string $periode)
    {
        $periodeDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        $scoring = IndexScoring::where('periode_pengajuan', $periodeDate)->get();

        if ($scoring->isEmpty()) {
            abort(404, 'Data pengajuan untuk periode ini tidak ditemukan.');
        }

        $statusPengajuan = optional($scoring->first())->status_pengajuan;
        $catatanRevisi = optional($scoring->first())->catatan_revisi;

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

        $scoringByPegawai = $scoring->keyBy('pegawai_id');

        $ruanganData = $ruangans->map(function ($ruangan) use ($scoring, $pegawaiMaster, $scoringByPegawai) {

            $pegawaiRuangan = $scoring->where('ruangan_id', $ruangan->id);

            return (object) [
                'id' => $ruangan->id,
                'nama_ruangan' => $ruangan->nama_ruangan,

                'pegawai' => $pegawaiRuangan->map(function ($row) use ($pegawaiMaster) {

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
                        'jumlah_akhir' => $row->jumlah_akhir,
                        'keterangan' => $row->keterangan,
                    ];
                })->values(),
            ];
        });

        $periodeInfo = (object) [
            'periode' => $periode,
            'periode_label' => $periodeDate->translatedFormat('F Y'),
            'status' => $statusPengajuan,
            'catatan_revisi' => $catatanRevisi,
            'bisa_direview' => $statusPengajuan === 'submit',
        ];

        return view('pages.management.IndexScoringReviewDetail', [
            'periodeInfo' => $periodeInfo,
            'ruangans' => $ruanganData,
        ]);
    }

    /**
     * Setujui periode -> status jadi "selesai", terkunci permanen.
     */
    public function approve(string $periode)
    {
        $periodeDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        $updated = IndexScoring::where('periode_pengajuan', $periodeDate)
            ->where('status_pengajuan', 'submit')
            ->update([
                'status_pengajuan' => 'selesai',
                'catatan_revisi' => null,
                'direvisi_oleh' => null,
                'direvisi_at' => null,
            ]);

        if ($updated === 0) {
            return redirect()
                ->route('management.index.scoring.index')
                ->with('error', 'Periode ini tidak dalam status menunggu review, atau sudah diproses pihak lain.');
        }

        return redirect()
            ->route('management.index.scoring.index')
            ->with('success', 'Pengajuan periode ' . $periodeDate->format('F Y') . ' telah disetujui.');
    }

    /**
     * Kembalikan periode untuk revisi -> status jadi "revisi", wajib sertakan catatan.
     */
    public function revisi(Request $request, string $periode)
    {
        $request->validate([
            'catatan_revisi' => ['required', 'string', 'min:5'],
        ], [
            'catatan_revisi.required' => 'Catatan revisi wajib diisi.',
            'catatan_revisi.min' => 'Catatan revisi terlalu singkat, jelaskan bagian yang perlu diperbaiki.',
        ]);

        $periodeDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        $updated = IndexScoring::where('periode_pengajuan', $periodeDate)
            ->where('status_pengajuan', 'submit')
            ->update([
                'status_pengajuan' => 'revisi',
                'catatan_revisi' => $request->catatan_revisi,
                'direvisi_oleh' => Auth::id(),
                'direvisi_at' => now(),
            ]);

        if ($updated === 0) {
            return redirect()
                ->route('management.index.scoring.index')
                ->with('error', 'Periode ini tidak dalam status menunggu review, atau sudah diproses pihak lain.');
        }

        return redirect()
            ->route('management.index.scoring.index')
            ->with('success', 'Pengajuan periode ' . $periodeDate->format('F Y') . ' dikembalikan untuk revisi.');
    }
}