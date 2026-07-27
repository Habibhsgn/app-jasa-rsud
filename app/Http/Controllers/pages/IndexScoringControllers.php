<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\IndexScoring;
use App\Models\PeriodeJasa;
use Illuminate\Support\Carbon;

class IndexScoringControllers extends Controller
{
    /**
     * Status yang mengunci form (tidak bisa diedit / disimpan ulang).
     */
    private array $lockedStatuses = ['submit', 'verifikasi', 'selesai'];

    /**
     * Tampilkan semua periode yang masih aktif (belum "selesai").
     */
    public function index()
    {
        $periodeList = IndexScoring::where('status_pengajuan', '!=', 'selesai')
            ->distinct()
            ->orderBy('periode_pengajuan')
            ->pluck('periode_pengajuan');

        $data = $periodeList->map(fn($p) => $this->buildPeriodeData(Carbon::parse($p)));

        return view('pages.IndexScoring', compact('data'));
    }

    /**
     * Buat pengajuan baru untuk 1 periode.
     * Jika periode itu sudah ada, tolak dan arahkan balik ke daftar (form lama tetap tampil).
     * Jika periode baru, tambahkan sebagai kartu terpisah di bawah periode-periode lain.
     */
    public function create(Request $request)
    {
        $request->validate([
            'periode' => ['required'],
        ]);

        $periode = Carbon::createFromFormat('Y-m', $request->periode)->startOfMonth();

        $sudahAda = IndexScoring::where('periode_pengajuan', $periode)->exists();

        if ($sudahAda) {
            return redirect()
                ->route('index.scoring.index')
                ->with('warning', 'Pengajuan untuk periode ' . $periode->format('F Y') . ' sudah ada. Silakan lanjutkan pada tabel yang sudah tersedia di bawah.');
        }

        $periodeList = IndexScoring::where('status_pengajuan', '!=', 'selesai')
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
     *
     * PENTING: status_pengajuan, disable_input, dan catatan_revisi sekarang dihitung
     * PER RUANGAN (bukan per periode), karena tiap ruangan submit form-nya sendiri-sendiri
     * (lihat view: satu <form> per ruangan). Jadi ruangan A bisa berstatus "submit"
     * sementara ruangan B masih "draft" di periode yang sama.
     */
    private function buildPeriodeData(Carbon $periode): object
    {
        $scoring = IndexScoring::where('periode_pengajuan', $periode)->get();
        $draftScoring = $scoring->keyBy('pegawai_id');

        // Kelompokkan record scoring per ruangan_id untuk menentukan status per ruangan
        $scoringByRuangan = $scoring->groupBy('ruangan_id');

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

        // Lookup pegawai by id untuk histori
        $pegawaiMasterById = DB::table('pegawai')->get()->keyBy('id');

        $ruanganData = $ruangans->map(function ($ruangan) use (
            $pegawaiByRuangan,
            $draftScoring,
            $scoringByRuangan,
            $pegawaiMasterById
        ) {

            // Data scoring khusus ruangan ini
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

                            'gaji_pokok' => (int) $row->gaji_pokok,
                            'gaji_pokok_display' => number_format((int) $row->gaji_pokok, 0, ',', '.'),

                            'jabatan' => $row->jabatan,
                            'pendidikan_formal' => $row->pendidikan_formal,
                            'pendidikan_non_formal' => $row->pendidikan_non_formal,

                            // Tetap gunakan snapshot historis
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

                    $draft = $draftScoring->get($p->id);

                    $gajiPokokRaw = $draft->gaji_pokok ?? $p->gaji_pokok;

                    return (object) [

                        'id' => $p->id,
                        'nama' => $p->nama,
                        'id_petugas' => $p->id_petugas,

                        'gaji_pokok' => (int) $gajiPokokRaw,
                        'gaji_pokok_display' => number_format((int) $gajiPokokRaw, 0, ',', '.'),

                        'jabatan' => $draft->jabatan ?? $p->jabatan,
                        'pendidikan_formal' => $draft->pendidikan_formal ?? null,
                        'pendidikan_non_formal' => $draft->pendidikan_non_formal ?? $p->pendidikan_non_formal,

                        // Ambil dari pegawai
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
            ];
        });

        return (object) [
            'periode' => $periode->format('Y-m'),
            'periode_label' => $periode->translatedFormat('F Y'),
            'ruangans' => $ruanganData,
        ];
    }

    /**
     * Simpan draft / submit. Key updateOrCreate WAJIB menyertakan periode,
     * bukan hanya pegawai_id — kalau tidak, data lintas bulan akan saling menimpa.
     *
     * Lock-check sekarang berbasis (periode + ruangan_id), bukan seluruh periode,
     * karena tiap ruangan submit form-nya sendiri-sendiri.
     */
    private function save(Request $request, string $status)
    {
        $request->validate([
            'periode' => ['required'],
            'jasa_ruangan_id' => ['required'],
            'pegawai' => ['required', 'array'],
        ]);

        $periode = Carbon::createFromFormat('Y-m', $request->periode)->startOfMonth();
        $ruanganId = $request->jasa_ruangan_id;

        $locked = IndexScoring::where('periode_pengajuan', $periode)
            ->where('ruangan_id', $ruanganId)
            ->whereIn('status_pengajuan', $this->lockedStatuses)
            ->exists();

        if ($locked) {
            return redirect()
                ->route('index.scoring.index')
                ->with('error', 'Pengajuan ruangan ini untuk periode ' . $periode->format('F Y') . ' sedang terkunci dan tidak bisa diubah.');
        }

        DB::transaction(function () use ($request, $status, $periode) {

            foreach ($request->pegawai as $pegawai) {

                IndexScoring::updateOrCreate(

                    [
                        'pegawai_id' => $pegawai['pegawai_id'],
                        'periode_pengajuan' => $periode,
                    ],

                    [
                        'ruangan_id' => $pegawai['ruangan_id'],
                        'jabatan' => $pegawai['jabatan'],

                        'pendidikan_formal' => $pegawai['pendidikan_formal'],

                        'pendidikan_non_formal' => is_numeric($pegawai['pendidikan_non_formal'])
                            ? $pegawai['pendidikan_non_formal']
                            : 0,

                        // Buang pemisah ribuan sebelum disimpan, pastikan integer murni
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
                    ]

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
     * Divalidasi per ruangan (sesuai isi $request->pegawai, yang hanya berisi
     * pegawai dari satu ruangan karena form sekarang per-ruangan).
     */
    public function submit(Request $request)
    {
        $request->validate([
            'periode' => ['required'],
            'jasa_ruangan_id' => ['required'],
            'pegawai' => ['required', 'array'],
        ]);

        $pegawaiIds = collect($request->pegawai)->pluck('pegawai_id');

        $pegawaiMaster = DB::table('pegawai')
            ->whereIn('id', $pegawaiIds)
            ->get(['id', 'id_petugas', 'nama'])
            ->keyBy('id');

        $incomplete = [];

        foreach ($request->pegawai as $pegawai) {
            $master = $pegawaiMaster->get($pegawai['pegawai_id']);

            // Pegawai belum punya ID petugas -> baris memang terkunci, lewati validasi
            if (!$master || empty($master->id_petugas)) {
                continue;
            }

            $jabatanKosong = empty($pegawai['jabatan']) || (int) $pegawai['jabatan'] === 0;
            $pendidikanKosong = empty($pegawai['pendidikan_formal']) || (int) $pegawai['pendidikan_formal'] === 0;

            if ($jabatanKosong || $pendidikanKosong) {
                $incomplete[] = $master->nama;
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
