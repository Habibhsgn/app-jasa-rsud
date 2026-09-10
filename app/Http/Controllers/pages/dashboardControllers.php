<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Models\JasaRuangan;
use App\Models\Pegawai;
use App\Models\Ruangan;
use App\Models\IndexScoring;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class dashboardControllers extends Controller
{
    public function index()
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | QUERY JASA
        |--------------------------------------------------------------------------
        */

        $queryJasa = JasaRuangan::with([
            'periode',
            'ruangan',
        ])
            ->whereIn('status', [
                'proses_karu',
                'verifikasi',
                'selesai',
            ]);

        /*
        |--------------------------------------------------------------------------
        | QUERY PEGAWAI
        |--------------------------------------------------------------------------
        */

        $pegawaiQuery = Pegawai::query();

        /*
        |--------------------------------------------------------------------------
        | QUERY RUANGAN
        |--------------------------------------------------------------------------
        */

        $ruanganQuery = Ruangan::query();

        /*
        |--------------------------------------------------------------------------
        | QUERY INDEX SCORING
        |--------------------------------------------------------------------------
        |
        | Load:
        | IndexScoring
        | -> Ruangan
        | -> Bidang
        |
        */

        $indexScoringQuery = IndexScoring::with([
            'ruangan.bidang',
        ]);

        /*
        |--------------------------------------------------------------------------
        | ROLE FILTER
        |--------------------------------------------------------------------------
        */

        if ($user->role?->code === 'koordinator_karu') {

            /*
            |--------------------------------------------------------------------------
            | Hanya ruangan user
            |--------------------------------------------------------------------------
            */

            $queryJasa->where(
                'ruangan_id',
                $user->ruangan_id
            );

            $pegawaiQuery->where(
                'ruangan_id',
                $user->ruangan_id
            );

            $ruanganQuery->where(
                'id',
                $user->ruangan_id
            );

            $indexScoringQuery->where(
                'ruangan_id',
                $user->ruangan_id
            );

            $labelPegawai = 'Pegawai di Ruangan Anda';
        } elseif ($user->role?->code === 'karu') {

            /*
            |--------------------------------------------------------------------------
            | Karu melihat semua data
            |--------------------------------------------------------------------------
            */

            $labelPegawai = 'Semua Pegawai (Koordinator)';
        } else {

            /*
            |--------------------------------------------------------------------------
            | Admin / role lainnya
            |--------------------------------------------------------------------------
            */

            $labelPegawai = 'Total Seluruh Pegawai';
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL PEGAWAI
        |--------------------------------------------------------------------------
        */

        $totalPegawai = $pegawaiQuery->count();

        /*
        |--------------------------------------------------------------------------
        | TOTAL RUANGAN
        |--------------------------------------------------------------------------
        */

        $ruanganCount = $ruanganQuery->count();

        /*
        |--------------------------------------------------------------------------
        | STATISTIC STATUS JASA
        |--------------------------------------------------------------------------
        */

        $totalProses = (clone $queryJasa)
            ->where(
                'status',
                'proses_karu'
            )
            ->count();

        $totalVerifikasi = (clone $queryJasa)
            ->where(
                'status',
                'verifikasi'
            )
            ->count();

        $totalSelesai = (clone $queryJasa)
            ->where(
                'status',
                'selesai'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | DATA JASA
        |--------------------------------------------------------------------------
        */

        $dataJasa = $queryJasa
            ->latest()
            ->get()
            ->groupBy(function ($item) {

                return $item->periode?->periode
                    ?? 'unknown';
            });

        /*
        |--------------------------------------------------------------------------
        | AMBIL DATA INDEX SCORING
        |--------------------------------------------------------------------------
        */

        $indexRows = $indexScoringQuery
            ->orderByDesc('periode_pengajuan')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | DATA INDEX SCORING
        |--------------------------------------------------------------------------
        |
        | Struktur:
        |
        | Bidang
        |   └── Periode
        |         └── Ruangan
        |
        */

        $dataIndexScoring = $indexRows
            ->groupBy(function ($item) {

                /*
                |--------------------------------------------------------------------------
                | Ambil nama bidang
                |--------------------------------------------------------------------------
                |
                | Jangan langsung:
                |
                | $item->ruangan->bidang
                |
                | karena itu object/model.
                |
                */

                return $item->ruangan
                    ?->bidang
                    ?->nama_bidang
                    ?? 'Tanpa Bidang';
            })
            ->map(function ($bidangItems) {

                /*
                |--------------------------------------------------------------------------
                | GROUP PERIODE
                |--------------------------------------------------------------------------
                */

                return $bidangItems
                    ->groupBy(function ($item) {

                        if (!$item->periode_pengajuan) {

                            return 'unknown';
                        }

                        return Carbon::parse(
                            $item->periode_pengajuan
                        )->format('Y-m');
                    })
                    ->map(function ($periodeItems) {

                        /*
                        |--------------------------------------------------------------------------
                        | GROUP PER RUANGAN
                        |--------------------------------------------------------------------------
                        */

                        return $periodeItems
                            ->groupBy('ruangan_id')
                            ->map(function ($roomItems) {

                                $first = $roomItems->first();

                                /*
                                |--------------------------------------------------------------------------
                                | TOTAL PEGAWAI DI RUANGAN
                                |--------------------------------------------------------------------------
                                */

                                $totalPegawaiRuangan = Pegawai::query()
                                    ->where(
                                        'ruangan_id',
                                        $first->ruangan_id
                                    )
                                    ->count();

                                /*
                                |--------------------------------------------------------------------------
                                | JUMLAH PEGAWAI YANG SUDAH
                                | MEMILIKI INDEX SCORING
                                |--------------------------------------------------------------------------
                                */

                                $jumlahPegawaiIndex = $roomItems
                                    ->pluck('pegawai_id')
                                    ->filter()
                                    ->unique()
                                    ->count();

                                /*
                                |--------------------------------------------------------------------------
                                | HITUNG PROGRESS
                                |--------------------------------------------------------------------------
                                */

                                $progress = 0;

                                if ($totalPegawaiRuangan > 0) {

                                    $progress = round(
                                        (
                                            $jumlahPegawaiIndex
                                            /
                                            $totalPegawaiRuangan
                                        ) * 100,
                                        2
                                    );
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | BATASI MAKSIMAL 100%
                                |--------------------------------------------------------------------------
                                */

                                $progress = min(
                                    $progress,
                                    100
                                );

                                /*
                                |--------------------------------------------------------------------------
                                | STATUS TERBARU
                                |--------------------------------------------------------------------------
                                */

                                $latestData = $roomItems
                                    ->sortByDesc('updated_at')
                                    ->first();

                                /*
                                |--------------------------------------------------------------------------
                                | RETURN DATA
                                |--------------------------------------------------------------------------
                                */

                                return (object) [

                                    'ruangan_id' =>
                                    $first->ruangan_id,

                                    'nama_ruangan' =>
                                    $first->ruangan
                                        ?->nama_ruangan
                                        ?? '-',

                                    'nama_bidang' =>
                                    $first->ruangan
                                        ?->bidang
                                        ?->nama_bidang
                                        ?? 'Tanpa Bidang',

                                    'status_pengajuan' =>
                                    $latestData
                                        ?->status_pengajuan
                                        ?? 'draft',

                                    'total_pegawai' =>
                                    $totalPegawaiRuangan,

                                    'jumlah_pegawai_index' =>
                                    $jumlahPegawaiIndex,

                                    'progress' =>
                                    $progress,
                                ];
                            })
                            ->values();
                    });
            });

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'dashboard.dashboard',
            [
                'data' => $dataJasa,

                'dataIndexScoring' =>
                $dataIndexScoring,

                'totalPegawai' =>
                $totalPegawai,

                'labelPegawai' =>
                $labelPegawai,

                'totalRuangan' =>
                $ruanganCount,

                'totalProses' =>
                $totalProses,

                'totalVerifikasi' =>
                $totalVerifikasi,

                'totalSelesai' =>
                $totalSelesai,

                'koordinator' =>
                $user->role?->code === 'karu',
            ]
        );
    }
}
