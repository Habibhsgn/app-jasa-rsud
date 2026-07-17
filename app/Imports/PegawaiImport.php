<?php

namespace App\Imports;

use App\Models\Ruangan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PegawaiImport implements ToCollection, WithHeadingRow
{
    /**
     * Data yang sudah valid
     *
     * @var array
     */
    public array $pegawai = [];

    /**
     * Daftar error validasi
     *
     * @var array
     */
    public array $errors = [];

    /**
     * Header yang wajib ada
     *
     * @var array
     */
    protected array $requiredHeaders = [
        'nama',
        'ruangan',
        'jabatan',
        'pendidikan_non_formal',
        'gaji_pokok',
        'risk',
        'emergency',
    ];

    public function collection(Collection $rows)
    {
        // ==========================
        // VALIDASI FILE
        // ==========================
        if ($rows->count() === 0) {
            $this->errors[] = 'File Excel tidak memiliki data.';
            return;
        }

        // ==========================
        // VALIDASI HEADER
        // ==========================
        $headers = array_keys($rows->first()->toArray());

        foreach ($this->requiredHeaders as $header) {
            if (!in_array($header, $headers)) {
                $this->errors[] = "Kolom '{$header}' tidak ditemukan.";
            }
        }

        if (!empty($this->errors)) {
            return;
        }

        // ==========================
        // LOAD MASTER RUANGAN (1 QUERY)
        // ==========================
        $ruanganMap = Ruangan::get()->keyBy(function ($item) {
            return $this->normalize($item->nama_ruangan);
        });

        // Timestamp cukup sekali
        $now = now();

        // Untuk cek duplikasi di file Excel
        $pegawaiCheck = [];

        // ==========================
        // VALIDASI DATA
        // ==========================
        foreach ($rows as $index => $row) {

            $baris = $index + 2;

            $nama = trim((string) ($row['nama'] ?? ''));
            $idPetugas = trim((string) ($row['id_petugas'] ?? ''));
            $jabatan = trim((string) ($row['jabatan'] ?? ''));
            $namaRuangan = trim((string) ($row['ruangan'] ?? ''));

            $pendidikanNonFormal = trim((string) ($row['pendidikan_non_formal'] ?? ''));
            $gajiPokok = trim((string) ($row['gaji_pokok'] ?? ''));
            $risk = trim((string) ($row['risk'] ?? ''));
            $emergency = trim((string) ($row['emergency'] ?? ''));

            // ==========================
            // SKIP BARIS KOSONG
            // ==========================
            if (
                $nama === '' &&
                $idPetugas === '' &&
                $jabatan === '' &&
                $namaRuangan === '' &&
                $pendidikanNonFormal === '' &&
                $gajiPokok === '' &&
                $risk === '' &&
                $emergency === ''
            ) {
                continue;
            }

            // ==========================
            // VALIDASI KOLOM WAJIB
            // ==========================
            if ($nama === '') {
                $this->errors[] = "Baris {$baris}: Nama pegawai kosong.";
                continue;
            }

            if ($jabatan === '') {
                $this->errors[] = "Baris {$baris}: Jabatan kosong.";
                continue;
            }

            if ($namaRuangan === '') {
                $this->errors[] = "Baris {$baris}: Ruangan kosong.";
                continue;
            }

            // if ($pendidikanNonFormal === '') {
            //     $this->errors[] = "Baris {$baris}: Pendidikan non formal kosong.";
            //     continue;
            // }

            if ($gajiPokok === '') {
                $this->errors[] = "Baris {$baris}: Gaji pokok kosong.";
                continue;
            }

            if ($risk === '') {
                $this->errors[] = "Baris {$baris}: Risk kosong.";
                continue;
            }

            if ($emergency === '') {
                $this->errors[] = "Baris {$baris}: Emergency kosong.";
                continue;
            }

            $gajiPokok = str_replace(['.', ','], ['', '.'], $gajiPokok);

            if (!is_numeric($gajiPokok)) {
                $this->errors[] = "Baris {$baris}: Gaji pokok harus berupa angka.";
                continue;
            }

            if (!is_numeric($risk)) {
                $this->errors[] = "Baris {$baris}: Risk harus berupa angka.";
                continue;
            }

            if (!is_numeric($emergency)) {
                $this->errors[] = "Baris {$baris}: Emergency harus berupa angka.";
                continue;
            }

            // ==========================
            // DUPLIKAT DI FILE EXCEL
            // ==========================
            $key = $this->normalize($nama) . '|' . $this->normalize($namaRuangan);

            if (isset($pegawaiCheck[$key])) {
                $this->errors[] =
                    "Baris {$baris}: Pegawai '{$nama}' pada ruangan '{$namaRuangan}' terduplikasi di file Excel.";
                continue;
            }

            $pegawaiCheck[$key] = true;

            // ==========================
            // CEK RUANGAN
            // ==========================
            $ruangan = $ruanganMap->get(
                $this->normalize($namaRuangan)
            );

            if (!$ruangan) {
                $this->errors[] =
                    "Baris {$baris}: Ruangan '{$namaRuangan}' tidak ditemukan.";
                continue;
            }

            // ==========================
            // DATA SIAP IMPORT
            // ==========================
            $this->pegawai[] = [
                'nama' => $nama,
                'id_petugas' => null,
                'ruangan_id' => $ruangan->id,
                'jabatan' => $jabatan,
                'pendidikan_non_formal' => $pendidikanNonFormal,
                'gaji_pokok' => (float) $gajiPokok,
                'risk' => (float) $risk,
                'emergency' => (float) $emergency,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
    }

    /**
     * Apakah validasi berhasil
     */
    public function passed(): bool
    {
        return empty($this->errors);
    }

    /**
     * Normalisasi text
     */
    private function normalize(string $text): string
    {
        $text = trim($text);

        // lowercase
        $text = mb_strtolower($text);

        // hilangkan spasi ganda
        $text = preg_replace('/\s+/', ' ', $text);

        return $text;
    }
}
