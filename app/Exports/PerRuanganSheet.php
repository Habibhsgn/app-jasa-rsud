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

class PerRuanganSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, WithCustomStartCell, WithStyles
{
    protected $item;
    private $rowNumber = 0;

    public function __construct($item)
    {
        $this->item = $item;
    }

    // Judul Sheet (Nama Ruangan)
    public function title(): string
    {
        return $this->item->ruangan->nama_ruangan ?? 'Ruangan';
    }

    // Ambil data pegawai untuk isi tabel
    public function collection()
    {
        return JasaPegawai::with('pegawai')
            ->where('jasa_ruangan_id', $this->item->id)
            ->get();
    }

    // Mulai dari baris ke-7 agar ada tempat untuk header Nama Karu, dsb.
    public function startCell(): string
    {
        return 'A7';
    }

    // Mapping kolom tabel (No -> Nama -> Jabatan -> Jasa -> Keterangan -> %)
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

    // Header Tabel
    public function headings(): array
    {
        return ["No.", "Nama", "Jabatan Ruang", "Jasa 30%", "Keterangan", "%"];
    }

    // Custom Header (Nama Karu, Ruang, Periode, Nilai)
    public function styles(Worksheet $sheet)
    {
        // Cari User Admin/Karu yang bertanggung jawab (ambil user pertama yang memiliki ruangan_id ini)
        $karu = \App\Models\User::where('ruangan_id', $this->item->ruangan_id)->first();
        
        // Pecah periode (Asumsi format periode: "Januari 2026")
        $periodeArray = explode(' ', $this->item->periode->periode);
        $bulan = $periodeArray[0] ?? '-';
        $tahun = $periodeArray[1] ?? '-';

        // Tulis Header di bagian atas
        $sheet->setCellValue('A1', 'Nama: ' . ($karu->name ?? 'Admin'));
        $sheet->setCellValue('A2', 'Ruang: ' . $this->item->ruangan->nama_ruangan);
        $sheet->setCellValue('A3', 'Bulan: ' . $bulan);
        $sheet->setCellValue('A4', 'Tahun: ' . $tahun);
        $sheet->setCellValue('A5', 'Nilai: ' . number_format($this->item->nominal, 0, ',', '.'));

        // Styling Tebal untuk header tabel
        return [
            7 => ['font' => ['bold' => true]],
        ];
    }
}