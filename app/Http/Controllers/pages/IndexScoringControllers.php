<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\IndexScoring;
use App\Models\TopLeader;
use Illuminate\Support\Carbon;

class IndexScoringControllers extends Controller
{
    /**
     * Status yang mengunci form (tidak bisa diedit / disimpan ulang).
     */
    private array $lockedStatuses = ['submit', 'verifikasi', 'selesai'];

    /**
     * Nilai target khusus di dropdown untuk section Top Leader (hanya admin).
     */
    private const TARGET_TOP_LEADER = 'top-leader';

    // =====================================================================
    // HELPER UMUM
    // =====================================================================

    private function isAdmin($user): bool
    {
        return $user->role?->code === 'admin';
    }

    /**
     * Parse periode "Y-m" menjadi tanggal 1 bulan tersebut jam 00:00.
     * Tanda "!" wajib: tanpa itu Carbon memakai TANGGAL HARI INI, sehingga
     * mis. periode "2026-06" yang dibuat tanggal 31 overflow menjadi 1 Juli.
     */
    private function parsePeriode(string $periode): Carbon
    {
        return Carbon::createFromFormat('!Y-m', $periode)->startOfMonth();
    }

    /**
     * Ruangan yang boleh dipilih user ini di dropdown.
     * Admin: semua ruangan aktif. Non-admin: hanya ruangan miliknya.
     */
    private function ruanganOptions($user)
    {
        return DB::table('ruangan')
            ->where('is_active', 1)
            ->when(!$this->isAdmin($user), fn($q) => $q->where('id', $user->ruangan_id))
            ->orderBy('nama_ruangan')
            ->get(['id', 'nama_ruangan']);
    }

    /**
     * Pastikan user berhak atas target (id ruangan atau 'top-leader').
     * Dropdown bisa dimanipulasi lewat inspect element/URL, jadi WAJIB dicek di server.
     */
    private function authorizeTarget($user, string $target): void
    {
        if ($this->isAdmin($user)) {
            return;
        }

        abort_unless(
            $target !== self::TARGET_TOP_LEADER && (int) $target === (int) $user->ruangan_id,
            403,
            'Anda tidak berhak mengakses ruangan ini.'
        );
    }

    /**
     * Tentukan ruangan_id yang akan disimpan untuk satu baris kiriman form.
     * Non-admin: selalu ruangannya sendiri. Top leader: null.
     */
    private function resolveRuanganId(Request $request, array $pegawai, string $sourceType): ?int
    {
        if ($sourceType === 'top_leader') {
            return null;
        }

        if (!$this->isAdmin(Auth::user())) {
            return (int) Auth::user()->ruangan_id;
        }

        $id = $pegawai['ruangan_id'] ?? $request->jasa_ruangan_id;

        return $id !== null && $id !== '' ? (int) $id : null;
    }

    // =====================================================================
    // INDEX: daftar pengajuan yang sudah tersimpan
    // =====================================================================

    public function index()
    {
        $user = Auth::user();

        // ---- KARU: render form semua periode tersimpan, khusus ruangannya ----
        if (!$this->isAdmin($user)) {
            $periodeList = IndexScoring::where('status_pengajuan', '!=', 'selesai')
                ->where('ruangan_id', $user->ruangan_id)
                ->distinct()
                ->orderBy('periode_pengajuan')
                ->pluck('periode_pengajuan');

            $data = $periodeList->map(
                fn($p) => $this->buildPeriodeData(Carbon::parse($p)->startOfMonth(), [(int) $user->ruangan_id], false)
            );

            $ruanganOptions = $this->ruanganOptions($user);

            return view('pages.IndexScoring', compact('data', 'ruanganOptions'));
        }

        // ---- ADMIN: 1 expand = 1 PERIODE, isinya semua ruangan yang sudah ada pengajuannya ----
        $list = IndexScoring::query()
            ->where('status_pengajuan', '!=', 'selesai')
            ->select('periode_pengajuan', DB::raw('MAX(updated_at) as terakhir_update'))
            ->groupBy('periode_pengajuan')
            ->orderByDesc('periode_pengajuan')
            ->paginate(5);

        $periodeRaw = $list->getCollection()
            ->map(fn($r) => $r->getRawOriginal('periode_pengajuan'))
            ->values();

        $saved = IndexScoring::query()
            ->where('status_pengajuan', '!=', 'selesai')
            ->whereIn('periode_pengajuan', $periodeRaw)
            ->select('periode_pengajuan', 'ruangan_id', 'source_type')
            ->distinct()
            ->get()
            ->groupBy(fn($r) => $r->getRawOriginal('periode_pengajuan'));

        $data = $periodeRaw->map(function ($raw) use ($saved) {
            $rows = $saved->get($raw, collect());

            $ruanganIds = $rows->where('source_type', 'pegawai')
                ->pluck('ruangan_id')->filter()->unique()->map(fn($id) => (int) $id)->values()->all();

            $hasTopLeader = $rows->contains('source_type', 'top_leader');

            return $this->buildPeriodeData(Carbon::parse($raw)->startOfMonth(), $ruanganIds, $hasTopLeader);
        })->values();

        $ruanganOptions = $this->ruanganOptions($user);

        return view('pages.IndexScoring', compact('data', 'list', 'ruanganOptions'));
    }

    // =====================================================================
    // CREATE: render 1 form (1 periode x 1 ruangan / top leader)
    // =====================================================================

    /**
     * @return string|null pesan error jika sudah diajukan, null jika boleh lanjut
     */
    private function alreadySubmittedMessage(Carbon $periode, string $target): ?string
    {
        $label = $periode->translatedFormat('F Y');

        if ($target === self::TARGET_TOP_LEADER) {
            $activeIds = TopLeader::where('is_active', true)->pluck('id');

            if ($activeIds->isEmpty()) {
                return null;
            }

            $lockedCount = IndexScoring::where('periode_pengajuan', $periode)
                ->where('source_type', 'top_leader')
                ->whereIn('source_id', $activeIds)
                ->whereIn('status_pengajuan', $this->lockedStatuses)
                ->distinct()
                ->count('source_id');

            return $lockedCount >= $activeIds->count()
                ? "Pengajuan Top Leader periode $label sudah diajukan dan tidak dapat dibuat ulang."
                : null;
        }

        $existing = IndexScoring::where('periode_pengajuan', $periode)
            ->where('source_type', 'pegawai')
            ->where('ruangan_id', (int) $target)
            ->whereIn('status_pengajuan', $this->lockedStatuses)
            ->first();

        if (!$existing) {
            return null;
        }

        $nama = DB::table('ruangan')->where('id', (int) $target)->value('nama_ruangan') ?? 'ruangan ini';

        return "Pengajuan untuk $nama periode $label sudah diajukan (status: {$existing->status_pengajuan}) "
            . 'dan tidak dapat dibuat ulang.';
    }

    public function create(Request $request)
    {
        $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'ruangan' => ['required'],
        ]);

        $user = Auth::user();
        $target = (string) $request->ruangan;

        $this->authorizeTarget($user, $target);

        $periode = $this->parsePeriode($request->periode);
        $isTopLeader = $target === self::TARGET_TOP_LEADER;

        if ($request->input('mode') === 'baru') {
            if ($message = $this->alreadySubmittedMessage($periode, $target)) {
                return redirect()
                    ->route('index.scoring.index')
                    ->with('error', $message);
            }

            $sudahAdaDraft = IndexScoring::where('periode_pengajuan', $periode)
                ->when(
                    $isTopLeader,
                    fn($q) => $q->where('source_type', 'top_leader'),
                    fn($q) => $q->where('source_type', 'pegawai')->where('ruangan_id', (int) $target)
                )
                ->exists();

            if ($sudahAdaDraft) {
                session()->now('warning', 'Pengajuan periode ' . $periode->translatedFormat('F Y')
                    . ' sudah pernah disimpan. Data yang tersimpan dimuat kembali, bukan dibuat ulang.');
            }
        }

        $item = $this->buildPeriodeData(
            $periode,
            $isTopLeader ? [] : [(int) $target],
            $isTopLeader
        );

        $data = collect([$item]);
        $ruanganOptions = $this->ruanganOptions($user);

        return view('pages.IndexScoring', compact('data', 'ruanganOptions'));
    }

    // =====================================================================
    // BUILD DATA FORM
    // =====================================================================

    /**
     * Susun data 1 periode untuk SATU target:
     *  - $ruanganIds diisi  => form pegawai untuk ruangan-ruangan itu saja
     *  - $withTopLeader     => section Top Leader (tanpa ruangan)
     *
     * Pegawai yang pada periode ini SUDAH punya record di ruangan lain
     * (mis. pindah ruangan setelah ruangan lama mengajukan) tidak dijadikan
     * input, melainkan dikumpulkan di 'pegawai_ruangan_lain' untuk info.
     */
    private function buildPeriodeData(Carbon $periode, array $ruanganIds, bool $withTopLeader = false): object
    {
        $scoring = IndexScoring::where('periode_pengajuan', $periode)
            ->where(function ($q) use ($ruanganIds, $withTopLeader) {
                if (!empty($ruanganIds)) {
                    $q->whereIn('ruangan_id', $ruanganIds);
                }
                if ($withTopLeader) {
                    !empty($ruanganIds)
                        ? $q->orWhere('source_type', 'top_leader')
                        : $q->where('source_type', 'top_leader');
                }
                if (empty($ruanganIds) && !$withTopLeader) {
                    $q->whereRaw('1 = 0');
                }
            })
            ->get();

        $draftScoring = $scoring->keyBy(fn($row) => $row->source_type . '_' . $row->source_id);
        $scoringByRuangan = $scoring->where('source_type', 'pegawai')->groupBy('ruangan_id');

        $ruangans = !empty($ruanganIds)
            ? DB::table('ruangan')->whereIn('id', $ruanganIds)->where('is_active', 1)->orderBy('nama_ruangan')->get()
            : collect();

        // Pegawai: ruangan yang dirender + pegawai yang sudah tersimpan di ruangan tsb
        // (supaya pegawai yang sudah pindah tetap tampil di snapshot terkunci).
        $pegawaiMasterById = collect();
        if ($ruangans->isNotEmpty()) {
            $pegawaiIdsTersimpan = $scoring->pluck('pegawai_id')->filter()->values();

            $pegawaiMasterById = DB::table('pegawai')
                ->where(function ($q) use ($ruangans, $pegawaiIdsTersimpan) {
                    $q->whereIn('ruangan_id', $ruangans->pluck('id'))
                        ->orWhereIn('id', $pegawaiIdsTersimpan);
                })
                ->orderBy('nama')
                ->get()
                ->keyBy('id');
        }
        $pegawaiByRuangan = $pegawaiMasterById->groupBy('ruangan_id');

        // Record periode ini untuk pegawai di atas, DI RUANGAN MANA PUN.
        // Dipakai untuk mendeteksi pegawai yang sudah dinilai ruangan lain.
        $scoringPegawaiPeriode = $pegawaiMasterById->isEmpty()
            ? collect()
            : IndexScoring::where('periode_pengajuan', $periode)
                ->where('source_type', 'pegawai')
                ->whereIn('source_id', $pegawaiMasterById->keys())
                ->get(['source_id', 'ruangan_id', 'status_pengajuan'])
                ->keyBy('source_id');

        $namaRuanganById = $scoringPegawaiPeriode->isEmpty()
            ? collect()
            : DB::table('ruangan')
                ->whereIn('id', $scoringPegawaiPeriode->pluck('ruangan_id')->filter()->unique())
                ->pluck('nama_ruangan', 'id');

        // ---------- Regular pegawai per ruangan ----------
        $ruanganData = $ruangans->map(function ($ruangan) use (
            $pegawaiByRuangan,
            $draftScoring,
            $scoringByRuangan,
            $pegawaiMasterById,
            $scoringPegawaiPeriode,
            $namaRuanganById
        ) {
            $scoringRuanganIni = $scoringByRuangan->get($ruangan->id, collect());

            $statusRuangan = optional($scoringRuanganIni->first())->status_pengajuan ?? 'draft';
            $disableInputRuangan = in_array($statusRuangan, $this->lockedStatuses);
            $catatanRevisiRuangan = optional($scoringRuanganIni->first())->catatan_revisi;

            $pegawaiRuanganLain = collect();

            if ($disableInputRuangan) {
                // PERIODE SUDAH TERKUNCI: tampilkan snapshot yang tersimpan
                $pegawaiList = $scoringRuanganIni
                    ->sortBy(fn($row) => optional($pegawaiMasterById->get($row->pegawai_id))->nama)
                    ->map(function ($row) use ($pegawaiMasterById) {
                        $p = $pegawaiMasterById->get($row->pegawai_id);
                        $gajiPokok = (int) ($row->gaji_pokok ?? $p->gaji_pokok ?? 0);

                        return (object) [
                            'id' => $row->pegawai_id,
                            'nama' => $p->nama ?? $row->pegawai_id,
                            'id_petugas' => $p->id_petugas ?? null,
                            'source_type' => 'pegawai',
                            'source_id' => $row->pegawai_id,

                            'gaji_pokok' => $gajiPokok,
                            'gaji_pokok_display' => number_format($gajiPokok, 0, ',', '.'),

                            'jabatan' => $row->jabatan,
                            'pendidikan_formal' => $row->pendidikan_formal,
                            'pendidikan_non_formal' => $row->pendidikan_non_formal,

                            'risk' => $row->risk,
                            'emergency' => $row->emergency,

                            'cuti' => $row->cuti ?? 0,
                            'izin' => $row->izin ?? 0,
                            'tanpa_izin' => $row->tanpa_izin ?? 0,
                            'telat' => $row->telat ?? 0,
                            'sikap' => $row->sikap ?? 0,
                            'jumlah' => $row->jumlah ?? 0,
                            'jumlah_akhir' => $row->jumlah_akhir ?? 0,
                            'keterangan' => $row->keterangan ?? null,
                        ];
                    })
                    ->values();
            } else {
                // PERIODE BELUM TERKUNCI: master + overlay draft
                $pegawaiRuangan = $pegawaiByRuangan->get($ruangan->id, collect());

                // Pisahkan pegawai yang periode ini sudah tercatat di ruangan lain
                [$diRuanganLain, $pegawaiRuangan] = $pegawaiRuangan->partition(
                    function ($p) use ($scoringPegawaiPeriode, $ruangan) {
                        $rec = $scoringPegawaiPeriode->get($p->id);

                        return $rec && (int) $rec->ruangan_id !== (int) $ruangan->id;
                    }
                );

                $pegawaiRuanganLain = $diRuanganLain->map(function ($p) use ($scoringPegawaiPeriode, $namaRuanganById) {
                    $rec = $scoringPegawaiPeriode->get($p->id);

                    return (object) [
                        'id' => $p->id,
                        'nama' => $p->nama,
                        'ruangan' => $namaRuanganById->get($rec->ruangan_id) ?? '-',
                        'status' => $rec->status_pengajuan,
                    ];
                })->values();

                $pegawaiList = $pegawaiRuangan->map(function ($p) use ($draftScoring) {
                    $draft = $draftScoring->get('pegawai_' . $p->id);

                    $gajiPokokRaw = $draft->gaji_pokok ?? $p->gaji_pokok;

                    return (object) [
                        'id' => $p->id,
                        'nama' => $p->nama,
                        'id_petugas' => $p->id_petugas,
                        'source_type' => 'pegawai',
                        'source_id' => $p->id,

                        'gaji_pokok' => (int) $gajiPokokRaw,
                        'gaji_pokok_display' => number_format((int) $gajiPokokRaw, 0, ',', '.'),

                        'jabatan' => $draft->jabatan ?? $p->jabatan,
                        'pendidikan_formal' => $draft->pendidikan_formal ?? null,
                        'pendidikan_non_formal' => $draft->pendidikan_non_formal ?? $p->pendidikan_non_formal,

                        'risk' => $draft->risk ?? $p->risk,
                        'emergency' => $draft->emergency ?? $p->emergency,

                        'cuti' => $draft->cuti ?? 0,
                        'izin' => $draft->izin ?? 0,
                        'tanpa_izin' => $draft->tanpa_izin ?? 0,
                        'telat' => $draft->telat ?? 0,
                        'sikap' => $draft->sikap ?? 0,
                        'jumlah' => $draft->jumlah ?? 0,
                        'jumlah_akhir' => $draft->jumlah_akhir ?? 0,
                        'keterangan' => $draft->keterangan ?? null,
                    ];
                })->values();
            }

            return (object) [
                'id' => $ruangan->id,
                'ruangan' => (object) [
                    'nama_ruangan' => $ruangan->nama_ruangan,
                ],
                'status_pengajuan' => $statusRuangan,
                'disable_input' => $disableInputRuangan,
                'catatan_revisi' => $catatanRevisiRuangan,
                'pegawai' => $pegawaiList,
                'pegawai_ruangan_lain' => $pegawaiRuanganLain,
                'is_top_leader_section' => false,
            ];
        });

        // ---------- Top Leader (grouped by posisi, tanpa ruangan) ----------
        $topLeaderData = collect();

        if ($withTopLeader) {
            $topLeaders = TopLeader::where('is_active', true)
                ->orderBy('posisi')
                ->get();

            $topLeaderData = $topLeaders->groupBy('posisi')->map(function ($leaders, $posisi) use ($draftScoring, $scoring) {
                $leaderData = $leaders->map(function ($tl) use ($draftScoring, $scoring) {
                    $draft = $draftScoring->get('top_leader_' . $tl->id);
                    $existing = $scoring->where('source_type', 'top_leader')
                        ->where('source_id', $tl->id)
                        ->first();

                    $statusTL = $existing?->status_pengajuan ?? 'draft';
                    $disableInputTL = in_array($statusTL, $this->lockedStatuses);
                    $catatanRevisiTL = $existing?->catatan_revisi;

                    $gajiPokok = $draft->gaji_pokok ?? $tl->gaji_pokok ?? 0;

                    return (object) [
                        'id' => $tl->id,
                        'nama' => $tl->nama,
                        'posisi' => $tl->posisi,
                        'id_petugas' => $tl->posisi,
                        'source_type' => 'top_leader',
                        'source_id' => $tl->id,

                        'gaji_pokok' => (int) $gajiPokok,
                        'gaji_pokok_display' => number_format((int) $gajiPokok, 0, ',', '.'),

                        'jabatan' => $draft->jabatan ?? 0,
                        'pendidikan_formal' => $draft->pendidikan_formal ?? 0,
                        'pendidikan_non_formal' => $draft->pendidikan_non_formal ?? 0,

                        'risk' => $draft->risk ?? 0,
                        'emergency' => $draft->emergency ?? 0,

                        'cuti' => $draft->cuti ?? 0,
                        'izin' => $draft->izin ?? 0,
                        'tanpa_izin' => $draft->tanpa_izin ?? 0,
                        'telat' => $draft->telat ?? 0,
                        'sikap' => $draft->sikap ?? 0,
                        'jumlah' => $draft->jumlah ?? 0,
                        'jumlah_akhir' => $draft->jumlah_akhir ?? 0,
                        'keterangan' => $draft->keterangan ?? null,

                        'status_pengajuan' => $statusTL,
                        'disable_input' => $disableInputTL,
                        'catatan_revisi' => $catatanRevisiTL,
                    ];
                })->values();

                $statuses = $leaderData->pluck('status_pengajuan');
                $statusGroup = 'draft';
                if ($statuses->contains('submit')) {
                    $statusGroup = 'submit';
                } elseif ($statuses->contains('verifikasi')) {
                    $statusGroup = 'verifikasi';
                } elseif ($statuses->contains('revisi')) {
                    $statusGroup = 'revisi';
                } elseif ($statuses->contains('selesai')) {
                    $statusGroup = 'selesai';
                }

                return (object) [
                    'posisi' => $posisi,
                    'leaders' => $leaderData,
                    'status_pengajuan' => $statusGroup,
                    'disable_input' => in_array($statusGroup, $this->lockedStatuses),
                    'catatan_revisi' => $leaderData->firstWhere('catatan_revisi', '!=', null)?->catatan_revisi,
                ];
            })->values();
        }

        return (object) [
            'periode' => $periode->format('Y-m'),
            'periode_label' => $periode->translatedFormat('F Y'),
            'ruangans' => $ruanganData,
            'top_leaders' => $topLeaderData,
        ];
    }

    // =====================================================================
    // SIMPAN / SUBMIT
    // =====================================================================

    /**
     * Non-admin:
     *  - hanya boleh source_type 'pegawai'
     *  - ruangan dipaksa = ruangan miliknya
     *  - semua pegawai yang dikirim harus milik ruangannya
     */
    private function authorizeRows(Request $request): void
    {
        $user = Auth::user();

        if ($this->isAdmin($user)) {
            return;
        }

        $request->merge(['jasa_ruangan_id' => $user->ruangan_id]);

        $rows = collect($request->pegawai);

        abort_if(
            $rows->contains(fn($r) => ($r['source_type'] ?? 'pegawai') !== 'pegawai'),
            403
        );

        $ids = $rows
            ->map(fn($r) => $r['source_id'] ?? $r['pegawai_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $valid = DB::table('pegawai')
            ->whereIn('id', $ids)
            ->where('ruangan_id', $user->ruangan_id)
            ->count();

        abort_unless($valid === $ids->count(), 403);
    }

    private function redirectToForm(Request $request, Carbon $periode)
    {
        $rows = collect($request->pegawai);

        $isTopLeader = $rows->contains(
            fn($r) => ($r['source_type'] ?? 'pegawai') === 'top_leader'
        );

        $target = $isTopLeader
            ? self::TARGET_TOP_LEADER
            : ($request->jasa_ruangan_id ?: ($rows->first()['ruangan_id'] ?? null));

        if (!$target) {
            return redirect()->route('index.scoring.index');
        }

        return redirect()->route('index.scoring.create', [
            'periode' => $periode->format('Y-m'),
            'ruangan' => $target,
        ]);
    }

    /**
     * Klasifikasikan baris kiriman form terhadap record yang sudah ada di periode ini.
     *
     * @return array{rows: array, skipped: array, lockedLabel: ?string}
     *   rows        : baris yang boleh disimpan (+ sourceType, sourceId, ruanganId)
     *   skipped     : nama pegawai yang dilewati karena milik ruangan lain
     *   lockedLabel : terisi jika ada baris milik target ini yang terkunci
     */
    private function classifyRows(Request $request, Carbon $periode): array
    {
        $existing = IndexScoring::where('periode_pengajuan', $periode)
            ->get(['source_type', 'source_id', 'ruangan_id', 'status_pengajuan'])
            ->keyBy(fn($r) => $r->source_type . '_' . $r->source_id);

        $rows = [];
        $skipped = [];
        $lockedLabel = null;

        foreach ($request->pegawai as $pegawai) {
            $sourceType = $pegawai['source_type'] ?? 'pegawai';
            $sourceId = $pegawai['source_id'] ?? $pegawai['pegawai_id'];
            $ruanganId = $this->resolveRuanganId($request, $pegawai, $sourceType);

            $rec = $existing->get($sourceType . '_' . $sourceId);

            if ($rec) {
                // Sudah dinilai ruangan lain (status apa pun): jangan diambil alih
                if ($sourceType === 'pegawai' && (int) $rec->ruangan_id !== (int) $ruanganId) {
                    $skipped[] = $pegawai['nama'] ?? "ID $sourceId";
                    continue;
                }

                if (in_array($rec->status_pengajuan, $this->lockedStatuses)) {
                    $lockedLabel = $sourceType === 'top_leader' ? 'Top Leader' : 'Ruangan ini';
                    break;
                }
            }

            $rows[] = [
                'pegawai' => $pegawai,
                'sourceType' => $sourceType,
                'sourceId' => $sourceId,
                'ruanganId' => $ruanganId,
            ];
        }

        return compact('rows', 'skipped', 'lockedLabel');
    }

    /**
     * Simpan draft / submit. Key updateOrCreate menyertakan periode supaya
     * data lintas bulan tidak saling menimpa. Pegawai yang periode ini sudah
     * tercatat di ruangan lain dilewati, bukan ditimpa.
     */
    private function save(Request $request, string $status)
    {
        $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jasa_ruangan_id' => ['nullable'], // null untuk top leader
            'pegawai' => ['required', 'array'],
        ]);

        $this->authorizeRows($request);

        $periode = $this->parsePeriode($request->periode);

        // ---- Klasifikasi & lock check (sebelum transaksi) ----
        ['rows' => $rows, 'skipped' => $skipped, 'lockedLabel' => $lockedLabel] = $this->classifyRows($request, $periode);

        if ($lockedLabel) {
            return $this->redirectToForm($request, $periode)
                ->with('error', "$lockedLabel untuk periode " . $periode->translatedFormat('F Y') . ' sedang terkunci dan tidak bisa diubah.');
        }

        if (empty($rows)) {
            return $this->redirectToForm($request, $periode)
                ->with('error', 'Tidak ada data yang bisa disimpan. Semua pegawai sudah dinilai oleh ruangan lain untuk periode ini.');
        }

        // ---- Simpan ----
        DB::transaction(function () use ($rows, $status, $periode) {
            foreach ($rows as $row) {
                $pegawai = $row['pegawai'];
                $sourceType = $row['sourceType'];
                $sourceId = $row['sourceId'];

                $data = [
                    'ruangan_id' => $row['ruanganId'],
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'pegawai_id' => $sourceType === 'pegawai' ? $sourceId : null,
                    'jabatan' => $pegawai['jabatan'] ?? null,
                    'pendidikan_formal' => $pegawai['pendidikan_formal'] ?? null,
                    'pendidikan_non_formal' => is_numeric($pegawai['pendidikan_non_formal'] ?? null)
                        ? $pegawai['pendidikan_non_formal']
                        : 0,
                    'gaji_pokok' => (int) str_replace(['.', ','], '', $pegawai['gaji_pokok'] ?? 0),
                    'risk' => $pegawai['risk'] ?? null,
                    'emergency' => $pegawai['emergency'] ?? null,
                    'cuti' => ($pegawai['cuti'] ?? null) ?: 0,
                    'izin' => ($pegawai['izin'] ?? null) ?: 0,
                    'tanpa_izin' => ($pegawai['tanpa_izin'] ?? null) ?: 0,
                    'telat' => ($pegawai['telat'] ?? null) ?: 0,
                    'sikap' => $pegawai['sikap'] ?? 0,
                    'jumlah' => $pegawai['jumlah'] ?? 0,
                    'jumlah_akhir' => $pegawai['jumlah_akhir'] ?? 0,
                    'keterangan' => $pegawai['keterangan'] ?? null,
                    'status_pengajuan' => $status,
                ];

                IndexScoring::updateOrCreate(
                    [
                        'source_type' => $sourceType,
                        'source_id' => $sourceId,
                        'periode_pengajuan' => $periode,
                    ],
                    $data
                );
            }
        });

        $msg = $status === 'draft' ? 'Draft berhasil disimpan.' : 'Pengajuan berhasil dikirim.';

        if (!empty($skipped)) {
            $msg .= ' Dilewati karena sudah dinilai oleh ruangan lain: ' . implode(', ', $skipped) . '.';
        }

        return $this->redirectToForm($request, $periode)->with('success', $msg);
    }

    /**
     * Simpan sebagai draft, tanpa validasi kelengkapan.
     */
    public function store(Request $request)
    {
        return $this->save($request, 'draft');
    }

    /**
     * Submit final: semua baris wajib terisi lengkap (Jabatan & Pendidikan Formal).
     * Baris yang terkunci atau milik ruangan lain tidak divalidasi (save() yang menanganinya).
     */
    public function submit(Request $request)
    {
        $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jasa_ruangan_id' => ['nullable'],
            'pegawai' => ['required', 'array'],
        ]);

        $this->authorizeRows($request);

        $periode = $this->parsePeriode($request->periode);

        ['rows' => $rows, 'lockedLabel' => $lockedLabel] = $this->classifyRows($request, $periode);

        // Terkunci: biarkan save() yang menolak dengan pesan yang sama
        if (!$lockedLabel) {
            $incomplete = [];

            foreach ($rows as $row) {
                $pegawai = $row['pegawai'];

                $jabatanKosong = empty($pegawai['jabatan']) || (int) $pegawai['jabatan'] === 0;
                $pendidikanKosong = empty($pegawai['pendidikan_formal']) || (int) $pegawai['pendidikan_formal'] === 0;

                if ($jabatanKosong || $pendidikanKosong) {
                    $incomplete[] = $row['sourceType'] === 'top_leader'
                        ? ($pegawai['posisi'] ?? $pegawai['nama'] ?? 'Top Leader')
                        : ($pegawai['nama'] ?? 'Pegawai');
                }
            }

            if (!empty($incomplete)) {
                return back()
                    ->withInput()
                    ->with('error', 'Tidak bisa submit final, masih ada data yang belum lengkap (Jabatan/Pendidikan Formal) untuk: ' . implode(', ', $incomplete));
            }
        }

        return $this->save($request, 'submit');
    }
}