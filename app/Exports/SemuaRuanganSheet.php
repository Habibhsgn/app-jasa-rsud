<?php

namespace App\Exports;

use App\Models\JasaPegawai;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SemuaRuanganSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, WithCustomStartCell, WithStyles
{
    protected $data; // Koleksi JasaRuangan
    private $rowNumber = 0;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'GABUNGAN SEMUA RUANGAN';
    }

    public function collection()
    {
        // Ambil semua JasaPegawai yang terkait dengan koleksi JasaRuangan yang dikirim
        return JasaPegawai::with(['pegawai', 'jasaRuangan.ruangan'])
            ->whereIn('jasa_ruangan_id', $this->data->pluck('id'))
            ->get();
    }

    public function startCell(): string
    {
        return 'A7';
    }

    public function map($jp): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber,
            $jp->pegawai->nama,
            $jp->pegawai->jabatan,
            $jp->nominal,
            $jp->keterangan,
            $jp->persen . '%'
        ];
    }

    public function headings(): array
    {
        return ["No.", "Nama (Ruangan)", "Jabatan Ruang", "Jasa 30%", "Keterangan", "%"];
    }

    public function styles(Worksheet $sheet)
    {
        $firstItem = $this->data->first();
        $periodeArray = explode(' ', $firstItem->periode->periode ?? ' ');
        $bulan = $periodeArray[0] ?? '-';
        $tahun = $periodeArray[1] ?? '-';

        // Tulis Header sesuai style asli Anda
        $sheet->setCellValue('A1', 'Nama: Admin Pusat');
        $sheet->setCellValue('A2', 'Ruang: Semua Ruangan');
        $sheet->setCellValue('A3', 'Bulan: ' . $bulan);
        $sheet->setCellValue('A4', 'Tahun: ' . $tahun);
        $sheet->setCellValue('A5', 'Total Nilai: ' . number_format($this->data->sum('nominal'), 0, ',', '.'));

        return [
            7 => ['font' => ['bold' => true]],
        ];
    }
}