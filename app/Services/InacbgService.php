<?php

namespace App\Services;

use App\Models\InacbgClaim;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InacbgService
{
    /**
     * Column mapping from inacbg.xlsx header → database column
     */
    protected array $inacbgMapping = [
        'KELAS_RS'            => 'kelas_rs',
        'KELAS_RAWAT'         => 'kelas_rawat',
        'KODE_TARIF'          => 'kode_tarif',
        'ADMISSION_DATE'      => 'admission_date',
        'DISCHARGE_DATE'      => 'discharge_date',
        'BIRTH_DATE'          => 'birth_date',
        'SEX'                 => 'sex',
        'DISCHARGE_STATUS'    => 'discharge_status',
        'DIAGLIST'            => 'diaglist',
        'PROCLIST'            => 'proclist',
        'INACBG'              => 'inacbg',
        'SUBACUTE'            => 'subacute',
        'CHRONIC'             => 'chronic',
        'DESKRIPSI_INACBG'    => 'deskripsi_inacbg',
        'TARIF_INACBG'        => 'tarif_inacbg',
        'TARIF_SUBACUTE'      => 'tarif_subacute',
        'TARIF_CHRONIC'       => 'tarif_chronic',
        'TARIF_SP'            => 'tarif_sp',
        'TARIF_SR'            => 'tarif_sr',
        'TARIF_SI'            => 'tarif_si',
        'TARIF_SD'            => 'tarif_sd',
        'TOTAL_TARIF'         => 'total_tarif',
        'TARIF_RS'            => 'tarif_rs',
        'LOS'                 => 'los',
        'NAMA_PASIEN'         => 'nama_pasien',
        'MRN'                 => 'mrn',
        'UMUR_TAHUN'          => 'umur_tahun',
        'UMUR_HARI'           => 'umur_hari',
        'DPJP'                => 'dpjp',
        'SEP'                 => 'sep',
        'PAYOR_ID'            => 'payor_id',
        'VERSI_INACBG'        => 'versi_inacbg',
        'VERSI_GROUPER'       => 'versi_grouper',
        'PROSEDUR_NON_BEDAH'  => 'prosedur_non_bedah',
        'PROSEDUR_BEDAH'      => 'prosedur_bedah',
        'KONSULTASI'          => 'konsultasi',
        'TENAGA_AHLI'         => 'tenaga_ahli',
        'KEPERAWATAN'         => 'keperawatan',
        'PENUNJANG'           => 'penunjang',
        'RADIOLOGI'           => 'radiologi',
        'LABORATORIUM'        => 'laboratorium',
        'PELAYANAN_DARAH'     => 'pelayanan_darah',
        'REHABILITASI'        => 'rehabilitasi',
        'KAMAR_AKOMODASI'     => 'kamar_akomodasi',
        'RAWAT_INTENSIF'      => 'rawat_intensif',
        'OBAT'                => 'obat',
        'ALKES'               => 'alkes',
        'BMHP'                => 'bmhp',
        'SEWA_ALAT'           => 'sewa_alat',
        'OBAT_KRONIS'         => 'obat_kronis',
        'OBAT_KEMO'           => 'obat_kemo',
        'IDRG_MDC_NUMBER'     => 'idrg_mdc_number',
        'IDRG_MDC_DESCRIPTION' => 'idrg_mdc_description',
        'IDRG_DRG_CODE'       => 'idrg_drg_code',
        'IDRG_DRG_DESCRIPTION' => 'idrg_drg_description',
        'IDRG_COST_WEIGHT'    => 'idrg_cost_weight',
        'IDRG_TOTAL_COST_WEIGHT' => 'idrg_total_cost_weight',
        'IDRG_TOTAL_TARIF'    => 'idrg_total_tarif',
    ];

    /**
     * Import both files and merge by SEP.
     *
     * @return array{imported: int, updated: int, errors: array}
     */
    public function import(string $inacbgPath, string $feedbackPath): array
    {
        $inacbgRows = $this->parseInacbg($inacbgPath);
        $feedbackRows = $this->parseFeedback($feedbackPath);

        if (empty($inacbgRows)) {
            return ['imported' => 0, 'updated' => 0, 'errors' => ['File INA-CBG tidak mengandung data atau format salah.']];
        }

        // Index feedback by SEP for quick lookup
        $feedbackBySep = [];
        foreach ($feedbackRows as $fb) {
            $sep = trim($fb['sep'] ?? '');
            if ($sep !== '') {
                $feedbackBySep[$sep] = $fb;
            }
        }

        $imported = 0;
        $updated = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($inacbgRows as $row) {
                $sep = trim($row['sep'] ?? '');

                // Determine status and verification data
                if ($sep !== '' && isset($feedbackBySep[$sep])) {
                    $fb = $feedbackBySep[$sep];
                    $row['status'] = 'disetujui';
                    $row['tgl_verifikasi'] = $fb['tgl_verifikasi'] ?? null;
                    $row['biaya_riil_rs'] = $fb['biaya_riil_rs'] ?? 0;
                    $row['biaya_diajukan'] = $fb['biaya_diajukan'] ?? 0;
                    $row['biaya_disetujui'] = $fb['biaya_disetujui'] ?? 0;
                    $row['jenis_rawat'] = $fb['jenis_rawat'] ?? 'unknown';
                } else {
                    $row['status'] = 'pending';
                    // Infer jenis_rawat from kelas_rawat if available
                    if (empty($row['jenis_rawat'])) {
                        $row['jenis_rawat'] = 'unknown';
                    }
                }

                // Check if SEP already exists — update instead of insert
                $existing = $sep !== ''
                    ? InacbgClaim::where('sep', $sep)->first()
                    : null;

                if ($existing) {
                    $existing->update($row);
                    $updated++;
                } else {
                    InacbgClaim::create($row);
                    $imported++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Inacbg import failed: ' . $e->getMessage());
            $errors[] = 'Terjadi kesalahan saat import: ' . $e->getMessage();
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Parse inacbg.xlsx and map columns.
     */
    protected function parseInacbg(string $path): array
    {
        /** @var \PhpOffice\PhpSpreadsheet\Reader\Xlsx $reader */
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        // Read only the header row to get mapping
        $headerRow = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1', null, true, false)[0] ?? [];
        $headers = array_map(fn($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $h ?? '')), $headerRow);

        $results = [];

        // Iterate row by row using iterator (memory efficient)
        $rowIterator = $sheet->getRowIterator(2);
        foreach ($rowIterator as $rowObj) {
            $cellIterator = $rowObj->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $row = [];
            foreach ($cellIterator as $cell) {
                $row[] = $cell->getValue();
            }

            $mapped = [];

            foreach ($headers as $colIdx => $header) {
                $dbCol = $this->inacbgMapping[$header] ?? null;
                if ($dbCol === null) {
                    continue;
                }

                $value = $row[$colIdx] ?? null;

                // Handle Excel date serial numbers
                if (in_array($dbCol, ['birth_date', 'admission_date', 'discharge_date', 'tgl_verifikasi'])) {
                    $value = $this->excelToDate($value);
                }

                // Numeric fields
                if (in_array($dbCol, ['los', 'umur_tahun', 'umur_hari'])) {
                    $value = $value !== null && $value !== '' ? (int) $value : null;
                }

                if (str_contains($dbCol, 'tarif') || str_contains($dbCol, 'biaya') || in_array($dbCol, [
                    'prosedur_non_bedah', 'prosedur_bedah', 'konsultasi', 'tenaga_ahli',
                    'keperawatan', 'penunjang', 'radiologi', 'laboratorium', 'pelayanan_darah',
                    'rehabilitasi', 'kamar_akomodasi', 'rawat_intensif', 'obat', 'alkes',
                    'bmhp', 'sewa_alat', 'obat_kronis', 'obat_kemo',
                    'idrg_cost_weight', 'idrg_total_cost_weight',
                ])) {
                    $value = $value !== null && $value !== '' ? (float) $value : 0;
                }

                $mapped[$dbCol] = $value;
            }

            // Skip entirely empty rows
            if (empty($mapped['mrn']) && empty($mapped['sep']) && empty($mapped['nama_pasien'])) {
                continue;
            }

            $results[] = $mapped;

            // Free memory every 500 rows
            if (count($results) % 500 === 0) {
                gc_collect_cycles();
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet, $reader);

        return $results;
    }

    /**
     * Parse feedback.xlsx — reads both RI (ranap) and RJ (ralan) worksheets.
     * Columns: No, No.SEP, Tgl. Verifikasi, Biaya Riil RS, Biaya Diajukan, Biaya Disetujui
     */
    protected function parseFeedback(string $path): array
    {
        if ($path === '' || !file_exists($path)) {
            return [];
        }

        $spreadsheet = IOFactory::load($path);
        $results = [];

        // Worksheet name → jenis_rawat mapping
        $sheetMap = [
            'RI' => 'ranap',
            'RJ' => 'ralan',
        ];

        foreach ($sheetMap as $sheetName => $jenisRawat) {
            try {
                $worksheet = $spreadsheet->getSheetByName($sheetName);
                if ($worksheet === null) {
                    continue;
                }
            } catch (\Throwable) {
                continue;
            }

            $highestRow = $worksheet->getHighestRow();
            if ($highestRow < 2) {
                continue;
            }

            // Read header row
            $headerRow = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . '1', null, true, false)[0] ?? [];

            // Find column positions (case-insensitive)
            $colIdx = [
                'sep' => null,
                'tgl_verifikasi' => null,
                'biaya_riil_rs' => null,
                'biaya_diajukan' => null,
                'biaya_disetujui' => null,
            ];

            foreach ($headerRow as $i => $h) {
                $h = strtolower(trim((string) $h));
                if (in_array($h, ['no.sep', 'no.sép', 'nosep'])) $colIdx['sep'] = $i;
                elseif (str_contains($h, 'tgl') || str_contains($h, 'tanggal')) $colIdx['tgl_verifikasi'] = $i;
                elseif (str_contains($h, 'riil')) $colIdx['biaya_riil_rs'] = $i;
                elseif (str_contains($h, 'diajukan')) $colIdx['biaya_diajukan'] = $i;
                elseif (str_contains($h, 'disetujui')) $colIdx['biaya_disetujui'] = $i;
            }

            // Fallback by position if header matching failed
            if ($colIdx['sep'] === null) $colIdx['sep'] = 1;
            if ($colIdx['tgl_verifikasi'] === null) $colIdx['tgl_verifikasi'] = 2;
            if ($colIdx['biaya_riil_rs'] === null) $colIdx['biaya_riil_rs'] = 3;
            if ($colIdx['biaya_diajukan'] === null) $colIdx['biaya_diajukan'] = 4;
            if ($colIdx['biaya_disetujui'] === null) $colIdx['biaya_disetujui'] = 5;

            // Iterate rows (skip header)
            for ($r = 2; $r <= $highestRow; $r++) {
                $rowData = $worksheet->rangeToArray('A' . $r . ':' . $worksheet->getHighestColumn() . $r, null, true, false)[0] ?? [];
                $sep = trim((string) ($rowData[$colIdx['sep']] ?? ''));

                if ($sep === '' || $sep === 'No.SEP') {
                    continue;
                }

                $results[] = [
                    'sep' => $sep,
                    'jenis_rawat' => $jenisRawat,
                    'tgl_verifikasi' => $this->excelToDate($rowData[$colIdx['tgl_verifikasi']] ?? null),
                    'biaya_riil_rs' => $this->parseIdNumber($rowData[$colIdx['biaya_riil_rs']] ?? 0),
                    'biaya_diajukan' => $this->parseIdNumber($rowData[$colIdx['biaya_diajukan']] ?? 0),
                    'biaya_disetujui' => $this->parseIdNumber($rowData[$colIdx['biaya_disetujui']] ?? 0),
                ];
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $results;
    }

    /**
     * Convert Excel serial date or string to Y-m-d format.
     */
    protected function excelToDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // If it's a numeric Excel serial date
        if (is_numeric($value)) {
            try {
                $timestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value);
                return date('Y-m-d', $timestamp);
            } catch (\Throwable) {
                return null;
            }
        }

        // If it's already a string date
        $value = trim((string) $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return substr($value, 0, 10);
        }

        // Try common formats
        $formats = ['d/m/Y', 'd-m-Y', 'm/d/Y', 'Y-m-d'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $value);
            if ($dt) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Parse Indonesian format number.
     * Examples: "198,000" → 198000, "2,074,125" → 2074125, "2.074.125,50" → 2074125.5
     */
    protected function parseIdNumber(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }
        // Already numeric (Excel stores as number)
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = trim((string) $value);
        if ($clean === '' || $clean === '-') {
            return 0;
        }

        // Determine format:
        // Jika ada koma (,) dan setelah koma ada 2 digit dan TIDAK ada lagi grup ribuan setelahnya,
        // maka koma adalah desimal (format Eropa/Indonesia: 2.074.125,50)
        // Jika hanya koma tanpa titik dan digit setelah koma = 3 (000), maka itu thousand sep
        // Aturan sederhana:
        //   - Jika format "x,xxx" (koma setelah 1 digit) → thousand separator, buang koma
        //   - Jika format "xx,xxx" atau "xxx,xxx" → thousand separator
        //   - Jika format "... ,xx" (koma diikuti 1-2 digit, dan sebelumnya ada titik atau di akhir) → decimal
        //   - Jika ada titik sebagai thousand separator dan koma sebagai desimal → format "1.500.000,50"

        $hasDot = str_contains($clean, '.');
        $hasComma = str_contains($clean, ',');

        if ($hasDot && $hasComma) {
            // Format: "1.500.000,50" — titik = ribuan, koma = desimal
            // Remove all dots, replace comma with dot
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif ($hasComma && !$hasDot) {
            // Hanya ada koma — tentukan apakah thousand separator atau desimal
            // Cari posisi koma terakhir
            $lastCommaPos = strrpos($clean, ',');
            $afterLastComma = substr($clean, $lastCommaPos + 1);

            if (strlen($afterLastComma) <= 2 && ctype_digit($afterLastComma)) {
                // "...,50" atau "...,5" → koma adalah desimal
                // Tapi "198,000" punya afterLastComma = "000" (3 digit) → thousand separator
                if (strlen($afterLastComma) == 2) {
                    // "...,50" type — kemungkinan desimal
                    // Kecuali "12,345" (after 3 digit before). Cek komponen sebelumnya
                    $beforeLastComma = substr($clean, 0, $lastCommaPos);
                    if (!str_contains($beforeLastComma, ',')) {
                        // Hanya 1 koma. "12,50" = desimal. "12,345" = 3 digit after → thousand sep
                        // Kalau 2 digit after comma → desimal, kalau 3 → thousand sep
                        // Kecuali "1,50" yang jelas desimal
                        if (strlen($afterLastComma) == 2) {
                            $clean = str_replace(',', '.', $clean);
                        } else {
                            $clean = str_replace(',', '', $clean);
                        }
                    } else {
                        // Multiple commas, last one with 2 digits → desimal
                        $clean = str_replace(',', '', $clean);
                        // Wait, we need to replace the last comma with dot
                        // This is complex. Better: replace all commas except last one
                        $parts = explode(',', $clean);
                        $last = array_pop($parts);
                        $clean = implode('', $parts) . '.' . $last;
                    }
                } else {
                    // 1 digit after comma — decimal for sure
                    $clean = str_replace(',', '.', $clean);
                }
            } else {
                // 3+ digits after comma (e.g. "000") → thousand separator
                $clean = str_replace(',', '', $clean);
            }
        }
        // If only dots, keep as-is (1.5 = 1.5)

        // Clean any remaining non-numeric except dot and minus
        $clean = preg_replace('/[^0-9\.\-]/', '', $clean);
        // Remove empty dot-only
        if ($clean === '' || $clean === '.') {
            return 0;
        }

        return (float) $clean;
    }
}
