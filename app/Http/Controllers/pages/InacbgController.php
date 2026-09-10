<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Models\InacbgClaim;
use App\Services\InacbgService;
use App\Services\FeedbackPdfService;
use App\Services\JasaService;
use Illuminate\Http\Request;
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


    /**
     * Display the inacbg data table with upload form.
     */
    public function index(Request $request)
    {
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
        $allowedSort = ['created_at', 'admission_date', 'status', 'nama_pasien', 'sep', 'total_tarif'];
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

        return view('pages.inacbg.index', compact('claims', 'stats', 'availableYears', 'bulan', 'tahun'));
    }

    /**
     * Import inacbg.xlsx and feedback.xlsx.
     */

    public function import(Request $request)
    {
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
    // public function import(Request $request)
    // {
    //     // Increase memory limit for large Excel files
    //     ini_set('memory_limit', '512M');

    //     $request->validate([
    //         'file_inacbg' => 'required|file|mimes:xlsx,xls',
    //         'file_feedback' => 'nullable|file|mimes:xlsx,xls',
    //     ]);

    //     $inacbgFullPath = $request->file('file_inacbg')->getRealPath();

    //     if ($request->hasFile('file_feedback')) {
    //         $feedbackFullPath = $request->file('file_feedback')->getRealPath();
    //     } else {
    //         $feedbackFullPath = '';
    //     }

    //     $result = $this->inacbgService->import($inacbgFullPath, $feedbackFullPath);

    //     $message = "Berhasil import {$result['imported']} data baru";
    //     if ($result['updated'] > 0) {
    //         $message .= ", {$result['updated']} data diperbarui";
    //     }
    //     $message .= '.';

    //     if (!empty($result['errors'])) {
    //         return redirect()->route('inacbg.index')
    //             ->with('error', implode(' ', $result['errors']));
    //     }

    //     return redirect()->route('inacbg.index')
    //         ->with('success', $message);
    // }

    /**
     * Show detail of a single claim.
     */
    public function show(InacbgClaim $inacbgClaim)
    {
        $jasaData = $this->jasaService->getStaffBreakdown($inacbgClaim);
        return view('pages.inacbg.show', compact('inacbgClaim', 'jasaData'));
    }

    /**
     * Update status manually.
     */
    public function updateStatus(Request $request, InacbgClaim $inacbgClaim)
    {
        $request->validate([
            'status' => 'required|in:pending,disetujui',
        ]);

        $inacbgClaim->update(['status' => $request->status]);

        return redirect()->route('inacbg.index')
            ->with('success', "Status berhasil diperbarui menjadi {$request->status}.");
    }

    public function export(Request $request)
    {
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

        return Excel::download(
            new InacbgExport($claims),
            $filename . '.xlsx'
        );
    }
}
