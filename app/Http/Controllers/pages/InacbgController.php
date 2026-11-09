<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Models\InacbgClaim;
use App\Services\InacbgService;
use App\Services\FeedbackPdfService;
use App\Services\JasaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InacbgExport;
use Illuminate\Support\Carbon;

class InacbgController extends Controller
{
    public function __construct(
        protected InacbgService $inacbgService,
        protected FeedbackPdfService $feedbackPdfService,
        protected JasaService $jasaService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Helper hak akses (RBAC: User::hasPermission)
    |--------------------------------------------------------------------------
    | inacbg.keterangan.all        -> tab "Semua", lihat semua keterangan, import, ubah status
    | inacbg.keterangan.{section}  -> tab seksi + boleh isi keterangan seksi tsb
    | inacbg.raber                 -> tab "Raber" + boleh mengubah dokter utama
    */

    protected function canViewAll(): bool
    {
        return auth()->user()->hasPermission('inacbg.keterangan.all');
    }

    protected function canManage(string $section): bool
    {
        return isset(InacbgClaim::SECTIONS[$section])
            && auth()->user()->hasPermission("inacbg.keterangan.{$section}");
    }

    protected function canRaber(): bool
    {
        return auth()->user()->hasPermission('inacbg.raber');
    }

    /**
     * Seksi yang boleh dilihat user (key SECTIONS).
     */
    protected function visibleSections(): array
    {
        $keys = array_keys(InacbgClaim::SECTIONS);

        if ($this->canViewAll()) {
            return $keys;
        }

        return array_values(array_filter($keys, fn($k) => $this->canManage($k)));
    }

    /**
     * Tentukan tab aktif. 403 jika tab tidak valid / user tidak berhak.
     */
    protected function resolveTab(Request $request): string
    {
        $allowed = $this->visibleSections();
        $canAll = $this->canViewAll();

        $default = $canAll ? 'semua' : ($allowed[0] ?? ($this->canRaber() ? 'raber' : null));
        $tab = $request->get('tab', $default);

        if ($tab === 'semua' && $canAll) {
            return 'semua';
        }

        if ($tab === 'raber' && $this->canRaber()) {
            return 'raber';
        }

        if (is_string($tab) && in_array($tab, $allowed, true)) {
            return $tab;
        }

        abort(403);
    }

    /**
     * Respon sukses: JSON untuk request AJAX, redirect back untuk form biasa.
     */
    protected function done(Request $request, string $message)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | Filter
    |--------------------------------------------------------------------------
    */

    /**
     * Filter "belum / sudah diisi" untuk tab seksi.
     */
    protected function applyKeteranganFilter($query, string $tab, ?string $ket): void
    {
        if ($tab === 'semua' || !isset(InacbgClaim::SECTIONS[$tab])) {
            return;
        }

        if ($ket === 'belum') {
            $query->whereNull("keterangan->{$tab}");
        } elseif ($ket === 'sudah') {
            $query->whereNotNull("keterangan->{$tab}");
        }
    }

    /**
     * Filter tindakan (khusus tab radiologi): rontgen / usg / belum.
     */
    protected function applyTindakanFilter($query, string $tab, ?string $tindakan): void
    {
        if ($tab !== 'radiologi' || !$tindakan) {
            return;
        }

        if ($tindakan === 'belum') {
            $query->whereNull('radiologi_tindakan');
        } elseif (isset(InacbgClaim::TINDAKAN_RADIOLOGI[$tindakan])) {
            $query->whereIn('radiologi_tindakan', [$tindakan, 'rontgen,usg']);
        }
    }

    /**
     * Tab raber: hanya SEP dengan DPJP lebih dari satu dokter.
     * Filter status: default (belum diubah) / diubah.
     */
    protected function applyRaberFilter($query, string $tab, ?string $status): void
    {
        if ($tab !== 'raber') {
            return;
        }

        $query->raber();

        if ($status === 'diubah') {
            $query->whereNotNull('dpjp_asli');
        } elseif ($status === 'default') {
            $query->whereNull('dpjp_asli');
        }
    }

    /**
     * Periode (bulan, tahun). Jika tidak ada filter -> default ke discharge_date terbaru.
     *
     * @return array{0: ?int, 1: ?int}
     */
    protected function resolvePeriode(Request $request): array
    {
        $hasFilter = $request->has('bulan') || $request->has('tahun');
        $bulan = $request->integer('bulan') ?: null;
        $tahun = $request->integer('tahun') ?: null;

        if (!$hasFilter && !$bulan && !$tahun) {
            $latest = InacbgClaim::where('status', 'disetujui')
                ->whereNotNull('discharge_date')
                ->orderByDesc('discharge_date')
                ->first();

            if ($latest) {
                $bulan = (int) Carbon::parse($latest->discharge_date)->format('n');
                $tahun = (int) Carbon::parse($latest->discharge_date)->format('Y');
            }
        }

        return [$bulan, $tahun];
    }

    /**
     * Query terfilter (dipakai index & export): tab, status, periode, pencarian.
     */
    protected function filteredQuery(Request $request, string $tab, ?int $bulan, ?int $tahun)
    {
        $query = InacbgClaim::query();

        if ($tab === 'raber') {
            $this->applyRaberFilter($query, $tab, $request->get('dpjp_status'));
        } elseif ($tab !== 'semua') {
            $query->forSection($tab);
            $this->applyKeteranganFilter($query, $tab, $request->get('ket'));
            $this->applyTindakanFilter($query, $tab, $request->get('tindakan'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($bulan) {
            $query->whereMonth('discharge_date', $bulan);
        }

        if ($tahun) {
            $query->whereYear('discharge_date', $tahun);
        }

        // Cari SEP, MRN, Nama, INA-CBG (+ DPJP di tab raber)
        if ($request->filled('search')) {
            $s = $request->search;

            $query->where(function ($q) use ($s, $tab) {
                $q->where('sep', 'like', "%{$s}%")
                    ->orWhere('mrn', 'like', "%{$s}%")
                    ->orWhere('nama_pasien', 'like', "%{$s}%")
                    ->orWhere('inacbg', 'like', "%{$s}%");

                if ($tab === 'raber') {
                    $q->orWhere('dpjp', 'like', "%{$s}%");
                }
            });
        }

        return $query;
    }

    /**
     * Angka per tab (mengikuti filter bulan & tahun):
     * semua, raber (total / diubah), serta total / terisi / belum untuk tiap seksi.
     */
    protected function tabStats(array $allowed, ?int $bulan, ?int $tahun, bool $withRaber = false): array
    {
        $selects = ['COUNT(*) AS semua'];

        if ($withRaber) {
            $selects[] = "SUM(dpjp LIKE '%/%') AS raber_total";
            $selects[] = "SUM(dpjp LIKE '%/%' AND dpjp_asli IS NOT NULL) AS raber_diubah";
        }

        foreach ($allowed as $key) {
            $col = InacbgClaim::SECTIONS[$key]['column'];
            $hasCost = "({$col} > 0 OR JSON_EXTRACT(keterangan, '$.{$key}.harga_awal') IS NOT NULL)";
            $selects[] = "SUM({$hasCost}) AS {$key}_total";
            $selects[] = "SUM({$hasCost} AND JSON_EXTRACT(keterangan, '$.{$key}') IS NOT NULL) AS {$key}_filled";
        }

        $row = InacbgClaim::query()
            ->when($bulan, fn($q) => $q->whereMonth('discharge_date', $bulan))
            ->when($tahun, fn($q) => $q->whereYear('discharge_date', $tahun))
            ->selectRaw(implode(', ', $selects))
            ->first();

        $stats = ['semua' => (int) ($row->semua ?? 0)];

        if ($withRaber) {
            $total = (int) ($row->raber_total ?? 0);
            $diubah = (int) ($row->raber_diubah ?? 0);
            $stats['raber'] = ['total' => $total, 'diubah' => $diubah, 'default' => max($total - $diubah, 0)];
        }

        foreach ($allowed as $key) {
            $total = (int) ($row->{"{$key}_total"} ?? 0);
            $filled = (int) ($row->{"{$key}_filled"} ?? 0);
            $stats[$key] = ['total' => $total, 'filled' => $filled, 'empty' => max($total - $filled, 0)];
        }

        return $stats;
    }

    /**
     * Angka filter tindakan radiologi (mengikuti bulan & tahun).
     */
    protected function tindakanStats(?int $bulan, ?int $tahun): array
    {
        $r = InacbgClaim::forSection('radiologi')
            ->when($bulan, fn($q) => $q->whereMonth('discharge_date', $bulan))
            ->when($tahun, fn($q) => $q->whereYear('discharge_date', $tahun))
            ->selectRaw("COUNT(*) AS total,
                         SUM(radiologi_tindakan IN ('rontgen', 'rontgen,usg')) AS rontgen,
                         SUM(radiologi_tindakan IN ('usg', 'rontgen,usg')) AS usg,
                         SUM(radiologi_tindakan IS NULL) AS belum")
            ->first();

        return [
            'total' => (int) ($r->total ?? 0),
            'rontgen' => (int) ($r->rontgen ?? 0),
            'usg' => (int) ($r->usg ?? 0),
            'belum' => (int) ($r->belum ?? 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Halaman
    |--------------------------------------------------------------------------
    */

    /**
     * Display the inacbg data table with upload form.
     */
    public function index(Request $request)
    {
        $canAll = $this->canViewAll();
        $canRaber = $this->canRaber();
        $allowed = $this->visibleSections();
        $tab = $this->resolveTab($request);

        [$bulan, $tahun] = $this->resolvePeriode($request);

        $query = $this->filteredQuery($request, $tab, $bulan, $tahun);

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSort = [
            'created_at', 'admission_date', 'discharge_date', 'status', 'nama_pasien', 'sep',
            'total_tarif', 'laboratorium', 'radiologi', 'pelayanan_darah', 'dpjp',
        ];
        if (!in_array($sortField, $allowedSort)) {
            $sortField = 'created_at';
        }
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $claims = $query->orderBy($sortField, $sortDir)->paginate(25)->withQueryString();

        $stats = $tab === 'semua'
            ? [
                'total' => InacbgClaim::count(),
                'pending' => InacbgClaim::pending()->count(),
                'disetujui' => InacbgClaim::disetujui()->count(),
            ]
            : null;

        // Available years for filter dropdown
        $availableYears = InacbgClaim::selectRaw('YEAR(discharge_date) as tahun')
            ->whereYear('discharge_date', '<=', now()->year)
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tabStats = $this->tabStats($allowed, $bulan, $tahun, $canRaber);
        $tindakanStats = $tab === 'radiologi' ? $this->tindakanStats($bulan, $tahun) : null;
        $editable = array_values(array_filter($allowed, fn($k) => $this->canManage($k)));

        return view('pages.inacbg.index', compact(
            'claims',
            'stats',
            'availableYears',
            'bulan',
            'tahun',
            'tab',
            'canAll',
            'canRaber',
            'allowed',
            'editable',
            'tabStats',
            'tindakanStats'
        ));
    }

    /**
     * Import inacbg.xlsx and feedback PDF.
     */
    public function import(Request $request)
    {
        abort_unless($this->canViewAll(), 403);

        ini_set('memory_limit', '512M');

        $request->validate([
            'file_inacbg' => 'required|file|mimes:xlsx,xls',
            'file_feedback' => 'nullable|array|max:2',
            'file_feedback.*' => 'file|mimes:pdf',
        ]);

        $inacbgFullPath = $request->file('file_inacbg')->getRealPath();

        $feedbackFullPath = '';

        try {

            if ($request->hasFile('file_feedback')) {

                $feedbackFullPath = $this->feedbackPdfService
                    ->convertToExcel(
                        $request->file('file_feedback')
                    );
            }

            $result = $this->inacbgService->import(
                $inacbgFullPath,
                $feedbackFullPath
            );
        } catch (\Throwable $e) {

            return redirect()
                ->route('inacbg.index')
                ->with('error', $e->getMessage());
        } finally {

            if (!empty($feedbackFullPath)) {
                $this->feedbackPdfService->cleanup($feedbackFullPath);
            }
        }

        $message = "Berhasil import {$result['imported']} data baru";

        if ($result['updated'] > 0) {
            $message .= ", {$result['updated']} data diperbarui";
        }

        $message .= '.';

        if (!empty($result['errors'])) {
            return redirect()
                ->route('inacbg.index')
                ->with('error', implode(' ', $result['errors']));
        }

        return redirect()
            ->route('inacbg.index')
            ->with('success', $message);
    }

    /**
     * Show detail of a single claim.
     */
    public function show(Request $request, InacbgClaim $inacbgClaim)
    {
        $canAll = $this->canViewAll();
        $tab = $this->resolveTab($request);

        // Tab raber tidak punya halaman detail sendiri
        abort_if($tab === 'raber', 404);

        // Tab "semua" -> semua seksi yang boleh dilihat; tab seksi -> hanya seksi itu
        $keys = $tab === 'semua' ? $this->visibleSections() : [$tab];

        // Keterangan sesuai tab yang sedang dibuka
        $sections = [];
        foreach ($keys as $key) {
            $data = $inacbgClaim->keterangan[$key] ?? null;
            $hasCost = $inacbgClaim->hasSectionCost($key);

            // tampilkan seksi yang relevan (ada biayanya) atau sudah terisi
            if ($hasCost || $data) {
                $sections[$key] = [
                    'label' => InacbgClaim::SECTIONS[$key]['label'],
                    'data' => $data,
                    'canEdit' => $this->canManage($key) && $hasCost,
                    'tindakan' => $key === 'radiologi' ? $inacbgClaim->radiologi_tindakan : null,
                ];
            }
        }

        // User seksi tidak boleh membuka klaim yang bukan bagiannya
        if (!$canAll && empty($sections)) {
            abort(403);
        }

        $jasaData = $canAll ? $this->jasaService->getStaffBreakdown($inacbgClaim) : null;

        return view('pages.inacbg.show', compact('inacbgClaim', 'jasaData', 'sections', 'canAll', 'tab'));
    }

    /*
    |--------------------------------------------------------------------------
    | Aksi
    |--------------------------------------------------------------------------
    */

    /**
     * Simpan keterangan satu seksi (lab / radiologi / utd).
     */
    public function updateKeterangan(Request $request, InacbgClaim $inacbgClaim, string $section)
    {
        abort_unless($this->canManage($section), 403);

        $col = InacbgClaim::SECTIONS[$section]['column'];
        abort_unless($inacbgClaim->hasSectionCost($section), 422, 'Klaim ini tidak memiliki biaya untuk seksi tersebut.');

        // Radiologi: tindakan (Rontgen / USG) wajib dipilih dulu
        if ($section === 'radiologi' && !$inacbgClaim->radiologi_tindakan) {
            return back()->with('error', 'Pilih tindakan (Rontgen / USG) terlebih dahulu.');
        }

        // Form Rupiah menggunakan titik ribuan dan koma desimal. Klien lama
        // tetap dapat mengirim field harga berupa angka tanpa pemisah ribuan.
        if ($request->exists('harga_rupiah')) {
            $rupiah = $request->validate([
                'harga_rupiah' => ['required', 'string', 'regex:/^\d+(?:\.\d{3})*(?:,\d{1,2})?$/D'],
            ], [
                'harga_rupiah.regex' => 'Gunakan format harga seperti 500.000 atau 500.000,50.',
            ]);
            $request->merge([
                'harga' => str_replace(',', '.', str_replace('.', '', $rupiah['harga_rupiah'])),
            ]);
        }

        $validated = $request->validate([
            'teks' => 'required|string|max:2000',
            'harga' => 'sometimes|required|numeric|decimal:0,2|min:0|max:9999999999999.99',
        ]);

        // Lock baris supaya simpan bersamaan dari seksi lain tidak saling menimpa
        DB::transaction(function () use ($inacbgClaim, $section, $col, $validated) {
            $claim = InacbgClaim::lockForUpdate()->findOrFail($inacbgClaim->id);

            $ket = $claim->keterangan ?? [];
            $data = $ket[$section] ?? [];
            $updates = [];
            $updatedAt = now()->toDateTimeString();

            if (array_key_exists('harga', $validated)) {
                $hargaLama = number_format((float) $claim->$col, 2, '.', '');
                $hargaBaru = number_format((float) $validated['harga'], 2, '.', '');

                if ($hargaLama !== $hargaBaru) {
                    $data['harga_awal'] ??= $hargaLama;
                    $data['riwayat_harga'][] = [
                        'harga_lama' => $hargaLama,
                        'harga_baru' => $hargaBaru,
                        'user_id' => auth()->id(),
                        'user_name' => auth()->user()->name,
                        'updated_at' => $updatedAt,
                    ];
                    $updates[$col] = $hargaBaru;
                }
            }

            $ket[$section] = array_merge($data, [
                'teks' => $validated['teks'],
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'updated_at' => $updatedAt,
            ]);

            $claim->update(array_merge($updates, ['keterangan' => $ket]));
        });

        return back()->with('success', 'Keterangan dan harga berhasil disimpan.');
    }

    /**
     * Pilih / ubah satu atau kedua tindakan radiologi (rontgen / usg).
     */
    public function updateTindakan(Request $request, InacbgClaim $inacbgClaim)
    {
        abort_unless($this->canManage('radiologi'), 403);
        abort_unless($inacbgClaim->hasSectionCost('radiologi'), 422, 'Klaim ini tidak memiliki biaya Radiologi.');

        // Tetap menerima satu nilai dari form / klien lama.
        if (is_string($request->input('tindakan'))) {
            $request->merge(['tindakan' => [$request->input('tindakan')]]);
        }

        $validated = $request->validate([
            'tindakan' => 'required|array|min:1|max:2',
            'tindakan.*' => 'required|string|distinct|in:' . implode(',', array_keys(InacbgClaim::TINDAKAN_RADIOLOGI)),
        ]);

        $tindakan = array_intersect(array_keys(InacbgClaim::TINDAKAN_RADIOLOGI), $validated['tindakan']);

        $inacbgClaim->update(['radiologi_tindakan' => implode(',', $tindakan)]);

        return $this->done(
            $request,
            'Tindakan radiologi: ' . $inacbgClaim->radiologiTindakanLabel() . '.'
        );
    }

    /**
     * Jadikan salah satu dokter sebagai dokter utama (dipindah ke urutan pertama).
     */
    public function updateDpjpUtama(Request $request, InacbgClaim $inacbgClaim)
    {
        abort_unless($this->canRaber(), 403);

        $validated = $request->validate([
            'dokter' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $inacbgClaim) {
            $claim = InacbgClaim::lockForUpdate()->findOrFail($inacbgClaim->id);

            $docs = InacbgClaim::splitDpjp($claim->dpjp);
            abort_if(count($docs) < 2, 422, 'Klaim ini bukan rawat bersama.');

            $idx = array_search(trim($validated['dokter']), $docs, true);
            abort_if($idx === false, 422, 'Dokter tidak ditemukan pada DPJP klaim ini.');

            if ($idx === 0) {
                return; // sudah jadi dokter utama
            }

            $utama = $docs[$idx];
            unset($docs[$idx]);
            $baru = implode(' / ', [$utama, ...array_values($docs)]);

            $asli = $claim->dpjp_asli ?? $claim->dpjp;

            // Urutan kembali sama dengan aslinya -> anggap default lagi
            if ($baru === implode(' / ', InacbgClaim::splitDpjp($asli))) {
                $claim->update([
                    'dpjp' => $asli,
                    'dpjp_asli' => null,
                    'dpjp_diubah_oleh' => null,
                    'dpjp_diubah_at' => null,
                ]);

                return;
            }

            $claim->update([
                'dpjp' => $baru,
                'dpjp_asli' => $asli,
                'dpjp_diubah_oleh' => auth()->user()->name,
                'dpjp_diubah_at' => now(),
            ]);
        });

        return $this->done($request, 'Dokter utama berhasil diubah.');
    }

    /**
     * Kembalikan DPJP ke urutan asli (sesuai file import).
     */
    public function resetDpjp(Request $request, InacbgClaim $inacbgClaim)
    {
        abort_unless($this->canRaber(), 403);
        abort_if($inacbgClaim->dpjp_asli === null, 422, 'DPJP masih default.');

        $inacbgClaim->update([
            'dpjp' => $inacbgClaim->dpjp_asli,
            'dpjp_asli' => null,
            'dpjp_diubah_oleh' => null,
            'dpjp_diubah_at' => null,
        ]);

        return $this->done($request, 'DPJP dikembalikan ke default.');
    }

    /**
     * Update status manually.
     */
    public function updateStatus(Request $request, InacbgClaim $inacbgClaim)
    {
        abort_unless($this->canViewAll(), 403);

        $request->validate([
            'status' => 'required|in:pending,disetujui',
        ]);

        $inacbgClaim->update(['status' => $request->status]);

        return redirect()->route('inacbg.index')
            ->with('success', "Status berhasil diperbarui menjadi {$request->status}.");
    }

    public function export(Request $request)
    {
        $tab = $this->resolveTab($request);

        [$bulan, $tahun] = $this->resolvePeriode($request);

        $claims = $this->filteredQuery($request, $tab, $bulan, $tahun)
            ->orderBy('dpjp', 'asc')
            ->get();

        // Generate filename
        if ($bulan && $tahun) {
            $namaBulan = Carbon::create()
                ->month($bulan)
                ->locale('id')
                ->translatedFormat('F');

            $filename = 'INA-CBG_EXPORT_' . strtoupper($namaBulan) . '_' . $tahun;
        } elseif ($bulan) {
            $namaBulan = Carbon::create()
                ->month($bulan)
                ->locale('id')
                ->translatedFormat('F');

            $filename = 'INA-CBG_EXPORT_' . strtoupper($namaBulan) . '_' . now()->year;
        } elseif ($tahun) {
            $filename = 'INA-CBG_EXPORT_SEMUA_BULAN_' . $tahun;
        } else {
            $filename = 'INA-CBG_EXPORT_' . now()->format('Ymd_His');
        }

        if ($tab !== 'semua') {
            $filename .= '_' . strtoupper($tab);
        }

        return Excel::download(
            new InacbgExport($claims),
            $filename . '.xlsx'
        );
    }
}
