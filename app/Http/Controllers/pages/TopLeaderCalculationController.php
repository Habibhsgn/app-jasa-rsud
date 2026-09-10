<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Services\TopLeaderCalculationService;
use Illuminate\Http\Request;

class TopLeaderCalculationController extends Controller
{
    public function __construct(
        protected TopLeaderCalculationService $service
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

        return view('pages.top-leader.perhitungan', $data);

    }
}