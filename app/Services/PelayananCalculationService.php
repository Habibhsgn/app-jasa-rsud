<?php

namespace App\Services;

use App\Models\InacbgClaim;
use App\Models\IndexScoring;
use App\Models\Setting;
use App\Models\Pegawai;
use App\Models\Ruangan;
use App\Models\MasterBidang;
use Carbon\Carbon;

class PelayananCalculationService
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

        // 4. Setting persen_jasa_staff (90%)
        $persenStaff = Setting::getValue('persen_jasa_staff', 90) / 100;

        // 5. Total Pelayanan = nilai persen jasa * persen_jasa_staff
        $totalPelayanan = $nilaiPersenJasa * $persenStaff;

        // 6. Ambil data Index Scoring Pelayanan yang status 'selesai' di periode yg sama
        $periodePengajuan = $bulan && $tahun
            ? Carbon::create($tahun, $bulan, 1)->startOfMonth()
            : null;

        $indexScoringQuery = IndexScoring::where('source_type', 'pegawai')
            ->where('status_pengajuan', 'selesai');

        if ($periodePengajuan) {
            $indexScoringQuery->where('periode_pengajuan', $periodePengajuan);
        }

        $pegawaiScorings = $indexScoringQuery->get();

        // 7. Pelayanan Positions dari settings (untuk % alokasi per posisi)
        // Menggunakan key staff.* dari settings
        $pelayananPositions = [
            'Staf Manajemen'       => 'staff.staf_manajemen',
            'Dokter Umum'          => 'staff.dokter_umum',
            'Dokter Gigi'          => 'staff.dokter_gigi',
            'Medis & Paramedis'    => 'staff.medis_paramedis',
        ];

        $pelayananPersen = [];
        foreach ($pelayananPositions as $posisi => $key) {
            $pelayananPersen[$posisi] = Setting::getValue($key, 0) / 100;
        }

        // 8. Ambil data pegawai aktif dari database (table pegawai) dengan relasi ruangan dan bidang
        $pegawaiList = Pegawai::where('status', 'aktif')
            ->with(['ruangan.bidang'])
            ->get();

        // 9. Hitung alokasi:
        //    - Alokasi posisi = Total Pelayanan * % posisi dari settings
        //    - Di dalam posisi: bagi proporsional ke jumlah_akhir index scoring
        $calculations = [];
        $detailPerOrang = [];
        $totalBobot = 0;

        foreach ($pelayananPositions as $posisi => $key) {
            $persenPosisi = $pelayananPersen[$posisi];
            $alokasiPosisi = $totalPelayanan * $persenPosisi;

            // Filter pegawai berdasarkan posisi/jabatan DAN bidang ruangan
            $orangDiPosisi = $this->filterPegawaiByPosition($pegawaiList, $posisi);
            $jumlahOrang = $orangDiPosisi->count();

            // Kumpulkan bobot index scoring per orang
            $anggota = [];
            $totalBobotPosisi = 0;

            foreach ($orangDiPosisi as $pegawai) {
                $scoring = $pegawaiScorings->where('source_id', $pegawai->id)->first();
                $jumlahAkhir = $scoring?->jumlah_akhir ?? 0;

                // Cek apakah ruangan sudah punya bidang (untuk Dokter Umum & Dokter Gigi)
                $ruanganBelumDiSet = false;
                $bidangNama = null;
                if (in_array($posisi, ['Dokter Umum', 'Dokter Gigi'])) {
                    $ruanganBelumDiSet = !$pegawai->ruangan || !$pegawai->ruangan->bidang_id;
                    $bidangNama = $pegawai->ruangan?->bidang?->nama_bidang ?? null;
                }

                $anggota[] = [
                    'id' => $pegawai->id,
                    'nama' => $pegawai->nama,
                    'posisi' => $pegawai->jabatan,
                    'ruangan' => $pegawai->ruangan?->nama_ruangan ?? '-',
                    'bidang' => $bidangNama,
                    'ruangan_belum_di_set' => $ruanganBelumDiSet,
                    'gaji_pokok' => $pegawai->gaji_pokok,
                    'jumlah_akhir' => $jumlahAkhir,
                    'bobot_persen_posisi' => 0,
                    'alokasi' => 0,
                ];

                $totalBobotPosisi += $jumlahAkhir;
            }

            $totalBobot += $totalBobotPosisi;

            // Hitung alokasi per orang proporsional di dalam posisi
            foreach ($anggota as &$a) {
                $a['bobot_persen_posisi'] = $totalBobotPosisi > 0
                    ? round(($a['jumlah_akhir'] / $totalBobotPosisi) * 100, 2)
                    : 0;
                $a['alokasi'] = $totalBobotPosisi > 0
                    ? round(($a['jumlah_akhir'] / $totalBobotPosisi) * $alokasiPosisi, 2)
                    : ($jumlahOrang > 0 ? round($alokasiPosisi / $jumlahOrang, 2) : 0);
            }
            unset($a);

            $perOrang = $jumlahOrang > 0 ? $alokasiPosisi / $jumlahOrang : 0;

            // Untuk Medis & Paramedis, hanya tampilkan alokasi total (akan dipecah lagi)
            $isMedisParamedis = $posisi === 'Medis & Paramedis';

            // Group by ruangan for detail view (only for non-Medis Paramedis)
            $detailByRuangan = [];
            if (!$isMedisParamedis) {
                foreach ($anggota as $a) {
                    $ruanganKey = $a['ruangan'] ?? 'Tanpa Ruangan';
                    if (!isset($detailByRuangan[$ruanganKey])) {
                        $detailByRuangan[$ruanganKey] = [];
                    }
                    $detailByRuangan[$ruanganKey][] = $a;
                }
            }

            $calculations[$posisi] = [
                'persen' => $persenPosisi * 100,
                'alokasi_total' => round($alokasiPosisi, 2),
                'jumlah_orang' => $isMedisParamedis ? null : $jumlahOrang,
                'per_orang_rata' => $isMedisParamedis ? null : round($perOrang, 2),
                'total_bobot' => round($totalBobotPosisi, 2),
                'anggota' => $anggota,
                'is_medis_paramedis' => $isMedisParamedis,
                'detail_by_ruangan' => $detailByRuangan,
            ];
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
            'totalPelayanan',
            'persenStaff',
            'calculations',
            'availableYears',
            'bulan',
            'tahun',
            'detailPerOrang',
            'periodePengajuan',
            'totalBobot',
        );
    }

    /**
     * Filter pegawai berdasarkan posisi/jabatan dan bidang ruangan
     */
    private function filterPegawaiByPosition($pegawaiList, $posisi)
    {
        return $pegawaiList->filter(function ($pegawai) use ($posisi) {
            $jabatan = $pegawai->jabatan ?? '';
            $bidangId = $pegawai->ruangan?->bidang_id ?? null;

            switch ($posisi) {
                case 'Staf Manajemen':
                    // Staf Manajemen = bidang MANAJEMEN (id: 4)
                    return $bidangId == 4;

                case 'Dokter Umum':
                    // Dokter Umum = jabatan mengandung "Dokter" DAN bidang PELAYANAN (id: 1)
                    // Atau bisa juga dari jabatan "Staf Bidang Pelayanan Medik" dll
                    return stripos($jabatan, 'dokter') !== false && $bidangId == 1;

                case 'Dokter Gigi':
                    // Dokter Gigi = jabatan mengandung "Gigi" DAN bidang PELAYANAN (id: 1)
                    return stripos($jabatan, 'gigi') !== false && $bidangId == 1;

                case 'Medis & Paramedis':
                    // Medis & Paramedis = bidang PELAYANAN (id: 1) DAN bukan Dokter Umum/Gigi
                    // Atau bidang PENUNJANG (id: 2) dan KEPERAWATAN (id: 3)
                    if (!$bidangId) return false;
                    $isDokterUmum = stripos($jabatan, 'dokter') !== false && $bidangId == 1;
                    $isDokterGigi = stripos($jabatan, 'gigi') !== false && $bidangId == 1;
                    return !$isDokterUmum && !$isDokterGigi && in_array($bidangId, [1, 2, 3]);

                default:
                    return false;
            }
        });
    }
}