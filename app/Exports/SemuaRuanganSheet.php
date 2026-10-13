<?php

namespace App\Exports;

use App\Models\JasaPegawai;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SemuaRuanganSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, WithCustomStartCell, WithStyles
{
    protected $data;   // koleksi JasaRuangan
    protected $jenis;  // null | REGULER | PENDING
    private $rowNumber = 0;

    public function __construct($data, $jenis = null)
    {
        $this->data  = $data;
        $this->jenis = $jenis;
    }

    public function title(): string
    {
        return match ($this->jenis) {
            'PENDING' => 'GABUNGAN PENDING',
            'REGULER' => 'GABUNGAN REGULER',
            default   => 'GABUNGAN SEMUA',
        };
    }

    public function collection()
    {
        return JasaPegawai::with(['pegawai', 'jasaRuangan.ruangan', 'jasaRuangan.periode'])
            ->whereIn('jasa_ruangan_id', $this->data->pluck('id'))
            ->orderBy('jasa_ruangan_id')
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
        $ruangan = $jp->jasaRuangan?->ruangan?->nama_ruangan ?? '-';

        return [
            $this->rowNumber,
            ($jp->pegawai?->nama ?? '(Pegawai dihapus)') . ' (' . $ruangan . ')',
            $jp->pegawai?->jabatan ?? '-',
            $jp->jasaRuangan?->periode?->keterangan ?? '-',
            $jp->nominal,
            $jp->keterangan,
            (float) $jp->persen . '%',
        ];
    }

    public function headings(): array
    {
        $labelJasa = match ($this->jenis) {
            'PENDING' => 'Jasa Pending',
            'REGULER' => 'Jasa 30%',
            default   => 'Jasa',
        };

        return ['No.', 'Nama (Ruangan)', 'Jabatan Ruang', 'Jenis', $labelJasa, 'Keterangan', '%'];
    }

    public function styles(Worksheet $sheet)
    {
        $first = $this->data->first();
        $bulan = '-';
        $tahun = '-';

        if ($this->data->pluck('periode.periode')->unique()->count() === 1 && $first?->periode?->periode) {
            $c = Carbon::parse($first->periode->periode . '-01');
            $bulan = $c->translatedFormat('F');
            $tahun = $c->format('Y');
        } else {
            $bulan = 'Semua Periode';
        }

        $jenisLabel = $this->jenis ? ucfirst(strtolower($this->jenis)) : 'Reguler & Pending';

        $sheet->setCellValue('A1', 'Nama: Admin Pusat');
        $sheet->setCellValue('A2', 'Ruang: Semua Ruangan (' . $jenisLabel . ')');
        $sheet->setCellValue('A3', 'Bulan: ' . $bulan);
        $sheet->setCellValue('A4', 'Tahun: ' . $tahun);
        $sheet->setCellValue('A5', 'Total Nilai: ' . number_format($this->data->sum('nominal'), 0, ',', '.'));

        return [7 => ['font' => ['bold' => true]]];
    }
}
