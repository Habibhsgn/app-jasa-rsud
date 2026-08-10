<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\IndexScoring;
use App\Models\TopLeader;
use Illuminate\Support\Carbon;

class ManagementIndexScoringControllers extends Controller
{
    /**
     * Daftar PERIODE (bulan) yang perlu direview.
     *
     * - "Menunggu Review": periode yang MASIH punya minimal 1 ruangan/top leader berstatus 'submit'.
     * - "Riwayat": periode yang SEMUA sudah selesai diproses (tidak ada lagi
     *   yang 'submit') dan minimal 1 berstatus 'selesai' atau 'revisi'.
     *
     * Detail siapa-approve-siapa per ruangan/top leader ditangani di halaman show().
     */
    public function index()
    {
        $query = IndexScoring::query();

        if (Auth::user()->role?->code === 'manajemen') {
            $query->join('ruangan', 'index_scorings.ruangan_id', '=', 'ruangan.id')
                ->where('ruangan.bidang_id', Auth::user()->bidang_id);
        }

        $rows = $query->select(
            'index_scorings.periode_pengajuan',
            'index_scorings.ruangan_id',
            'index_scorings.source_type',
            'index_scorings.source_id',
            'index_scorings.status_pengajuan'
        )->get();

        $grouped = $rows->groupBy(fn($r) => Carbon::parse($r->periode_pengajuan)->format('Y-m'));

        $items = $grouped->map(function ($group, $periodeStr) {
            $periode = Carbon::createFromFormat('Y-m', $periodeStr)->startOfMonth();
            $statuses = $group->pluck('status_pengajuan');

            return (object) [
                'periode' => $periodeStr,
                'periode_label' => $periode->translatedFormat('F Y'),
                'ada_menunggu' => $statuses->contains('submit'),
                'ada_riwayat' => $statuses->contains(fn($s) => in_array($s, ['selesai', 'revisi'])),
                'jumlah_ruangan' => $group->where('source_type', 'pegawai')->pluck('ruangan_id')->unique()->count(),
                'jumlah_top_leader' => $group->where('source_type', 'top_leader')->count(),
                'jumlah_pegawai' => $group->where('source_type', 'pegawai')->count(),
            ];
        })->values();

        $menunggu = $items->filter(fn($i) => $i->ada_menunggu)
            ->sortByDesc('periode')
            ->values();

        // Periode hanya masuk "riwayat" kalau sudah tidak ada lagi yang menunggu review.
        $riwayat = $items->filter(fn($i) => $i->ada_riwayat && !$i->ada_menunggu)
            ->sortByDesc('periode')
            ->values();


        return view('pages.management.IndexScoringReview', compact('menunggu', 'riwayat'));
    }

    /**
     * Detail 1 periode: semua ruangan + top leader ditampilkan, masing-masing dengan
     * status, catatan revisi, dan tombol Setujui/Revisi SENDIRI-SENDIRI.
     */
    public function show(string $periode)
    {
        $periodeDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        $scoring = IndexScoring::where('periode_pengajuan', $periodeDate)->get();

        if ($scoring->isEmpty()) {
            abort(404, 'Data pengajuan untuk periode ini tidak ditemukan.');
        }

        // --- Regular Pegawai per Ruangan ---
        $ruanganIds = $scoring->where('source_type', 'pegawai')->pluck('ruangan_id')->unique();

        $ruanganQuery = DB::table('ruangan')
            ->whereIn('id', $ruanganIds);

        if (Auth::user()->role?->code === 'manajemen') {
            $ruanganQuery->where('bidang_id', Auth::user()->bidang_id);
        }

        $ruangans = $ruanganQuery
            ->orderBy('nama_ruangan')
            ->get();

        $pegawaiIds = $scoring->where('source_type', 'pegawai')->pluck('pegawai_id')->unique();

        $pegawaiMaster = DB::table('pegawai')
            ->whereIn('id', $pegawaiIds)
            ->get()
            ->keyBy('id');

        $scoringByRuangan = $scoring->where('source_type', 'pegawai')->groupBy('ruangan_id');

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
                'source_type' => 'pegawai',

                'pegawai' => $rows->map(function ($row) use ($pegawaiMaster) {

                    $master = $pegawaiMaster->get($row->pegawai_id);

                    return (object) [
                        'nama' => $master->nama ?? '-',
                        'id_petugas' => $master->id_petugas ?? '-',
                        'jabatan' => $row->jabatan,
                        'pendidikan_formal' => $row->pendidikan_formal,
                        'pendidikan_non_formal' => $row->pendidikan_non_formal,
                        'gaji_pokok' => number_format((int) $master->gaji_pokok, 0, ',', '.'),
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

        // --- Top Leader (grouped by posisi) ---
        $topLeaderIds = $scoring->where('source_type', 'top_leader')->pluck('source_id')->unique();
        $topLeaders = TopLeader::whereIn('id', $topLeaderIds)->get()->keyBy('id');

        $scoringByTopLeader = $scoring->where('source_type', 'top_leader')->keyBy('source_id');

        $topLeaderData = $topLeaders->groupBy('posisi')->map(function ($leaders, $posisi) use ($scoringByTopLeader) {
            $leaderData = $leaders->map(function ($tl) use ($scoringByTopLeader) {
                $row = $scoringByTopLeader->get($tl->id);
                $statusTL = $row?->status_pengajuan ?? 'draft';
                $catatanRevisi = $row?->catatan_revisi;

                return (object) [
                    'id' => $tl->id,
                    'nama' => $tl->nama,
                    'posisi' => $tl->posisi,
                    'id_petugas' => $tl->posisi,
                    'status_pengajuan' => $statusTL,
                    'catatan_revisi' => $catatanRevisi,
                    'bisa_direview' => $statusTL === 'submit',
                    'source_type' => 'top_leader',
                    'jabatan' => $row?->jabatan ?? 0,
                    'pendidikan_formal' => $row?->pendidikan_formal ?? 0,
                    'pendidikan_non_formal' => $row?->pendidikan_non_formal ?? 0,
                    'gaji_pokok' => $row ? number_format((int) $row->gaji_pokok, 0, ',', '.') : '0',
                    'risk' => $row?->risk ?? 0,
                    'emergency' => $row?->emergency ?? 0,
                    'cuti' => $row?->cuti ?? 0,
                    'izin' => $row?->izin ?? 0,
                    'tanpa_izin' => $row?->tanpa_izin ?? 0,
                    'telat' => $row?->telat ?? 0,
                    'sikap' => $row?->sikap ?? 0,
                    'jumlah' => $row?->jumlah ?? 0,
                    'keterangan' => $row?->keterangan ?? null,
                    'jumlah_akhir' => $row?->jumlah_akhir ?? 0,
                ];
            })->values();

            // Determine overall status for this position group
            $statuses = $leaderData->pluck('status_pengajuan');
            $statusGroup = 'draft';
            if ($statuses->contains('submit')) $statusGroup = 'submit';
            elseif ($statuses->contains('verifikasi')) $statusGroup = 'verifikasi';
            elseif ($statuses->contains('revisi')) $statusGroup = 'revisi';
            elseif ($statuses->contains('selesai')) $statusGroup = 'selesai';

            $bisaDireview = $statusGroup === 'submit';
            $catatanRevisiGroup = $leaderData->firstWhere('catatan_revisi', '!=', null)?->catatan_revisi;

            return (object) [
                'posisi' => $posisi,
                'leaders' => $leaderData,
                'status' => $statusGroup,
                'catatan_revisi' => $catatanRevisiGroup,
                'bisa_direview' => $bisaDireview,
                'source_type' => 'top_leader',
            ];
        })->values();

        $periodeInfo = (object) [
            'periode' => $periode,
            'periode_label' => $periodeDate->translatedFormat('F Y'),
        ];

        return view('pages.management.IndexScoringReviewDetail', [
            'periodeInfo' => $periodeInfo,
            'ruangans' => $ruanganData,
            'top_leaders' => $topLeaderData,
        ]);
    }

    /**
     * Setujui 1 ruangan/top leader pada 1 periode -> status jadi "selesai".
     * Yang lain di periode yang sama TIDAK terpengaruh.
     * Untuk top_leader: approve SEMUA yang punya posisi yang sama.
     */
    public function approve(string $periode, int $id, string $sourceType = 'pegawai')
    {
        $periodeDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        // Authorization check
        if (Auth::user()->role?->code === 'manajemen') {
            if ($sourceType === 'pegawai') {
                $cekRuangan = DB::table('ruangan')
                    ->where('id', $id)
                    ->where('bidang_id', Auth::user()->bidang_id)
                    ->exists();

                if (!$cekRuangan) {
                    abort(403, 'Anda tidak memiliki akses ke ruangan ini.');
                }
            } else {
                // Top leader - verify the position exists
                $posisi = TopLeader::where('id', $id)->value('posisi');
                if (!$posisi) {
                    abort(404, 'Top Leader tidak ditemukan.');
                }
            }
        }

        $query = IndexScoring::where('periode_pengajuan', $periodeDate)
            ->where('source_type', $sourceType)
            ->where('status_pengajuan', 'submit');

        if ($sourceType === 'pegawai') {
            $query->where('ruangan_id', $id);
        } else {
            // For top_leader: approve ALL with same posisi
            $posisi = TopLeader::where('id', $id)->value('posisi');
            $leaderIds = TopLeader::where('posisi', $posisi)->pluck('id');
            $query->whereIn('source_id', $leaderIds);
        }

        $updated = $query->update([
            'status_pengajuan' => 'selesai',
            'catatan_revisi' => null,
            'direvisi_oleh' => null,
            'direvisi_at' => null,
        ]);

        if ($updated === 0) {
            $label = $sourceType === 'pegawai' ? 'Ruangan ini' : 'Top Leader ini';
            return redirect()
                ->route('management.index.scoring.show', $periode)
                ->with('error', "$label tidak dalam status menunggu review, atau sudah diproses pihak lain.");
        }

        return redirect()
            ->route('management.index.scoring.show', $periode)
            ->with('success', 'Pengajuan telah disetujui.');
    }

    /**
     * Kembalikan 1 ruangan/top leader pada 1 periode untuk revisi -> status jadi "revisi".
     * Yang lain di periode yang sama TIDAK terpengaruh.
     * Untuk top_leader: revisi SEMUA yang punya posisi yang sama.
     */
    public function revisi(Request $request, string $periode, int $id, string $sourceType = 'pegawai')
    {
        $request->validate([
            'catatan_revisi' => ['required', 'string', 'min:5'],
        ], [
            'catatan_revisi.required' => 'Catatan revisi wajib diisi.',
            'catatan_revisi.min' => 'Catatan revisi terlalu singkat, jelaskan bagian yang perlu diperbaiki.',
        ]);

        $periodeDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        // Authorization check
        if (Auth::user()->role?->code === 'manajemen') {
            if ($sourceType === 'pegawai') {
                $cekRuangan = DB::table('ruangan')
                    ->where('id', $id)
                    ->where('bidang_id', Auth::user()->bidang_id)
                    ->exists();

                if (!$cekRuangan) {
                    abort(403, 'Anda tidak memiliki akses ke ruangan ini.');
                }
            } else {
                // Top leader - verify the position exists
                $posisi = TopLeader::where('id', $id)->value('posisi');
                if (!$posisi) {
                    abort(404, 'Top Leader tidak ditemukan.');
                }
            }
        }

        $query = IndexScoring::where('periode_pengajuan', $periodeDate)
            ->where('source_type', $sourceType)
            ->where('status_pengajuan', 'submit');

        if ($sourceType === 'pegawai') {
            $query->where('ruangan_id', $id);
        } else {
            // For top_leader: revisi ALL with same posisi
            $posisi = TopLeader::where('id', $id)->value('posisi');
            $leaderIds = TopLeader::where('posisi', $posisi)->pluck('id');
            $query->whereIn('source_id', $leaderIds);
        }

        $updated = $query->update([
            'status_pengajuan' => 'revisi',
            'catatan_revisi' => $request->catatan_revisi,
            'direvisi_oleh' => Auth::id(),
            'direvisi_at' => now(),
        ]);

        if ($updated === 0) {
            $label = $sourceType === 'pegawai' ? 'Ruangan ini' : 'Top Leader ini';
            return redirect()
                ->route('management.index.scoring.show', $periode)
                ->with('error', "$label tidak dalam status menunggu review, atau sudah diproses pihak lain.");
        }

        return redirect()
            ->route('management.index.scoring.show', $periode)
            ->with('success', 'Pengajuan dikembalikan untuk revisi.');
    }
}