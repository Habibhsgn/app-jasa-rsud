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
            new InacbgTypeStatusSheet($this->data, 'ranap', 'disetujui', 'RANAP Disetujui'),
            new InacbgTypeStatusSheet($this->data, 'ranap', 'pending', 'RANAP Pending'),
            new InacbgTypeStatusSheet($this->data, 'ralan', 'disetujui', 'RALAN Disetujui'),
            new InacbgTypeStatusSheet($this->data, 'ralan', 'pending', 'RALAN Pending'),
        ];
    }
}