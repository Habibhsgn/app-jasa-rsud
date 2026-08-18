<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Services\PelayananCalculationService;
use Illuminate\Http\Request;

class PelayananCalculationController extends Controller
{
    public function __construct(
        protected PelayananCalculationService $service
    ) {
    }

    public function index(Request $request)
    {
        $hasFilter = $request->has('bulan') || $request->has('tahun');

        $data = $this->service->calculate(
            bulan: $request->integer('bulan') ?: null,
            tahun: $request->integer('tahun') ?: null,
            hasFilter: $hasFilter,
        );

        return view('pages.pelayanan.perhitungan', $data);

    }
}