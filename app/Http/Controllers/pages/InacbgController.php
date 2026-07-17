<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Models\InacbgClaim;
use App\Services\InacbgService;
use App\Services\FeedbackPdfService;
use App\Services\JasaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InacbgController extends Controller
{
    public function __construct(
        protected InacbgService $inacbgService,
        protected FeedbackPdfService $feedbackPdfService,
        protected JasaService $jasaService
    ) {}


    /**
     * Display the inacbg data table with upload form.
     */
    public function index(Request $request)
    {
        $query = InacbgClaim::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        return view('pages.inacbg.index', compact('claims', 'stats'));
    }

    /**
     * Import inacbg.xlsx and feedback.xlsx.
     */

    public function import(Request $request)
    {
        ini_set('memory_limit', '512M');

        $request->validate([
            'file_inacbg'     => 'required|file|mimes:xlsx,xls',
            'file_feedback'   => 'nullable|array|max:2',
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
}
