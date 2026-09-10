<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InacbgExport implements WithMultipleSheets
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new InacbgStatusSheet($this->data, 'disetujui', 'Disetujui'),
            new InacbgStatusSheet($this->data, 'pending', 'Pending'),
        ];
    }
}