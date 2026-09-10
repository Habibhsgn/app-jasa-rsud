<?php

namespace App\Services;

use App\Models\InacbgClaim;
use App\Models\Setting;
use App\Models\TopLeader;
use Carbon\Carbon;

class TopLeaderCalculationService
{
    public function calculate(?int $bulan = null, ?int $tahun = null, bool $hasFilter = false): array
    {
        // Kalau halaman baru pertama kali dibuka (belum ada filter sama sekali),
        // default ke bulan & tahun dari discharge_date terbaru yang berstatus disetujui
        if (! $hasFilter && ! $bulan && ! $tahun) {
            $latest = InacbgClaim::where('status', 'disetujui')
                ->whereNotNull('discharge_date')
                ->orderByDesc('discharge_date')
                ->first();

            if ($latest) {
                $bulan = (int) Carbon::parse($latest->discharge_date)->format('n');
                $tahun = (int) Carbon::parse($latest->discharge_date)->format('Y');
            }
        }

        // 1. Total jasa disetujui dari InacbgClaim (status = 'disetujui')
        $query = InacbgClaim::where('status', 'disetujui');

        if ($bulan) {
            $query->whereMonth('discharge_date', $bulan);
        }
        if ($tahun) {
            $query->whereYear('discharge_date', $tahun);
        }

        $totalJasaDisetujui = $query->orderBy('discharge_date')->sum('biaya_disetujui');

        // 2. Setting persen_jasa (44%)
        $persenJasa = Setting::getValue('persen_jasa', 44) / 100;

        // 3. Nilai persen jasa = total jasa * persen_jasa
        $nilaiPersenJasa = $totalJasaDisetujui * $persenJasa;

        // 4. Setting persen_jasa_top_leader (10%)
        $persenTopLeader = Setting::getValue('persen_jasa_top_leader', 10);

        // 5. Total Top Leader = nilai persen jasa * persen_jasa_top_leader
        $totalTopLeader = $nilaiPersenJasa * $persenTopLeader / 100;

        // 6. Ambil persentase top leader dari setting (top_leader.*)
        $topLeaderPositions = [
            'Direktur' => 'top_leader.direktur',
            'Kabid' => 'top_leader.kabid',
            'Kasie' => 'top_leader.kasie',
            'Bendahara' => 'top_leader.bendahara',
            'Casemix' => 'top_leader.casemix',
            'Costing' => 'top_leader.costing',
        ];

        $topLeaderPersen = [];
        foreach ($topLeaderPositions as $posisi => $key) {
            $topLeaderPersen[$posisi] = Setting::getValue($key, 0) / 100;
        }

        // 7. Ambil data top leader aktif dari database
        $topLeaders = TopLeader::where('is_active', true)->get();

        // 8. Hitung alokasi per posisi = totalTopLeader * persen posisi
        $calculations = [];
        foreach ($topLeaderPositions as $posisi => $key) {
            $persenPosisi = $topLeaderPersen[$posisi];
            $alokasiPosisi = $totalTopLeader * $persenPosisi;

            $orangDiPosisi = $topLeaders->where('posisi', $posisi);
            $jumlahOrang = $orangDiPosisi->count();
            $perOrang = $jumlahOrang > 0 ? $alokasiPosisi / $jumlahOrang : 0;

            $calculations[$posisi] = [
                'persen' => $persenPosisi * 100,
                'alokasi_total' => $alokasiPosisi,
                'jumlah_orang' => $jumlahOrang,
                'per_orang' => $perOrang,
                'anggota' => $orangDiPosisi->values(),
            ];
        }

        // 9. List tahun yang ada
        $availableYears = InacbgClaim::selectRaw('YEAR(discharge_date) as tahun')
            ->whereYear('discharge_date', '<=', now()->year)
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        return compact(
            'totalJasaDisetujui',
            'persenJasa',
            'nilaiPersenJasa',
            'totalTopLeader',
            'calculations',
            'persenTopLeader',
            'availableYears',
            'bulan',
            'tahun'
        );
    }
}