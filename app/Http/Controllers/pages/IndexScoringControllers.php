<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\IndexScoring;
use App\Models\PeriodeJasa;
use App\Models\TopLeader;
use Illuminate\Support\Carbon;

class IndexScoringControllers extends Controller
{
    /**
     * Status yang mengunci form (tidak bisa diedit / disimpan ulang).
     */
    private array $lockedStatuses = ['submit', 'verifikasi', 'selesai'];

    /**
     * Tampilkan semua periode yang masih aktif (belum "selesai").
     *
     * PENTING: untuk user non-admin, daftar periode WAJIB difilter berdasarkan
     * ruangan_id miliknya sendiri. Kalau tidak difilter, periode yang dibuat/
     * disimpan oleh ruangan lain akan ikut muncul (dan ikut men-generate form
     * kosong) di halaman ruangan yang tidak bersangkutan.
     */
    public function index()
    {
        $user = Auth::user();

        $periodeQuery = IndexScoring::where('status_pengajuan', '!=', 'selesai');

        if ($user->role?->code != 'admin') {
            $periodeQuery->where('ruangan_id', $user->ruangan_id);
        }

        $periodeList = $periodeQuery
            ->distinct()
            ->orderBy('periode_pengajuan')
            ->pluck('periode_pengajuan');

        $data = $periodeList->map(fn($p) => $this->buildPeriodeData(Carbon::parse($p)));

        return view('pages.IndexScoring', compact('data'));
    }

    /**
     * Buat pengajuan baru untuk 1 periode.
     * Jika periode itu sudah ada UNTUK RUANGAN INI (non-admin), tolak dan arahkan
     * balik ke daftar (form lama tetap tampil). Jika periode baru, tambahkan
     * sebagai kartu terpisah di bawah periode-periode lain.
     *
     * Sama seperti index(), daftar periode di sini juga WAJIB difilter per
     * ruangan_id untuk non-admin, supaya generate periode oleh ruangan lain
     * tidak "bocor" ke ruangan yang sedang login.
     */
    public function create(Request $request)
    {
        $request->validate([
            'periode' => ['required'],
        ]);

        $user = Auth::user();
        $periode = Carbon::createFromFormat('Y-m', $request->periode)->startOfMonth();

        $sudahAdaQuery = IndexScoring::where('periode_pengajuan', $periode);

        if ($user->role?->code != 'admin') {
            $sudahAdaQuery->where('ruangan_id', $user->ruangan_id);
        }

        $sudahAda = $sudahAdaQuery->exists();

        if ($sudahAda) {
            return redirect()
                ->route('index.scoring.index')
                ->with('warning', 'Pengajuan untuk periode ' . $periode->format('F Y') . ' sudah ada. Silakan lanjutkan pada tabel yang sudah tersedia di bawah.');
        }

        $periodeQuery = IndexScoring::where('status_pengajuan', '!=', 'selesai');

        if ($user->role?->code != 'admin') {
            $periodeQuery->where('ruangan_id', $user->ruangan_id);
        }

        $periodeList = $periodeQuery
            ->distinct()
            ->pluck('periode_pengajuan')
            ->push($periode->toDateString())
            ->unique()
            ->sortBy(fn($p) => Carbon::parse($p))
            ->values();

        $data = $periodeList->map(fn($p) => $this->buildPeriodeData(Carbon::parse($p)));

        return view('pages.IndexScoring', compact('data'));
    }

    /**
     * Susun data 1 periode: ruangan -> pegawai, dengan draft (jika ada) di-overlay ke data master.
     * Tambahan: section Top Leader (tanpa ruangan)
     */
    private function buildPeriodeData(Carbon $periode): object
    {
        $scoring = IndexScoring::where('periode_pengajuan', $periode)->get();
        $draftScoring = $scoring->keyBy(function ($row) {
            return $row->source_type . '_' . $row->source_id;
        });

        // Kelompokkan record scoring per ruangan_id untuk menentukan status per ruangan
        $scoringByRuangan = $scoring->where('source_type', 'pegawai')->groupBy('ruangan_id');

        $user = Auth::user();

        if ($user->role?->code == 'admin') {
            $ruangans = DB::table('ruangan')
                ->where('is_active', 1)
                ->orderBy('nama_ruangan')
                ->get();
        } else {
            $ruangans = DB::table('ruangan')
                ->where('id', $user->ruangan_id)
                ->where('is_active', 1)
                ->get();
        }

        $pegawaiByRuangan = DB::table('pegawai')
            ->orderBy('nama')
            ->get()
            ->groupBy('ruangan_id');

        // Load Top Leaders (active only)
        $topLeaders = TopLeader::where('is_active', true)
            ->orderBy('posisi')
            ->get();

        // Lookup pegawai by id untuk histori
        $pegawaiMasterById = DB::table('pegawai')->get()->keyBy('id');

        // Build ruangan data (regular pegawai)
        $ruanganData = $ruangans->map(function ($ruangan) use ($pegawaiByRuangan, $draftScoring, $scoringByRuangan, $pegawaiMasterById) {
            $scoringRuanganIni = $scoringByRuangan->get($ruangan->id, collect());

            $statusRuangan = optional($scoringRuanganIni->first())->status_pengajuan ?? 'draft';
            $disableInputRuangan = in_array($statusRuangan, $this->lockedStatuses);
            $catatanRevisiRuangan = optional($scoringRuanganIni->first())->catatan_revisi;

            if ($disableInputRuangan) {
                // PERIODE SUDAH TERKUNCI
                $pegawaiList = $scoringRuanganIni
                    ->sortBy(function ($row) use ($pegawaiMasterById) {
                        return optional($pegawaiMasterById->get($row->pegawai_id))->nama;
                    })
                    ->map(function ($row) use ($pegawaiMasterById) {
                        $p = $pegawaiMasterById->get($row->pegawai_id);

                        return (object) [
                            'id' => $row->pegawai_id,
                            'nama' => $p->nama ?? $row->pegawai_id,
                            'id_petugas' => $p->id_petugas ?? null,
                            'source_type' => 'pegawai',
                            'source_id' => $row->pegawai_id,

                            'gaji_pokok' => (int) $p->gaji_pokok,
                            'gaji_pokok_display' => number_format((int) $p->gaji_pokok, 0, ',', '.'),

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
                // PERIODE BELUM TERKUNCI
                $pegawaiRuangan = $pegawaiByRuangan->get($ruangan->id, collect());

                $pegawaiList = $pegawaiRuangan->map(function ($p) use ($draftScoring) {
                    $key = 'pegawai_' . $p->id;
                    $draft = $draftScoring->get($key);

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

        // Build Top Leader section (grouped by posisi, tanpa ruangan)
        $topLeaderData = $topLeaders->groupBy('posisi')->map(function ($leaders, $posisi) use ($draftScoring, $scoring) {
            $leaderData = $leaders->map(function ($tl) use ($draftScoring, $scoring) {
                $key = 'top_leader_' . $tl->id;
                $draft = $draftScoring->get($key);
                $existing = $scoring->where('source_type', 'top_leader')
                    ->where('source_id', $tl->id)
                    ->first();

                $statusTL = $existing?->status_pengajuan ?? 'draft';
                $disableInputTL = in_array($statusTL, $this->lockedStatuses);
                $catatanRevisiTL = $existing?->catatan_revisi;

                $jabatan = $draft->jabatan ?? 0;
                $pendidikanFormal = $draft->pendidikan_formal ?? 0;
                $pendidikanNonFormal = $draft->pendidikan_non_formal ?? 0;
                $risk = $draft->risk ?? 0;
                $emergency = $draft->emergency ?? 0;
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

                    'jabatan' => $jabatan,
                    'pendidikan_formal' => $pendidikanFormal,
                    'pendidikan_non_formal' => $pendidikanNonFormal,

                    'risk' => $risk,
                    'emergency' => $emergency,

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

            // Determine overall status for this position group
            $statuses = $leaderData->pluck('status_pengajuan');
            $statusGroup = 'draft';
            if ($statuses->contains('submit'))
                $statusGroup = 'submit';
            elseif ($statuses->contains('verifikasi'))
                $statusGroup = 'verifikasi';
            elseif ($statuses->contains('revisi'))
                $statusGroup = 'revisi';
            elseif ($statuses->contains('selesai'))
                $statusGroup = 'selesai';

            $disableInputGroup = in_array($statusGroup, $this->lockedStatuses);
            $catatanRevisiGroup = $leaderData->firstWhere('catatan_revisi', '!=', null)?->catatan_revisi;

            return (object) [
                'posisi' => $posisi,
                'leaders' => $leaderData,
                'status_pengajuan' => $statusGroup,
                'disable_input' => $disableInputGroup,
                'catatan_revisi' => $catatanRevisiGroup,
            ];
        })->values();

        return (object) [
            'periode' => $periode->format('Y-m'),
            'periode_label' => $periode->translatedFormat('F Y'),
            'ruangans' => $ruanganData,
            'top_leaders' => $topLeaderData,
        ];
    }

    /**
     * Simpan draft / submit. Key updateOrCreate WAJIB menyertakan periode,
     * bukan hanya pegawai_id — kalau tidak, data lintas bulan akan saling menimpa.
     *
     * Lock-check sekarang berbasis (periode + ruangan_id) untuk pegawai,
     * dan (periode + source_id) untuk top leader,
     * karena tiap ruangan/top leader submit form-nya sendiri-sendiri.
     */
    private function save(Request $request, string $status)
    {
        $request->validate([
            'periode' => ['required'],
            'jasa_ruangan_id' => ['nullable'], // nullable for top leaders
            'pegawai' => ['required', 'array'],
        ]);

        $periode = Carbon::createFromFormat('Y-m', $request->periode)->startOfMonth();

        DB::transaction(function () use ($request, $status, $periode) {
            foreach ($request->pegawai as $pegawai) {
                $sourceType = $pegawai['source_type'] ?? 'pegawai';
                $sourceId = $pegawai['source_id'] ?? $pegawai['pegawai_id'];
                $ruanganId = $pegawai['ruangan_id'] ?? $request->jasa_ruangan_id;

                // Lock check
                $locked = IndexScoring::where('periode_pengajuan', $periode)
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->whereIn('status_pengajuan', $this->lockedStatuses)
                    ->exists();

                if ($locked) {
                    $label = $sourceType === 'top_leader' ? 'Top Leader' : 'Ruangan ini';
                    return redirect()
                        ->route('index.scoring.index')
                        ->with('error', "$label untuk periode " . $periode->format('F Y') . " sedang terkunci dan tidak bisa diubah.");
                }

                $data = [
                    'ruangan_id' => $ruanganId,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'jabatan' => $pegawai['jabatan'],
                    'pendidikan_formal' => $pegawai['pendidikan_formal'],
                    'pendidikan_non_formal' => is_numeric($pegawai['pendidikan_non_formal'])
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

                // Pegawai-specific fields
                if ($sourceType === 'pegawai') {
                    $data['pegawai_id'] = $sourceId;
                } else {
                    $data['pegawai_id'] = null;
                }

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



        return redirect()
            ->route('index.scoring.index')
            ->with(
                'success',
                $status === 'draft'
                ? 'Draft berhasil disimpan.'
                : 'Pengajuan berhasil dikirim.'
            );
    }

    /**
     * Simpan sebagai draft — tidak ada validasi kelengkapan.
     */
    public function store(Request $request)
    {
        return $this->save($request, 'draft');
    }

    /**
     * Submit final — wajib semua baris (yang punya id_petugas) terisi lengkap.
     * Divalidasi per ruangan/top leader.
     */
    public function submit(Request $request)
    {
        $request->validate([
            'periode' => ['required'],
            'jasa_ruangan_id' => ['nullable'],
            'pegawai' => ['required', 'array'],
        ]);

        $incomplete = [];

        foreach ($request->pegawai as $pegawai) {
            $sourceType = $pegawai['source_type'] ?? 'pegawai';
            $sourceId = $pegawai['source_id'] ?? $pegawai['pegawai_id'];

            // Only validate if not locked
            $existing = IndexScoring::where('periode_pengajuan', Carbon::createFromFormat('Y-m', $request->periode)->startOfMonth())
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->first();

            if ($existing && in_array($existing->status_pengajuan, $this->lockedStatuses)) {
                continue; // Already locked, skip validation
            }

            $jabatanKosong = empty($pegawai['jabatan']) || (int) $pegawai['jabatan'] === 0;
            $pendidikanKosong = empty($pegawai['pendidikan_formal']) || (int) $pegawai['pendidikan_formal'] === 0;

            if ($jabatanKosong || $pendidikanKosong) {
                $label = $sourceType === 'top_leader' ? ($pegawai['posisi'] ?? $pegawai['nama'] ?? 'Top Leader') : ($pegawai['nama'] ?? 'Pegawai');
                $incomplete[] = $label;
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