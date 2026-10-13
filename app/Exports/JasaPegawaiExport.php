<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class JasaPegawaiExport implements WithMultipleSheets
{
    protected $data;
    protected $jenis; // null | REGULER | PENDING

    public function __construct($data, $jenis = null)
    {
        $this->data  = $data;
        $this->jenis = $jenis;
    }

    public function sheets(): array
    {
        $sheets = [new SemuaRuanganSheet($this->data, $this->jenis)];

        foreach ($this->data as $item) {
            $sheets[] = new PerRuanganSheet($item);
        }

        return $sheets;
    }
}
