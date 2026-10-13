<?php

namespace App\Exports;

use App\Models\JasaPegawai;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
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

    private function jenis(): string
    {
        return strtoupper($this->item->periode?->keterangan ?? 'REGULER');
    }

    public function title(): string
    {
        $nama = $this->item->ruangan->nama_ruangan ?? 'Ruangan';
        $nama = preg_replace('/[\\\\\/\?\*\[\]:]/', '', $nama);

        // Maks 31 karakter dan harus unik: "NAMA-P12" / "NAMA-R7"
        $kode = ($this->jenis() === 'PENDING' ? 'P' : 'R') . $this->item->id;

        return Str::limit($nama, 31 - strlen($kode) - 1, '') . '-' . $kode;
    }

    public function collection()
    {
        return JasaPegawai::with('pegawai')
            ->where('jasa_ruangan_id', $this->item->id)
            ->orderBy('id')
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
            $jp->pegawai?->nama ?? '(Pegawai dihapus)',
            $jp->pegawai?->jabatan ?? '-',
            $jp->nominal,
            $jp->keterangan,
            (float) $jp->persen . '%',
        ];
    }

    public function headings(): array
    {
        return ['No.', 'Nama', 'Jabatan Ruang', $this->jenis() === 'PENDING' ? 'Jasa Pending' : 'Jasa 30%', 'Keterangan', '%'];
    }

    public function styles(Worksheet $sheet)
    {
        $karu = User::where('ruangan_id', $this->item->ruangan_id)->first();

        $bulan = '-';
        $tahun = '-';
        if ($this->item->periode?->periode) {
            $c = Carbon::parse($this->item->periode->periode . '-01');
            $bulan = $c->translatedFormat('F');
            $tahun = $c->format('Y');
        }

        $sheet->setCellValue('A1', 'Nama: ' . ($karu->name ?? 'Admin'));
        $sheet->setCellValue('A2', 'Ruang: ' . ($this->item->ruangan?->nama_ruangan ?? '-') . ' (' . ucfirst(strtolower($this->jenis())) . ')');
        $sheet->setCellValue('A3', 'Bulan: ' . $bulan);
        $sheet->setCellValue('A4', 'Tahun: ' . $tahun);
        $sheet->setCellValue('A5', 'Nilai: ' . number_format($this->item->nominal, 0, ',', '.'));

        return [7 => ['font' => ['bold' => true]]];
    }
}
