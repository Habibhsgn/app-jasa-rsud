<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InacbgTypeStatusSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    protected $data;
    protected $jenisRawat;
    protected $status;
    protected $title;
    protected $dpjpMap;

    public function __construct($data, $jenisRawat, $status, $title)
    {
        $this->data = $data;
        $this->jenisRawat = $jenisRawat;
        $this->status = $status;
        $this->title = $title;
        $this->dpjpMap = $this->buildDpjpMap($data);
    }

    public function collection()
    {
        return $this->data
            ->filter(function ($claim) {
                if ($this->status == 'pending') {
                    $admission = $claim->admission_date;
                    $discharge = $claim->discharge_date;
                    if ($admission && $discharge) {
                        $admissionStr = $admission instanceof \Carbon\Carbon
                            ? $admission->format('Y-m-d')
                            : (string) $admission;
                        $dischargeStr = $discharge instanceof \Carbon\Carbon
                            ? $discharge->format('Y-m-d')
                            : (string) $discharge;

                        $computed = $admissionStr === $dischargeStr ? 'ralan' : 'ranap';
                        return $computed === $this->jenisRawat;
                    }
                    return false;
                }
                return $claim->jenis_rawat === $this->jenisRawat;
            })
            ->where('status', $this->status)
            ->values();
    }
    public function title(): string
    {
        return $this->title;
    }

    protected function buildDpjpMap($data): array
    {
        // Collect all unique raw DPJP names from the full dataset
        $rawNames = $data
            ->pluck('dpjp')
            ->filter(fn($n) => $n !== null && trim($n) !== '')
            ->unique()
            ->values()
            ->toArray();

        // Normalize each name for comparison (remove titles, dots, extra spaces, lowercase)
        $normalizedMap = [];
        foreach ($rawNames as $raw) {
            $norm = $this->normalizeDpjp($raw);
            if ($norm !== '') {
                $normalizedMap[$norm][] = $raw;
            }
        }

        // For each normalized group, pick the "best" name (most complete/with title)
        $dpjpMap = [];
        foreach ($normalizedMap as $norm => $variants) {
            $dpjpMap[$norm] = $this->pickBestDpjp($variants);
        }

        // Build final map: raw name → best name
        $finalMap = [];
        foreach ($normalizedMap as $norm => $variants) {
            $best = $dpjpMap[$norm];
            foreach ($variants as $variant) {
                $finalMap[$variant] = $best;
            }
        }

        return $finalMap;
    }

    protected function normalizeDpjp(string $name): string
    {
        $name = trim($name);

        // Joint-care ("rawat bersama") entries list multiple doctors separated by "/",
        // e.g. "dr. A, Sp.PD/dr. B, Sp.OG". This identifies a *combination* of doctors,
        // not a single person — it must never merge into, or be picked to represent,
        // a single doctor's group.
        if (str_contains($name, '/')) {
            $parts = array_map(
                fn($part) => $this->normalizeSingleDpjp($part),
                explode('/', $name)
            );
            return implode('/', $parts);
        }

        return $this->normalizeSingleDpjp($name);
    }

    protected function normalizeSingleDpjp(string $name): string
    {
        $name = trim($name);

        // Only the part before the first comma identifies the person —
        // everything after is specialization info (Sp.PD, Sp.OG(K), etc.)
        $name = explode(',', $name, 2)[0];

        // Remove common professional title prefixes
        $name = preg_replace('/\b(dr\.?|drg\.?|dokter)\b/i', '', $name);

        $name = str_replace('.', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return strtolower(trim($name));
    }

    protected function pickBestDpjp(array $variants): string
    {
        // Priority: longest name (most complete), preferring ones with titles/degrees
        // Score each variant
        $scored = array_map(function ($v) {
            $score = 0;
            $v = trim($v);
            $len = strlen($v);

            // Has title (dr., Sp., S.Pp, M.Kes, etc.)
            if (preg_match('/\b(dr\.?|sp\.?|s\.?p\.?p?|sp\.?p|m\.?k\.?e?s?|m\.?a?r?s?)\b/i', $v)) {
                $score += 100;
            }
            // Longer = more complete
            $score += $len;

            return ['name' => $v, 'score' => $score];
        }, $variants);

        // Sort by score descending
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return $scored[0]['name'];
    }

    protected function getNormalizedDpjp($rawDpjp): string
    {
        if (!$rawDpjp || trim($rawDpjp) === '') {
            return '-';
        }
        return $this->dpjpMap[trim($rawDpjp)] ?? trim($rawDpjp);
    }

    public function map($claim): array
    {
        return [
            $this->getNormalizedDpjp($claim->dpjp ?? '-'),
            $claim->admission_date ? \Carbon\Carbon::parse($claim->admission_date)->format('d/m/Y') : '-',
            $claim->discharge_date ? \Carbon\Carbon::parse($claim->discharge_date)->format('d/m/Y') : '-',
            $claim->kelas_rawat ?? '-',
            $claim->nama_pasien ?? '-',
            $claim->mrn ?? '-',
            $claim->sep ?? '-',
            $claim->total_tarif ?? 0,
            $claim->laboratorium ?? 0,
            $claim->radiologi ?? 0,
            0, // USG - column doesn't exist in DB, always 0
            $claim->pelayanan_darah ?? 0,
        ];
    }

    public function headings(): array
    {
        return [
            'DPJP',
            'Tanggal Masuk',
            'Tanggal Keluar',
            'Kelas Rawat',
            'Nama',
            'No.RM',
            'No SEP',
            'Total Billing',
            'Lab',
            'Radiologi',
            'USG',
            'UTD',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Format numeric columns as numbers (not text) - columns H through L (8-12)
        for ($row = 2; $row <= $highestRow; $row++) {
            for ($col = 'H'; $col <= 'L'; $col++) {
                $cell = $sheet->getCell($col . $row);
                if ($cell->getValue() !== '-' && $cell->getValue() !== '') {
                    $cell->setDataType(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                }
            }
        }

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,  // DPJP
            'B' => 18,  // Tanggal Masuk
            'C' => 18,  // Tanggal Keluar
            'D' => 15,  // Kelas Rawat
            'E' => 30,  // Nama
            'F' => 18,  // No.RM
            'G' => 20,  // No SEP
            'H' => 18,  // Total Billing
            'I' => 18,  // Lab
            'J' => 18,  // Radiologi
            'K' => 18,  // USG
            'L' => 18,  // UTD
        ];
    }
}