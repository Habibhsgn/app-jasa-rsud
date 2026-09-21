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
    // HELPER OTORISASI
    // =====================================================================

    private function isAdmin($user): bool
    {
        return $user->role?->code === 'admin';
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

    // =====================================================================
    // INDEX: daftar pengajuan yang sudah tersimpan (TANPA render form)
    // =====================================================================

    /**
     * Tampilkan daftar pengajuan (periode + ruangan) yang sudah tersimpan di DB
     * dan belum "selesai". Form tidak dirender di sini; user membukanya satu per
     * satu lewat link ke create() dengan parameter periode + ruangan.
     *
     * Non-admin difilter berdasarkan ruangan_id miliknya.
     */
    public function index()
    {
        $user = Auth::user();

        // ---- KARU: render form semua periode tersimpan, khusus ruangannya ----
        // Aman (ringan) karena tiap form hanya berisi pegawai 1 ruangan.
        if (!$this->isAdmin($user)) {
            $periodeList = IndexScoring::where('status_pengajuan', '!=', 'selesai')
                ->where('ruangan_id', $user->ruangan_id)
                ->distinct()
                ->orderBy('periode_pengajuan')
                ->pluck('periode_pengajuan');

            $data = $periodeList->map(
                fn($p) => $this->buildPeriodeData(Carbon::parse($p), [(int) $user->ruangan_id], false)
            );

            $ruanganOptions = $this->ruanganOptions($user);

            return view('pages.IndexScoring', compact('data', 'ruanganOptions'));
        }

        // ---- ADMIN: 1 expand = 1 PERIODE, isinya semua ruangan yang sudah ada pengajuannya ----
        // Paginasi dilakukan per periode supaya jumlah form yang dirender tetap terbatas.
        $list = IndexScoring::query()
            ->where('status_pengajuan', '!=', 'selesai')
            ->select('periode_pengajuan', DB::raw('MAX(updated_at) as terakhir_update'))
            ->groupBy('periode_pengajuan')
            ->orderByDesc('periode_pengajuan')
            ->paginate(5);

        $periodeRaw = $list->getCollection()
            ->map(fn($r) => $r->getRawOriginal('periode_pengajuan'))
            ->values();

        // Ruangan / top leader mana saja yang sudah tersimpan pada periode-periode di halaman ini
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

            return $this->buildPeriodeData(Carbon::parse($raw), $ruanganIds, $hasTopLeader);
        })->values();

        $ruanganOptions = $this->ruanganOptions($user);

        return view('pages.IndexScoring', compact('data', 'list', 'ruanganOptions'));
    }

    // =====================================================================
    // CREATE: render 1 form (1 periode x 1 ruangan / top leader), tanpa menyimpan
    // =====================================================================

    /**
     * Cek apakah pengajuan untuk periode + target SUDAH DIAJUKAN (status terkunci:
     * submit / verifikasi / selesai). Dipakai untuk mencegah pengajuan ganda
     * saat user membuka form baru lewat tombol "Buka Form".
     *
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

            // Top Leader diajukan per posisi; blokir hanya jika SEMUA sudah diajukan
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

    /**
     * Render form untuk 1 periode + 1 target. Belum ada yang disimpan ke DB
     * sampai user klik "Simpan Draft" atau "Submit".
     *
     * Karena data draft yang sudah ada ikut dimuat, method ini juga dipakai
     * untuk membuka kembali pengajuan yang sudah tersimpan.
     */
    public function create(Request $request)
    {
        $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'ruangan' => ['required'],
        ]);

        $user = Auth::user();
        $target = (string) $request->ruangan;

        $this->authorizeTarget($user, $target);

        $periode = Carbon::createFromFormat('Y-m', $request->periode)->startOfMonth();
        $isTopLeader = $target === self::TARGET_TOP_LEADER;

        // Pembukaan form BARU lewat tombol "Buka Form" (mode=baru): tolak jika sudah diajukan.
        // Melihat form yang sudah terkunci (mis. redirect setelah submit) tidak dicek di sini,
        // karena form tersebut memang hanya tampil (disabled) dan save() tetap menolak perubahan.
        if ($request->input('mode') === 'baru') {
            if ($message = $this->alreadySubmittedMessage($periode, $target)) {
                return redirect()
                    ->route('index.scoring.index')
                    ->with('error', $message);
            }

            // Draft/revisi yang sudah ada: tidak menduplikasi, data tersimpan dimuat kembali
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
     * Draft (jika ada) di-overlay ke data master.
     */
    private function buildPeriodeData(Carbon $periode, array $ruanganIds, bool $withTopLeader = false): object
    {
        // Hanya muat scoring milik target yang dirender
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

        // Ruangan yang dirender
        $ruangans = !empty($ruanganIds)
            ? DB::table('ruangan')->whereIn('id', $ruanganIds)->where('is_active', 1)->orderBy('nama_ruangan')->get()
            : collect();

        // Pegawai: diambil SEKALI, hanya untuk ruangan yang dirender.
        // Tambahan whereIn id supaya pegawai yang sudah pindah ruangan tetap
        // ditemukan pada data yang sudah terkunci.
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

        // ---------- Regular pegawai per ruangan ----------
        $ruanganData = $ruangans->map(function ($ruangan) use ($pegawaiByRuangan, $draftScoring, $scoringByRuangan, $pegawaiMasterById) {
            $scoringRuanganIni = $scoringByRuangan->get($ruangan->id, collect());

            $statusRuangan = optional($scoringRuanganIni->first())->status_pengajuan ?? 'draft';
            $disableInputRuangan = in_array($statusRuangan, $this->lockedStatuses);
            $catatanRevisiRuangan = optional($scoringRuanganIni->first())->catatan_revisi;

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

                    // Fallback ke gaji_pokok dari tabel top_leaders jika draft kosong
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

                // Status keseluruhan untuk grup posisi ini
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
     * Otorisasi baris-baris yang dikirim form (dropdown & hidden input bisa dimanipulasi).
     *
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

    /**
     * Redirect kembali ke form yang sedang dibuka (periode + ruangan / top-leader),
     * supaya setelah Simpan Draft / Submit form tetap tampil dengan data terbaru
     * (atau tampil terkunci jika sudah submit).
     */
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
     * Simpan draft / submit. Key updateOrCreate WAJIB menyertakan periode,
     * bukan hanya pegawai_id, supaya data lintas bulan tidak saling menimpa.
     *
     * Lock-check dilakukan SEBELUM transaksi, karena `return` di dalam closure
     * DB::transaction hanya keluar dari closure, bukan dari method.
     */
    private function save(Request $request, string $status)
    {
        $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jasa_ruangan_id' => ['nullable'], // null untuk top leader
            'pegawai' => ['required', 'array'],
        ]);

        $this->authorizeRows($request);

        $periode = Carbon::createFromFormat('Y-m', $request->periode)->startOfMonth();

        // ---- Lock check (1 query, sebelum transaksi) ----
        $lockedKeys = IndexScoring::where('periode_pengajuan', $periode)
            ->whereIn('status_pengajuan', $this->lockedStatuses)
            ->get(['source_type', 'source_id'])
            ->map(fn($r) => $r->source_type . '_' . $r->source_id)
            ->flip();

        foreach ($request->pegawai as $pegawai) {
            $sourceType = $pegawai['source_type'] ?? 'pegawai';
            $sourceId = $pegawai['source_id'] ?? $pegawai['pegawai_id'];

            if ($lockedKeys->has($sourceType . '_' . $sourceId)) {
                $label = $sourceType === 'top_leader' ? 'Top Leader' : 'Ruangan ini';

                return $this->redirectToForm($request, $periode)
                    ->with('error', "$label untuk periode " . $periode->format('F Y') . ' sedang terkunci dan tidak bisa diubah.');
            }
        }

        // ---- Simpan ----
        DB::transaction(function () use ($request, $status, $periode) {
            foreach ($request->pegawai as $pegawai) {
                $sourceType = $pegawai['source_type'] ?? 'pegawai';
                $sourceId = $pegawai['source_id'] ?? $pegawai['pegawai_id'];

                $ruanganId = $sourceType === 'top_leader'
                    ? null
                    : ($pegawai['ruangan_id'] ?? $request->jasa_ruangan_id);

                // Non-admin: ruangan selalu ruangannya sendiri
                if (!$this->isAdmin(Auth::user()) && $sourceType === 'pegawai') {
                    $ruanganId = Auth::user()->ruangan_id;
                }

                $data = [
                    'ruangan_id' => $ruanganId,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'pegawai_id' => $sourceType === 'pegawai' ? $sourceId : null,
                    'jabatan' => $pegawai['jabatan'],
                    'pendidikan_formal' => $pegawai['pendidikan_formal'],
                    'pendidikan_non_formal' => is_numeric($pegawai['pendidikan_non_formal'] ?? null)
                        ? $pegawai['pendidikan_non_formal']
                        : 0,
                    'gaji_pokok' => (int) str_replace(['.', ','], '', $pegawai['gaji_pokok'] ?? 0),
                    'risk' => $pegawai['risk'],
                    'emergency' => $pegawai['emergency'],
                    'cuti' => $pegawai['cuti'] ?: 0,
                    'izin' => $pegawai['izin'] ?: 0,
                    'tanpa_izin' => $pegawai['tanpa_izin'] ?: 0,
                    'telat' => $pegawai['telat'] ?: 0,
                    'sikap' => $pegawai['sikap'],
                    'jumlah' => $pegawai['jumlah'],
                    'jumlah_akhir' => $pegawai['jumlah_akhir'],
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

        return $this->redirectToForm($request, $periode)
            ->with(
                'success',
                $status === 'draft'
                    ? 'Draft berhasil disimpan.'
                    : 'Pengajuan berhasil dikirim.'
            );
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
     * Divalidasi per ruangan / top leader.
     */
    public function submit(Request $request)
    {
        $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jasa_ruangan_id' => ['nullable'],
            'pegawai' => ['required', 'array'],
        ]);

        $this->authorizeRows($request);

        $periode = Carbon::createFromFormat('Y-m', $request->periode)->startOfMonth();

        // Ambil status terkunci sekali saja (bukan query per baris)
        $lockedKeys = IndexScoring::where('periode_pengajuan', $periode)
            ->whereIn('status_pengajuan', $this->lockedStatuses)
            ->get(['source_type', 'source_id'])
            ->map(fn($r) => $r->source_type . '_' . $r->source_id)
            ->flip();

        $incomplete = [];

        foreach ($request->pegawai as $pegawai) {
            $sourceType = $pegawai['source_type'] ?? 'pegawai';
            $sourceId = $pegawai['source_id'] ?? $pegawai['pegawai_id'];

            // Sudah terkunci: lewati validasi (save() yang akan menolak)
            if ($lockedKeys->has($sourceType . '_' . $sourceId)) {
                continue;
            }

            $jabatanKosong = empty($pegawai['jabatan']) || (int) $pegawai['jabatan'] === 0;
            $pendidikanKosong = empty($pegawai['pendidikan_formal']) || (int) $pegawai['pendidikan_formal'] === 0;

            if ($jabatanKosong || $pendidikanKosong) {
                $incomplete[] = $sourceType === 'top_leader'
                    ? ($pegawai['posisi'] ?? $pegawai['nama'] ?? 'Top Leader')
                    : ($pegawai['nama'] ?? 'Pegawai');
            }
        }

        if (!empty($incomplete)) {
            return back()
                ->withInput()
                ->with('error', 'Tidak bisa submit final, masih ada data yang belum lengkap (Jabatan/Pendidikan Formal) untuk: ' . implode(', ', $incomplete));
        }

        return $this->save($request, 'submit');
    }
}
