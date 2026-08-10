<?php

namespace App\Services;

use App\Models\InacbgClaim;
use App\Models\IndexScoring;
use App\Models\Setting;
use App\Models\TopLeader;
use Carbon\Carbon;

class TopLeaderCalculationService
{
    public function calculate(?int $bulan = null, ?int $tahun = null, bool $hasFilter = false): array
    {
        // Kalau halaman baru pertama kali dibuka (belum ada filter sama sekali),
        // default ke bulan & tahun dari discharge_date terbaru yang berstatus disetujui
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

        // 6. Ambil data Index Scoring Top Leader yang status 'selesai' di periode yg sama
        $periodePengajuan = $bulan && $tahun
            ? Carbon::create($tahun, $bulan, 1)->startOfMonth()
            : null;

        $indexScoringQuery = IndexScoring::where('source_type', 'top_leader')
            ->where('status_pengajuan', 'selesai');

        if ($periodePengajuan) {
            $indexScoringQuery->where('periode_pengajuan', $periodePengajuan);
        }

        $topLeaderScorings = $indexScoringQuery->get();

        // 7. Top Leader Positions dari settings (untuk % alokasi per posisi)
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

        // 8. Ambil data top leader aktif dari database
        $topLeaders = TopLeader::where('is_active', true)->get();

        // 9. Hitung alokasi:
        //    - Alokasi posisi = Total Top Leader * % posisi dari settings
        //    - Di dalam posisi: bagi proporsional ke jumlah_akhir index scoring
        $calculations = [];
        $detailPerOrang = [];
        $totalBobot = 0;

        foreach ($topLeaderPositions as $posisi => $key) {
            $persenPosisi = $topLeaderPersen[$posisi];
            $alokasiPosisi = $totalTopLeader * $persenPosisi;

            $orangDiPosisi = $topLeaders->where('posisi', $posisi);
            $jumlahOrang = $orangDiPosisi->count();

            // Kumpulkan bobot index scoring per orang
            $anggota = [];
            $totalBobotPosisi = 0;

            foreach ($orangDiPosisi as $tl) {
                $scoring = $topLeaderScorings->where('source_id', $tl->id)->first();
                $jumlahAkhir = $scoring?->jumlah_akhir ?? 0;

                $anggota[] = [
                    'id' => $tl->id,
                    'nama' => $tl->nama,
                    'posisi' => $tl->posisi,
                    'gaji_pokok' => $tl->gaji_pokok,
                    'jumlah_akhir' => $jumlahAkhir,
                    'bobot_persen_posisi' => 0, // akan diisi setelah loop
                    'alokasi' => 0, // akan diisi setelah loop
                ];

                $totalBobotPosisi += $jumlahAkhir;
            }

            $totalBobot += $totalBobotPosisi;

            // Hitung alokasi per orang proporsional di dalam posisi
            foreach ($anggota as &$a) {
                $a['bobot_persen_posisi'] = $totalBobotPosisi > 0 ? round(($a['jumlah_akhir'] / $totalBobotPosisi) * 100, 2) : 0;
                $a['alokasi'] = $totalBobotPosisi > 0 ? round(($a['jumlah_akhir'] / $totalBobotPosisi) * $alokasiPosisi, 2) : ($jumlahOrang > 0 ? round($alokasiPosisi / $jumlahOrang, 2) : 0);
            }
            unset($a);

            $perOrang = $jumlahOrang > 0 ? $alokasiPosisi / $jumlahOrang : 0; // rata-rata untuk display

            $calculations[$posisi] = [
                'persen' => $persenPosisi * 100,
                'alokasi_total' => round($alokasiPosisi, 2),
                'jumlah_orang' => $jumlahOrang,
                'per_orang_rata' => round($perOrang, 2),
                'total_bobot' => round($totalBobotPosisi, 2),
                'anggota' => $anggota,
            ];

            // Flat list for detail view
            foreach ($anggota as $a) {
                $detailPerOrang[] = $a;
            }
        }

        // 10. List tahun yang ada
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
            'tahun',
            'detailPerOrang',
            'periodePengajuan',
            'totalBobot',
        );
    }
}