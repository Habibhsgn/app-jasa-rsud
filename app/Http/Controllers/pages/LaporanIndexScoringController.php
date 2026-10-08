<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IndexScoring;
use App\Models\Pegawai;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanIndexScoringController extends Controller
{
    /**
     * Role yang boleh melihat SEMUA ruangan.
     *
     * Selain role ini, hanya boleh melihat
     * ruangan miliknya sendiri.
     */
    protected array $fullAccessRoles = [
        'admin',
        'manajemen',
    ];

    /**
     * ============================================================
     * INDEX
     * ============================================================
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $isFullAccess = in_array(
            $user->role?->code,
            $this->fullAccessRoles,
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Daftar periode
        |--------------------------------------------------------------------------
        */

        $periodeQuery = IndexScoring::query()
            ->where('status_pengajuan', 'selesai')
            ->select('periode_pengajuan')
            ->distinct()
            ->orderByDesc('periode_pengajuan');

        /*
        |--------------------------------------------------------------------------
        | RBAC
        |--------------------------------------------------------------------------
        */

        if (!$isFullAccess) {
            $periodeQuery->where(
                'ruangan_id',
                $user->ruangan_id
            );
        }

        $periodeList = $periodeQuery
            ->pluck('periode_pengajuan')
            ->map(
                fn($periode) =>
                Carbon::parse($periode)->format('Y-m')
            )
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Data laporan
        |--------------------------------------------------------------------------
        */

        $query = IndexScoring::with([
            'pegawai',
            'ruangan',
        ])
            ->where(
                'status_pengajuan',
                'selesai'
            );

        /*
        |--------------------------------------------------------------------------
        | RBAC
        |--------------------------------------------------------------------------
        */

        if (!$isFullAccess) {
            $query->where(
                'ruangan_id',
                $user->ruangan_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter periode
        |--------------------------------------------------------------------------
        */

        if ($request->filled('periode')) {
            $query->whereRaw(
                "DATE_FORMAT(periode_pengajuan, '%Y-%m') = ?",
                [$request->periode]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil data
        |--------------------------------------------------------------------------
        */

        $rows = $query
            ->orderBy('periode_pengajuan')
            ->orderBy('ruangan_id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Group berdasarkan periode + ruangan
        |--------------------------------------------------------------------------
        */

        $data = $rows
            ->groupBy(
                fn($row) =>
                Carbon::parse(
                    $row->periode_pengajuan
                )->format('Y-m')
                    . '_'
                    . $row->ruangan_id
            )
            ->map(function ($group) {

                $first = $group->first();

                return (object) [

                    'periode' => Carbon::parse(
                        $first->periode_pengajuan
                    )->format('Y-m'),

                    'periode_label' => Carbon::parse(
                        $first->periode_pengajuan
                    )->translatedFormat('F Y'),

                    'ruangan' => $first->ruangan,

                    'pegawai' => $group
                        ->map(
                            fn($row) => $row
                        )
                        ->values(),

                    'jumlah_pegawai' => $group->count(),

                    'total_jumlah' => $group->sum(
                        fn($row) =>
                        (float) ($row->jumlah ?? 0)
                    ),

                    'total_jumlah_akhir' => $group->sum(
                        fn($row) =>
                        (float) ($row->jumlah_akhir ?? 0)
                    ),
                ];
            })
            ->values();

        return view(
            'pages.laporanIndexScoring',
            compact(
                'data',
                'periodeList'
            )
        );
    }

    /**
     * ============================================================
     * EXPORT EXCEL
     * ============================================================
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Cek akses penuh
        |--------------------------------------------------------------------------
        */

        $isFullAccess = in_array(
            $user->role?->code,
            $this->fullAccessRoles,
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Query data
        |--------------------------------------------------------------------------
        */

        $query = IndexScoring::with([
            'pegawai',
            'ruangan',
        ])
            ->where(
                'status_pengajuan',
                'selesai'
            );

        /*
        |--------------------------------------------------------------------------
        | RBAC
        |--------------------------------------------------------------------------
        |
        | Admin + Manajemen:
        | Semua ruangan.
        |
        | Role lainnya:
        | Hanya ruangan miliknya sendiri.
        |
        */

        if (!$isFullAccess) {
            $query->where(
                'ruangan_id',
                $user->ruangan_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter periode
        |--------------------------------------------------------------------------
        */

        if ($request->filled('periode')) {
            $query->whereRaw(
                "DATE_FORMAT(periode_pengajuan, '%Y-%m') = ?",
                [$request->periode]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil data
        |--------------------------------------------------------------------------
        */

        $rows = $query
            ->orderBy('periode_pengajuan')
            ->orderBy('ruangan_id')
            ->orderBy('pegawai_id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Tidak ada data
        |--------------------------------------------------------------------------
        */

        if ($rows->isEmpty()) {
            return back()->with(
                'error',
                'Tidak ada data laporan yang dapat diekspor.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Spreadsheet
        |--------------------------------------------------------------------------
        */

        $spreadsheet = new Spreadsheet();

        /*
        |--------------------------------------------------------------------------
        | Header tabel
        |--------------------------------------------------------------------------
        |
        | Jabatan Ruangan = tabel pegawai (field jabatan)
        | Jabatan         = tabel index scoring
        |
        */

        $headers = [
            'No',
            'Nama',
            'Jabatan Ruangan',
            'NIP/NIP3K',
            'Jabatan',
            'Pend. Formal',
            'Pend. Non Formal',
            'Gaji Pokok',
            'Risk',
            'Emergency',
            'Cuti',
            'Izin',
            'Tanpa Izin',
            'Telat',
            'Sikap',
            'Jumlah',
            'Keterangan',
            'Jumlah Akhir',
        ];

        /*
        |--------------------------------------------------------------------------
        | Helper membuat row
        |--------------------------------------------------------------------------
        */

        $buildRow = function ($row, int $number) {
            return [
                $number,
                $row->pegawai?->nama ?? '-',
                $row->pegawai?->jabatan ?? '-',
                $row->pegawai?->id_petugas !== null
                    ? (string) $row->pegawai->id_petugas
                    : '-',
                $row->jabatan ?? '-',
                $row->pendidikan_formal ?? '-',
                $row->pendidikan_non_formal ?? 0,
                (float) (
                    $row->pegawai?->gaji_pokok ?? 0
                ),
                $row->risk ?? 0,
                $row->emergency ?? 0,
                $row->cuti ?? 0,
                $row->izin ?? 0,
                $row->tanpa_izin ?? 0,
                $row->telat ?? 0,
                $row->sikap ?? 0,
                (float) (
                    $row->jumlah ?? 0
                ),
                $row->keterangan ?: '-',
                (float) (
                    $row->jumlah_akhir ?? 0
                ),
            ];
        };

        /*
        |--------------------------------------------------------------------------
        | Helper mencari Nama Ka. Ruang
        |--------------------------------------------------------------------------
        |
        | Dari tabel pegawai:
        |
        |   ruangan_id = ruangan yang sedang diproses
        |   jabatan mengandung "Karu"
        |
        */

        $getNamaKaru = function ($ruanganId) {

            return Pegawai::query()
                ->where(
                    'ruangan_id',
                    $ruanganId
                )
                ->where(
                    'jabatan',
                    'LIKE',
                    '%Karu%'
                )
                ->value('nama') ?? '-';
        };

        /*
        |--------------------------------------------------------------------------
        | Helper menulis data ke Excel
        |--------------------------------------------------------------------------
        |
        | Kolom D = NIP/NIP3K.
        | Dipaksa menjadi TYPE_STRING.
        |
        */

        $writeDataRow = function (
            $sheet,
            int $excelRow,
            array $dataRow
        ) {

            foreach (
                $dataRow as $columnIndex => $value
            ) {

                $column = $columnIndex + 1;

                /*
                |--------------------------------------------------------------------------
                | Kolom D = NIP/NIP3K
                |--------------------------------------------------------------------------
                */

                if ($column === 4) {

                    $sheet->setCellValueExplicit(
                        "D{$excelRow}",
                        (string) $value,
                        DataType::TYPE_STRING
                    );
                } else {

                    $sheet->setCellValueByColumnAndRow(
                        $column,
                        $excelRow,
                        $value
                    );
                }
            }
        };

        /*
        |--------------------------------------------------------------------------
        | Helper header sheet
        |--------------------------------------------------------------------------
        |
        | Metadata dibuat benar-benar SATU CELL per baris:
        |
        | A3:R3 = Nama Ruang : ...
        | A4:R4 = Nama Ka. Ruang : ...
        | A5:R5 = Bulan/Periode : ...
        |
        */

        $writeSheetHeader = function (
            $sheet,
            ?string $namaRuangan,
            ?string $namaKaru,
            ?string $periode
        ) use ($headers) {

            /*
            |--------------------------------------------------------------------------
            | Judul
            |--------------------------------------------------------------------------
            */

            $sheet->mergeCells(
                'A1:R1'
            );

            $sheet->setCellValue(
                'A1',
                'LAPORAN HASIL INDEX SCORING'
            );

            $sheet->getStyle(
                'A1:R1'
            )->applyFromArray([

                'font' => [
                    'bold' => true,
                    'size' => 14,
                ],

                'alignment' => [
                    'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,

                    'vertical' =>
                    Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getRowDimension(1)
                ->setRowHeight(25);

            /*
            |--------------------------------------------------------------------------
            | Nama Ruang
            |--------------------------------------------------------------------------
            */

            $sheet->mergeCells(
                'A3:R3'
            );

            $sheet->setCellValue(
                'A3',
                'Nama Ruang      : '
                    . ($namaRuangan ?? '-')
            );

            /*
            |--------------------------------------------------------------------------
            | Nama Ka. Ruang
            |--------------------------------------------------------------------------
            */

            $sheet->mergeCells(
                'A4:R4'
            );

            $sheet->setCellValue(
                'A4',
                'Nama Ka. Ruang  : '
                    . ($namaKaru ?? '-')
            );

            /*
            |--------------------------------------------------------------------------
            | Bulan / Periode
            |--------------------------------------------------------------------------
            */

            $sheet->mergeCells(
                'A5:R5'
            );

            $periodeLabel = 'Semua Periode';

            if ($periode) {

                try {

                    $periodeLabel =
                        Carbon::createFromFormat(
                            'Y-m',
                            $periode
                        )->translatedFormat(
                            'F Y'
                        );
                } catch (\Throwable $e) {

                    $periodeLabel =
                        $periode;
                }
            }

            $sheet->setCellValue(
                'A5',
                'Bulan/Periode   : '
                    . $periodeLabel
            );

            /*
            |--------------------------------------------------------------------------
            | Style informasi
            |--------------------------------------------------------------------------
            */

            $sheet->getStyle(
                'A3:R5'
            )->applyFromArray([

                'alignment' => [
                    'horizontal' =>
                    Alignment::HORIZONTAL_LEFT,

                    'vertical' =>
                    Alignment::VERTICAL_CENTER,
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Bold hanya label menggunakan Rich Text
            |--------------------------------------------------------------------------
            */

            $namaRuangRichText =
                new \PhpOffice\PhpSpreadsheet\RichText\RichText();

            $boldText =
                $namaRuangRichText->createTextRun(
                    'Nama Ruang      : '
                );

            $boldText->getFont()
                ->setBold(true);

            $namaRuangRichText->createText(
                $namaRuangan ?? '-'
            );

            $sheet->setCellValue(
                'A3',
                $namaRuangRichText
            );

            /*
            |--------------------------------------------------------------------------
            | Nama Ka. Ruang Rich Text
            |--------------------------------------------------------------------------
            */

            $namaKaruRichText =
                new \PhpOffice\PhpSpreadsheet\RichText\RichText();

            $boldText =
                $namaKaruRichText->createTextRun(
                    'Nama Ka. Ruang  : '
                );

            $boldText->getFont()
                ->setBold(true);

            $namaKaruRichText->createText(
                $namaKaru ?? '-'
            );

            $sheet->setCellValue(
                'A4',
                $namaKaruRichText
            );

            /*
            |--------------------------------------------------------------------------
            | Periode Rich Text
            |--------------------------------------------------------------------------
            */

            $periodeRichText =
                new \PhpOffice\PhpSpreadsheet\RichText\RichText();

            $boldText =
                $periodeRichText->createTextRun(
                    'Bulan/Periode   : '
                );

            $boldText->getFont()
                ->setBold(true);

            $periodeRichText->createText(
                $periodeLabel
            );

            $sheet->setCellValue(
                'A5',
                $periodeRichText
            );

            /*
            |--------------------------------------------------------------------------
            | Tinggi baris
            |--------------------------------------------------------------------------
            */

            $sheet->getRowDimension(3)
                ->setRowHeight(22);

            $sheet->getRowDimension(4)
                ->setRowHeight(22);

            $sheet->getRowDimension(5)
                ->setRowHeight(22);

            /*
            |--------------------------------------------------------------------------
            | Header tabel
            |--------------------------------------------------------------------------
            */

            $headerRow = 7;

            foreach (
                $headers as $columnIndex => $header
            ) {

                $sheet->setCellValueByColumnAndRow(
                    $columnIndex + 1,
                    $headerRow,
                    $header
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Style header tabel
            |--------------------------------------------------------------------------
            */

            $sheet->getStyle(
                "A{$headerRow}:R{$headerRow}"
            )->applyFromArray([

                'font' => [
                    'bold' => true,

                    'color' => [
                        'rgb' => 'FFFFFF',
                    ],
                ],

                'fill' => [

                    'fillType' =>
                    Fill::FILL_SOLID,

                    'startColor' => [
                        'rgb' => '198754',
                    ],
                ],

                'alignment' => [

                    'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,

                    'vertical' =>
                    Alignment::VERTICAL_CENTER,

                    'wrapText' => true,
                ],

                'borders' => [

                    'allBorders' => [

                        'borderStyle' =>
                        Border::BORDER_THIN,
                    ],
                ],
            ]);

            $sheet->getRowDimension(
                $headerRow
            )->setRowHeight(30);

            return $headerRow;
        };

        /*
        |--------------------------------------------------------------------------
        | Helper styling data
        |--------------------------------------------------------------------------
        */

        $styleData = function (
            $sheet,
            int $startRow,
            int $lastRow
        ) {

            if ($lastRow < $startRow) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Border
            |--------------------------------------------------------------------------
            */

            $sheet->getStyle(
                "A{$startRow}:R{$lastRow}"
            )->applyFromArray([

                'borders' => [

                    'allBorders' => [

                        'borderStyle' =>
                        Border::BORDER_THIN,
                    ],
                ],

                'alignment' => [

                    'vertical' =>
                    Alignment::VERTICAL_CENTER,
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Center columns
            |--------------------------------------------------------------------------
            */

            $centerColumns = [
                'A', // No
                'D', // NIP/NIP3K
                'F', // Pend. Formal
                'G', // Pend. Non Formal
                'I', // Risk
                'J', // Emergency
                'K', // Cuti
                'L', // Izin
                'M', // Tanpa Izin
                'N', // Telat
                'O', // Sikap
            ];

            foreach (
                $centerColumns as $column
            ) {

                $sheet->getStyle(
                    "{$column}{$startRow}:{$column}{$lastRow}"
                )->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Gaji Pokok
            |--------------------------------------------------------------------------
            */

            $sheet->getStyle(
                "H{$startRow}:H{$lastRow}"
            )->getNumberFormat()
                ->setFormatCode(
                    '#,##0'
                );

            /*
            |--------------------------------------------------------------------------
            | Jumlah
            |--------------------------------------------------------------------------
            */

            $sheet->getStyle(
                "P{$startRow}:P{$lastRow}"
            )->getNumberFormat()
                ->setFormatCode(
                    '#,##0.00'
                );

            /*
            |--------------------------------------------------------------------------
            | Jumlah Akhir
            |--------------------------------------------------------------------------
            */

            $sheet->getStyle(
                "R{$startRow}:R{$lastRow}"
            )->getNumberFormat()
                ->setFormatCode(
                    '#,##0.00'
                );

            /*
            |--------------------------------------------------------------------------
            | NIP/NIP3K
            |--------------------------------------------------------------------------
            */

            $sheet->getStyle(
                "D{$startRow}:D{$lastRow}"
            )->getNumberFormat()
                ->setFormatCode('@');
        };

        /*
        |--------------------------------------------------------------------------
        | Helper lebar kolom
        |--------------------------------------------------------------------------
        */

        $setColumnWidths = function ($sheet) {

            $widths = [

                'A' => 7,   // No
                'B' => 30,  // Nama
                'C' => 25,  // Jabatan Ruangan
                'D' => 22,  // NIP/NIP3K
                'E' => 20,  // Jabatan
                'F' => 15,  // Pend. Formal
                'G' => 18,  // Pend. Non Formal
                'H' => 18,  // Gaji Pokok
                'I' => 10,  // Risk
                'J' => 12,  // Emergency
                'K' => 10,  // Cuti
                'L' => 10,  // Izin
                'M' => 14,  // Tanpa Izin
                'N' => 10,  // Telat
                'O' => 10,  // Sikap
                'P' => 15,  // Jumlah
                'Q' => 35,  // Keterangan
                'R' => 18,  // Jumlah Akhir
            ];

            foreach (
                $widths as $column => $width
            ) {

                $sheet
                    ->getColumnDimension($column)
                    ->setWidth($width);
            }
        };

        /*
        |--------------------------------------------------------------------------
        | ==============================================================
        | SHEET 1 - SEMUA RUANGAN
        | ==============================================================
        |--------------------------------------------------------------------------
        */

        $sheet =
            $spreadsheet->getActiveSheet();

        $sheet->setTitle(
            'Semua Ruangan'
        );

        /*
        |--------------------------------------------------------------------------
        | Header semua ruangan
        |--------------------------------------------------------------------------
        */

        $periodeHeader = null;

        if ($request->filled('periode')) {
            $periodeHeader =
                $request->periode;
        }

        $headerRow =
            $writeSheetHeader(
                $sheet,

                'Semua Ruangan',

                '-',

                $periodeHeader
            );

        /*
        |--------------------------------------------------------------------------
        | Data semua ruangan
        |--------------------------------------------------------------------------
        */

        foreach (
            $rows->values()
            as $index => $row
        ) {

            $excelRow =
                $index
                + $headerRow
                + 1;

            $dataRow =
                $buildRow(
                    $row,
                    $index + 1
                );

            $writeDataRow(
                $sheet,
                $excelRow,
                $dataRow
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Total semua ruangan
        |--------------------------------------------------------------------------
        */

        $totalRow =
            $rows->count()
            + $headerRow
            + 1;

        $sheet->setCellValue(
            "A{$totalRow}",
            'TOTAL'
        );

        $sheet->mergeCells(
            "A{$totalRow}:O{$totalRow}"
        );

        $sheet->setCellValue(
            "P{$totalRow}",
            $rows->sum(
                fn($row) =>
                (float) (
                    $row->jumlah ?? 0
                )
            )
        );

        $sheet->setCellValue(
            "Q{$totalRow}",
            ''
        );

        $sheet->setCellValue(
            "R{$totalRow}",
            $rows->sum(
                fn($row) =>
                (float) (
                    $row->jumlah_akhir ?? 0
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Style data
        |--------------------------------------------------------------------------
        */

        $lastDataRow =
            $totalRow - 1;

        $styleData(
            $sheet,

            $headerRow + 1,

            $lastDataRow
        );

        /*
        |--------------------------------------------------------------------------
        | Style total
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle(
            "A{$totalRow}:R{$totalRow}"
        )->applyFromArray([

            'font' => [
                'bold' => true,
            ],

            'fill' => [

                'fillType' =>
                Fill::FILL_SOLID,

                'startColor' => [
                    'rgb' => 'E9ECEF',
                ],
            ],

            'borders' => [

                'allBorders' => [

                    'borderStyle' =>
                    Border::BORDER_THIN,
                ],
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Format total
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle(
            "P{$totalRow}"
        )->getNumberFormat()
            ->setFormatCode(
                '#,##0.00'
            );

        $sheet->getStyle(
            "R{$totalRow}"
        )->getNumberFormat()
            ->setFormatCode(
                '#,##0.00'
            );

        /*
        |--------------------------------------------------------------------------
        | Lebar kolom
        |--------------------------------------------------------------------------
        */

        $setColumnWidths(
            $sheet
        );

        /*
        |--------------------------------------------------------------------------
        | Freeze
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane(
            'A8'
        );

        /*
        |--------------------------------------------------------------------------
        | ==============================================================
        | SHEET PER RUANGAN
        | ==============================================================
        |--------------------------------------------------------------------------
        */

        $groupedByRoom =
            $rows->groupBy(
                'ruangan_id'
            );

        foreach (
            $groupedByRoom
            as $ruanganId => $roomRows
        ) {

            /*
            |--------------------------------------------------------------------------
            | Data pertama
            |--------------------------------------------------------------------------
            */

            $firstRow =
                $roomRows->first();

            $ruangan =
                $firstRow?->ruangan;

            /*
            |--------------------------------------------------------------------------
            | Nama ruangan
            |--------------------------------------------------------------------------
            */

            $namaRuangan =
                $ruangan?->nama_ruangan
                ?? "Ruangan {$ruanganId}";

            /*
            |--------------------------------------------------------------------------
            | Nama Ka. Ruang
            |--------------------------------------------------------------------------
            */

            $namaKaru =
                $getNamaKaru(
                    $ruanganId
                );

            /*
            |--------------------------------------------------------------------------
            | Nama sheet
            |--------------------------------------------------------------------------
            */

            $sheetName =
                preg_replace(
                    '/[\\\\\/\?\*\[\]\:]/',
                    '',
                    $namaRuangan
                );

            $sheetName =
                trim(
                    $sheetName
                );

            if (
                $sheetName === ''
            ) {

                $sheetName =
                    "Ruangan {$ruanganId}";
            }

            /*
            |--------------------------------------------------------------------------
            | Maksimal 31 karakter
            |--------------------------------------------------------------------------
            */

            $sheetName =
                mb_substr(
                    $sheetName,
                    0,
                    31
                );

            /*
            |--------------------------------------------------------------------------
            | Pastikan unik
            |--------------------------------------------------------------------------
            */

            $baseSheetName =
                $sheetName;

            $counter = 1;

            while (
                $spreadsheet->sheetNameExists(
                    $sheetName
                )
            ) {

                $suffix =
                    " ({$counter})";

                $sheetName =
                    mb_substr(
                        $baseSheetName,
                        0,
                        31 - mb_strlen(
                            $suffix
                        )
                    )
                    . $suffix;

                $counter++;
            }

            /*
            |--------------------------------------------------------------------------
            | Buat sheet
            |--------------------------------------------------------------------------
            */

            $roomSheet =
                $spreadsheet->createSheet();

            $roomSheet->setTitle(
                $sheetName
            );

            /*
            |--------------------------------------------------------------------------
            | Header
            |--------------------------------------------------------------------------
            */

            $roomHeaderRow =
                $writeSheetHeader(
                    $roomSheet,

                    $namaRuangan,

                    $namaKaru,

                    $request->periode
                );

            /*
            |--------------------------------------------------------------------------
            | Data ruangan
            |--------------------------------------------------------------------------
            */

            foreach (
                $roomRows->values()
                as $index => $row
            ) {

                $excelRow =
                    $index
                    + $roomHeaderRow
                    + 1;

                $dataRow =
                    $buildRow(
                        $row,
                        $index + 1
                    );

                $writeDataRow(
                    $roomSheet,
                    $excelRow,
                    $dataRow
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Total ruangan
            |--------------------------------------------------------------------------
            */

            $roomTotalRow =
                $roomRows->count()
                + $roomHeaderRow
                + 1;

            $roomSheet->setCellValue(
                "A{$roomTotalRow}",
                'TOTAL'
            );

            $roomSheet->mergeCells(
                "A{$roomTotalRow}:O{$roomTotalRow}"
            );

            $roomSheet->setCellValue(
                "P{$roomTotalRow}",
                $roomRows->sum(
                    fn($row) =>
                    (float) (
                        $row->jumlah ?? 0
                    )
                )
            );

            $roomSheet->setCellValue(
                "Q{$roomTotalRow}",
                ''
            );

            $roomSheet->setCellValue(
                "R{$roomTotalRow}",
                $roomRows->sum(
                    fn($row) =>
                    (float) (
                        $row->jumlah_akhir ?? 0
                    )
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Style data
            |--------------------------------------------------------------------------
            */

            $roomLastDataRow =
                $roomTotalRow - 1;

            $styleData(
                $roomSheet,

                $roomHeaderRow + 1,

                $roomLastDataRow
            );

            /*
            |--------------------------------------------------------------------------
            | Style total
            |--------------------------------------------------------------------------
            */

            $roomSheet->getStyle(
                "A{$roomTotalRow}:R{$roomTotalRow}"
            )->applyFromArray([

                'font' => [
                    'bold' => true,
                ],

                'fill' => [

                    'fillType' =>
                    Fill::FILL_SOLID,

                    'startColor' => [
                        'rgb' => 'E9ECEF',
                    ],
                ],

                'borders' => [

                    'allBorders' => [

                        'borderStyle' =>
                        Border::BORDER_THIN,
                    ],
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Format total
            |--------------------------------------------------------------------------
            */

            $roomSheet
                ->getStyle(
                    "P{$roomTotalRow}"
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '#,##0.00'
                );

            $roomSheet
                ->getStyle(
                    "R{$roomTotalRow}"
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '#,##0.00'
                );

            /*
            |--------------------------------------------------------------------------
            | Lebar kolom
            |--------------------------------------------------------------------------
            */

            $setColumnWidths(
                $roomSheet
            );

            /*
            |--------------------------------------------------------------------------
            | Freeze
            |--------------------------------------------------------------------------
            */

            $roomSheet->freezePane(
                'A8'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Nama file
        |--------------------------------------------------------------------------
        */

        if ($request->filled('periode')) {

            $filename =
                'Laporan_Index_Scoring_'
                . $request->periode
                . '.xlsx';
        } else {

            $filename =
                'Laporan_Index_Scoring_Semua_Periode.xlsx';
        }

        /*
        |--------------------------------------------------------------------------
        | Download
        |--------------------------------------------------------------------------
        */

        $writer =
            new Xlsx(
                $spreadsheet
            );

        return response()->streamDownload(

            function () use ($writer) {

                $writer->save(
                    'php://output'
                );
            },

            $filename,

            [
                'Content-Type' =>
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }
}
