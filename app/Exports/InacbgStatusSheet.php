<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InacbgStatusSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    protected $data;
    protected $status;
    protected $title;

    public function __construct($data, $status, $title)
    {
        $this->data = $data;
        $this->status = $status;
        $this->title = $title;
    }

    public function collection()
    {
        return $this->data->where('status', $this->status)->values();
    }

    public function title(): string
    {
        return $this->title;
    }

    public function map($claim): array
    {
        return [
            $claim->dpjp ?? '-',
            $claim->discharge_date ? \Carbon\Carbon::parse($claim->discharge_date)->format('d/m/Y') : '-',
            $claim->kelas_rawat ?? '-',
            $claim->nama_pasien ?? '-',
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
            'Tanggal Keluar',
            'Kelas Rawat',
            'Nama',
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

        // Format numeric columns as numbers (not text) - columns F through J (6-10)
        for ($row = 2; $row <= $highestRow; $row++) {
            for ($col = 'F'; $col <= 'J'; $col++) {
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
            'B' => 18,  // Tanggal Keluar
            'C' => 15,  // Kelas Rawat
            'D' => 30,  // Nama
            'E' => 20,  // No SEP
            'F' => 18,  // Total Billing
            'G' => 18,  // Lab
            'H' => 18,  // Radiologi
            'I' => 18,  // USG
            'J' => 18,  // UTD
        ];
    }
}