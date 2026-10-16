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
    | Helper hak akses keterangan (RBAC: User::hasPermission)
    |--------------------------------------------------------------------------
    | inacbg.keterangan.all        -> tab "Semua", lihat semua keterangan, import, ubah status
    | inacbg.keterangan.{section}  -> tab seksi + boleh isi keterangan seksi tsb
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

        $default = $canAll ? 'semua' : ($allowed[0] ?? null);
        $tab = $request->get('tab', $default);

        if ($tab === 'semua' && $canAll) {
            return 'semua';
        }

        if (is_string($tab) && in_array($tab, $allowed, true)) {
            return $tab;
        }

        abort(403);
    }

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
     * Angka per tab (mengikuti filter bulan & tahun):
     * semua, serta total / terisi / belum untuk tiap seksi yang boleh dilihat.
     */
    protected function tabStats(array $allowed, ?int $bulan, ?int $tahun): array
    {
        $selects = ['COUNT(*) AS semua'];
        foreach ($allowed as $key) {
            $col = InacbgClaim::SECTIONS[$key]['column'];
            $selects[] = "SUM({$col} > 0) AS {$key}_total";
            $selects[] = "SUM({$col} > 0 AND JSON_EXTRACT(keterangan, '$.{$key}') IS NOT NULL) AS {$key}_filled";
        }

        $row = InacbgClaim::query()
            ->when($bulan, fn($q) => $q->whereMonth('discharge_date', $bulan))
            ->when($tahun, fn($q) => $q->whereYear('discharge_date', $tahun))
            ->selectRaw(implode(', ', $selects))
            ->first();

        $stats = ['semua' => (int) ($row->semua ?? 0)];
        foreach ($allowed as $key) {
            $total = (int) ($row->{"{$key}_total"} ?? 0);
            $filled = (int) ($row->{"{$key}_filled"} ?? 0);
            $stats[$key] = ['total' => $total, 'filled' => $filled, 'empty' => max($total - $filled, 0)];
        }

        return $stats;
    }

    /**
     * Display the inacbg data table with upload form.
     */
    public function index(Request $request)
    {
        $canAll = $this->canViewAll();
        $allowed = $this->visibleSections();
        $tab = $this->resolveTab($request);

        // Default to latest discharge_date if no filter provided
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

        $query = InacbgClaim::query();

        // Filter tab seksi (hanya SEP yang punya biaya di seksi tsb)
        if ($tab !== 'semua') {
            $query->forSection($tab);
            $this->applyKeteranganFilter($query, $tab, $request->get('ket'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by month
        if ($bulan) {
            $query->whereMonth('discharge_date', $bulan);
        }

        // Filter by year
        if ($tahun) {
            $query->whereYear('discharge_date', $tahun);
        }

        // Search by SEP, MRN, or Nama
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('sep', 'like', "%{$s}%")
                    ->orWhere('mrn', 'like', "%{$s}%")
                    ->orWhere('nama_pasien', 'like', "%{$s}%")
                    ->orWhere('inacbg', 'like', "%{$s}%");
            });
        }

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSort = ['created_at', 'admission_date', 'discharge_date', 'status', 'nama_pasien', 'sep', 'total_tarif', 'laboratorium', 'radiologi', 'pelayanan_darah'];
        if (!in_array($sortField, $allowedSort)) {
            $sortField = 'created_at';
        }
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $claims = $query->orderBy($sortField, $sortDir)->paginate(25)->withQueryString();

        $stats = [
            'total' => InacbgClaim::count(),
            'pending' => InacbgClaim::pending()->count(),
            'disetujui' => InacbgClaim::disetujui()->count(),
        ];

        // Available years for filter dropdown
        $availableYears = InacbgClaim::selectRaw('YEAR(discharge_date) as tahun')
            ->whereYear('discharge_date', '<=', now()->year)
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $tabStats = $this->tabStats($allowed, $bulan, $tahun);
        $editable = array_values(array_filter($allowed, fn($k) => $this->canManage($k)));

        return view('pages.inacbg.index', compact(
            'claims',
            'stats',
            'availableYears',
            'bulan',
            'tahun',
            'tab',
            'canAll',
            'allowed',
            'editable',
            'tabStats'
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

        // Tab "semua" -> semua seksi yang boleh dilihat; tab seksi -> hanya seksi itu
        $keys = $tab === 'semua' ? $this->visibleSections() : [$tab];

        // Keterangan sesuai tab yang sedang dibuka
        $sections = [];
        foreach ($keys as $key) {
            $col = InacbgClaim::SECTIONS[$key]['column'];
            $data = $inacbgClaim->keterangan[$key] ?? null;
            $hasCost = (float) $inacbgClaim->$col > 0;

            // tampilkan seksi yang relevan (ada biayanya) atau sudah terisi
            if ($hasCost || $data) {
                $sections[$key] = [
                    'label' => InacbgClaim::SECTIONS[$key]['label'],
                    'data' => $data,
                    'canEdit' => $this->canManage($key) && $hasCost,
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

    /**
     * Simpan keterangan satu seksi (lab / radiologi / utd).
     */
    public function updateKeterangan(Request $request, InacbgClaim $inacbgClaim, string $section)
    {
        abort_unless($this->canManage($section), 403);

        $col = InacbgClaim::SECTIONS[$section]['column'];
        abort_if((float) $inacbgClaim->$col <= 0, 422, 'Klaim ini tidak memiliki biaya untuk seksi tersebut.');

        $validated = $request->validate([
            'teks' => 'required|string|max:2000',
        ]);

        // Lock baris supaya simpan bersamaan dari seksi lain tidak saling menimpa
        DB::transaction(function () use ($inacbgClaim, $section, $validated) {
            $claim = InacbgClaim::lockForUpdate()->findOrFail($inacbgClaim->id);

            $ket = $claim->keterangan ?? [];
            $ket[$section] = [
                'teks' => $validated['teks'],
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'updated_at' => now()->toDateTimeString(),
            ];

            $claim->update(['keterangan' => $ket]);
        });

        return back()->with('success', 'Keterangan berhasil disimpan.');
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

        // Default ke discharge_date terbaru jika tidak ada filter bulan/tahun
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

        $query = InacbgClaim::query();

        // Filter tab seksi
        if ($tab !== 'semua') {
            $query->forSection($tab);
            $this->applyKeteranganFilter($query, $tab, $request->get('ket'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by month
        if ($bulan) {
            $query->whereMonth('discharge_date', $bulan);
        }

        // Filter by year
        if ($tahun) {
            $query->whereYear('discharge_date', $tahun);
        }

        // Search by SEP, MRN, Nama, atau INA-CBG
        if ($request->filled('search')) {
            $s = $request->search;

            $query->where(function ($q) use ($s) {
                $q->where('sep', 'like', "%{$s}%")
                    ->orWhere('mrn', 'like', "%{$s}%")
                    ->orWhere('nama_pasien', 'like', "%{$s}%")
                    ->orWhere('inacbg', 'like', "%{$s}%");
            });
        }

        // Sort by DPJP
        $query->orderBy('dpjp', 'asc');

        $claims = $query->get();

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