<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class JasaPegawaiExport implements WithMultipleSheets
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [];

        // 1. Panggil class KHUSUS GABUNGAN (kirim semua data)
        $sheets[] = new SemuaRuanganSheet($this->data);

        // 2. Loop class KHUSUS PER RUANGAN (kirim satu per satu)
        foreach ($this->data as $item) {
            $sheets[] = new PerRuanganSheet($item);
        }

        return $sheets;
    }
}
