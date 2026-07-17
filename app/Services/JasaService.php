<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\InacbgClaim;

class JasaService
{
    public function getStaffBreakdown(InacbgClaim $claim): array
    {
        $persenJasa = (float) Setting::getValue('persen_jasa', 0);
        $persenStaff = (float) Setting::getValue('persen_jasa_staff', 0);

        if ($persenJasa <= 0) {
            throw new \RuntimeException('Setting persen_jasa belum dikonfigurasi');
        }
        if ($persenStaff <= 0) {
            throw new \RuntimeException('Setting persen_jasa_staff belum dikonfigurasi');
        }

        $totalJasa = round($claim->biaya_disetujui * ($persenJasa / 100), 2);
        $jasaStaff = round($totalJasa * ($persenStaff / 100), 2);

        // Ambil semua setting staff sekaligus (termasuk dokter_gigi)
        $staffSettings = Setting::where('key', 'like', 'staff.%')->get()->keyBy('key');

        $dokterGigiPct = (float) ($staffSettings['staff.dokter_gigi']->value ?? 0);

        // Logic ranap: gabung dokter gigi ke medis/paramedis
        $isRanap = $claim->jenis_rawat === 'ranap';
        $breakdown = [];
        $grandTotalStaff = 0;

        foreach ($staffSettings as $key => $s) {
            // Skip dokter_gigi di ranap (disederhanakan ke medis_paramedis)
            if ($isRanap && $key === 'staff.dokter_gigi') {
                continue;
            }

            $label = $s->label;
            $val = (float) $s->value;

            // Ranap: tambahkan persen dokter_gigi ke medis_paramedis
            if ($isRanap && $key === 'staff.medis_paramedis') {
                $val += $dokterGigiPct;
            }

            $jumlah = round($jasaStaff * ($val / 100), 2);
            $grandTotalStaff += $jumlah;

            $breakdown[] = [
                'label' => $label,
                'persen' => $val,
                'jumlah' => $jumlah
            ];
        }

        return [
            'totalJasa' => $totalJasa,
            'jasaStaff' => $jasaStaff,
            'persenStaff' => $persenStaff,
            'breakdown' => $breakdown,
            'grandTotalStaff' => round($grandTotalStaff, 2)
        ];
    }
}
