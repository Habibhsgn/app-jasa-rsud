<?php

namespace App\Exports;

use App\Models\JasaPegawai;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PerRuanganSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, WithCustomStartCell, WithStyles
{
    protected $item;
    private $rowNumber = 0;

    public function __construct($item) { $this->item = $item; }

    public function title(): string { return $this->item->ruangan->nama_ruangan ?? 'Ruangan'; }

    public function collection()
    {
        return JasaPegawai::with('pegawai')
            ->where('jasa_ruangan_id', $this->item->id) // Filter per satu ruangan
            ->get();
    }

    public function startCell(): string { return 'A7'; }
    
    public function map($jp): array
    {
        $this->rowNumber++;
        return [$this->rowNumber, $jp->pegawai->nama, $jp->pegawai->jabatan, $jp->nominal, $jp->keterangan, $jp->persen . '%'];
    }

    public function headings(): array
    {
        return ["No.", "Nama", "Jabatan Ruang", "Jasa 30%", "Keterangan", "%"];
    }

    public function styles(Worksheet $sheet)
    {
        $karu = User::where('ruangan_id', $this->item->ruangan_id)->first();
        $periodeArray = explode(' ', $this->item->periode->periode);

        $sheet->setCellValue('A1', 'Nama: ' . ($karu->name ?? 'Admin'));
        $sheet->setCellValue('A2', 'Ruang: ' . $this->item->ruangan->nama_ruangan);
        $sheet->setCellValue('A3', 'Bulan: ' . ($periodeArray[0] ?? '-'));
        $sheet->setCellValue('A4', 'Tahun: ' . ($periodeArray[1] ?? '-'));
        $sheet->setCellValue('A5', 'Nilai: ' . number_format($this->item->nominal, 0, ',', '.'));

        return [7 => ['font' => ['bold' => true]]];
    }
}