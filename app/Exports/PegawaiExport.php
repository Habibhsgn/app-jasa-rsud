<?php

namespace App\Exports;

use App\Models\Pegawai;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PegawaiExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected ?int $ruanganId;

    public function __construct(?int $ruanganId = null)
    {
        $this->ruanganId = $ruanganId;
    }

    public function collection()
    {
        return Pegawai::with('ruangan:id,nama_ruangan')
            ->when($this->ruanganId, fn($q) => $q->where('ruangan_id', $this->ruanganId))
            ->orderBy('ruangan_id')
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'nama',
            'id_petugas',
            'ruangan_id',
            'ruangan',
            'jabatan',
            'pendidikan_non_formal',
            'gaji_pokok',
        ];
    }

    public function map($pegawai): array
    {
        return [
            $pegawai->nama,
            $pegawai->id_petugas,
            $pegawai->ruangan_id,
            $pegawai->ruangan->nama_ruangan ?? '',
            $pegawai->jabatan,
            $pegawai->pendidikan_non_formal,
            $pegawai->gaji_pokok,
        ];
    }

    /**
     * FIX: paksa kolom B (id_petugas) benar-benar bertipe STRING di level cell,
     * bukan cuma diformat text. Ini yang membuat Excel berhenti mengubahnya
     * jadi notasi scientific (1,98107E+17).
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Mulai dari baris 2 (baris 1 = heading)
                for ($row = 2; $row <= $highestRow; $row++) {
                    $cell = $sheet->getCell('B' . $row);
                    $value = $cell->getValue();

                    $sheet->setCellValueExplicit(
                        'B' . $row,
                        (string) $value,
                        DataType::TYPE_STRING
                    );
                }

                // Format tampilan kolom B sebagai Text juga (jaga-jaga)
                $sheet->getStyle('B2:B' . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('@');
            },
        ];
    }
}
