<?php

namespace App\Exports;

use App\Models\JasaRuangan;
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

        foreach ($this->data as $item) {
            // Setiap JasaRuangan (per ruangan per periode) akan jadi 1 sheet
            $sheets[] = new PerRuanganSheet($item);
        }

        return $sheets;
    }
}