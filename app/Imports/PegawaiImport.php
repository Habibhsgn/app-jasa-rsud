<?php

namespace App\Imports;

use App\Models\Pegawai;
use App\Models\Ruangan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PegawaiImport implements ToCollection, WithHeadingRow
{
    /**
     * Baris yang akan di-UPDATE (pegawai lama ditemukan)
     * Format: [ ['id' => 12, 'data' => [...]], ... ]
     *
     * @var array
     */
    public array $toUpdate = [];

    /**
     * Baris yang akan di-INSERT (pegawai baru)
     *
     * @var array
     */
    public array $toInsert = [];

    /**
     * Daftar error validasi
     *
     * @var array
     */
    public array $errors = [];

    /**
     * Ringkasan hasil (diisi setelah proses)
     *
     * @var array
     */
    public array $summary = [
        'update' => 0,
        'insert' => 0,
    ];

    /**
     * Header yang wajib ada.
     * risk & emergency SENGAJA tidak wajib lagi -> nilainya diambil dari relasi Ruangan.
     * id_petugas SENGAJA tidak wajib -> boleh kosong (pegawai baru / belum diisi).
     *
     * @var array
     */
    protected array $requiredHeaders = [
        'nama',
        'ruangan_id',
        'jabatan',
        'gaji_pokok',
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
        $ruanganMap = Ruangan::get()->keyBy('id');

        // ==========================
        // LOAD PEGAWAI EXISTING (1 QUERY)
        // untuk keperluan matching update vs insert
        // ==========================
        $pegawaiExisting = Pegawai::select('id', 'id_petugas', 'nama', 'ruangan_id')->get();

        // id_petugas yang sudah dipakai pegawai NONAKTIF (soft-deleted)
        // -> dipakai untuk deteksi konflik, bukan untuk restore otomatis
        $deletedIdPetugas = Pegawai::onlyTrashed()
            ->whereNotNull('id_petugas')
            ->pluck('id_petugas')
            ->map(fn($v) => $this->normalize((string) $v))
            ->flip();

        // Map by id_petugas (hanya yang terisi)
        $mapByIdPetugas = $pegawaiExisting
            ->filter(fn($p) => filled($p->id_petugas))
            ->keyBy(fn($p) => $this->normalize((string) $p->id_petugas));

        // Map fallback by nama + ruangan_id (untuk deteksi duplikat baris identik)
        $mapByNamaRuangan = $pegawaiExisting->keyBy(function ($p) {
            return $this->normalize($p->nama) . '|' . $p->ruangan_id;
        });

        // Map fallback by NAMA SAJA (lintas ruangan) -> dipakai saat pegawai pindah
        // ruangan di Excel tanpa id_petugas. Hanya dipakai kalau namanya UNIK
        // (tidak ada pegawai lain dengan nama sama) supaya tidak salah cocok.
        $namaCount = $pegawaiExisting->groupBy(fn($p) => $this->normalize($p->nama))
            ->map(fn($g) => $g->count());

        $mapByNamaUnik = $pegawaiExisting
            ->filter(fn($p) => $namaCount->get($this->normalize($p->nama)) === 1)
            ->keyBy(fn($p) => $this->normalize($p->nama));

        $now = now();

        // Untuk cek duplikasi DI DALAM file Excel itu sendiri
        $seenIdPetugas = [];
        $seenNamaRuangan = [];

        // ==========================
        // VALIDASI & PROSES PER BARIS
        // ==========================
        foreach ($rows as $index => $row) {

            $baris = $index + 2;

            $nama = trim((string) ($row['nama'] ?? ''));
            $idPetugas = trim((string) ($row['id_petugas'] ?? ''));
            $jabatan = trim((string) ($row['jabatan'] ?? ''));
            $ruanganId = trim((string) ($row['ruangan_id'] ?? ''));
            $pendidikanNonFormal = trim((string) ($row['pendidikan_non_formal'] ?? ''));
            $gajiPokok = trim((string) ($row['gaji_pokok'] ?? ''));

            // risk & emergency SENGAJA dibaca lalu DIABAIKAN
            // (masih bisa ada di file karena berasal dari hasil Export, tapi tidak dipakai)

            // ==========================
            // SKIP BARIS KOSONG
            // ==========================
            if (
                $nama === '' &&
                $idPetugas === '' &&
                $jabatan === '' &&
                $ruanganId === '' &&
                $pendidikanNonFormal === '' &&
                $gajiPokok === ''
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

            if ($ruanganId === '') {
                $this->errors[] = "Baris {$baris}: Ruangan ID kosong.";
                continue;
            }

            if (!is_numeric($ruanganId)) {
                $this->errors[] = "Baris {$baris}: Ruangan ID harus berupa angka.";
                continue;
            }

            if ($gajiPokok === '') {
                $this->errors[] = "Baris {$baris}: Gaji pokok kosong.";
                continue;
            }

            $gajiPokok = str_replace(['.', ','], ['', '.'], $gajiPokok);

            if (!is_numeric($gajiPokok)) {
                $this->errors[] = "Baris {$baris}: Gaji pokok harus berupa angka.";
                continue;
            }

            if ($idPetugas !== '' && mb_strlen($idPetugas) > 18) {
                $this->errors[] = "Baris {$baris}: ID Petugas maksimal 18 karakter.";
                continue;
            }

            // ==========================
            // CEK RUANGAN
            // ==========================
            $ruangan = $ruanganMap->get((int) $ruanganId);

            if (!$ruangan) {
                $this->errors[] = "Baris {$baris}: Ruangan ID '{$ruanganId}' tidak ditemukan di master ruangan.";
                continue;
            }

            // ==========================
            // DUPLIKAT DI DALAM FILE EXCEL
            // ==========================
            if ($idPetugas !== '') {
                $keyId = $this->normalize($idPetugas);

                if (isset($seenIdPetugas[$keyId])) {
                    $this->errors[] = "Baris {$baris}: ID Petugas '{$idPetugas}' terduplikasi di file Excel.";
                    continue;
                }

                $seenIdPetugas[$keyId] = true;
            } else {
                $keyNamaRuangan = $this->normalize($nama) . '|' . $ruangan->id;

                if (isset($seenNamaRuangan[$keyNamaRuangan])) {
                    $this->errors[] =
                        "Baris {$baris}: Pegawai '{$nama}' pada ruangan ID '{$ruanganId}' terduplikasi di file Excel.";
                    continue;
                }

                $seenNamaRuangan[$keyNamaRuangan] = true;
            }

            // ==========================
            // DATA DASAR (risk/emergency DIAMBIL DARI RUANGAN, BUKAN DARI EXCEL)
            // ==========================
            $data = [
                'nama' => $nama,
                'ruangan_id' => $ruangan->id,
                'jabatan' => $jabatan,
                'pendidikan_non_formal' => $pendidikanNonFormal,
                'gaji_pokok' => (float) $gajiPokok,
                'risk' => $ruangan->resiko,
                'emergency' => $ruangan->emergency,
                'updated_at' => $now,
            ];

            // ==========================
            // MATCHING: UPDATE vs INSERT
            // ==========================
            $existing = null;
            $namaAmbigu = $namaCount->get($this->normalize($nama)) > 1;

            if ($idPetugas !== '') {
                // Prioritas 1: match by id_petugas (pegawai yang di DB sudah punya id_petugas ini)
                $existing = $mapByIdPetugas->get($this->normalize($idPetugas));

                // Fallback 2: id_petugas ini belum pernah tersimpan di DB (baru "diperkenalkan"
                // lewat file Excel) -> coba nama+ruangan (kasus ruangan tidak berubah)
                if (!$existing) {
                    $existing = $mapByNamaRuangan->get($this->normalize($nama) . '|' . $ruangan->id);
                }

                // Fallback 3: ruangan-nya BERUBAH di Excel (pindah ruangan) -> cocokkan by nama
                // saja, asal namanya unik di database (tidak ada pegawai lain nama sama)
                if (!$existing) {
                    $existing = $mapByNamaUnik->get($this->normalize($nama));
                }

                // id_petugas ikut di-update baik ketemu lewat jalur manapun
                $data['id_petugas'] = $idPetugas;
            } else {
                // id_petugas kosong di file:
                // Fallback 1: nama + ruangan_id sama persis (kasus umum, ruangan tidak berubah)
                $existing = $mapByNamaRuangan->get($this->normalize($nama) . '|' . $ruangan->id);

                // Fallback 2: ruangan-nya berubah -> cocokkan by nama saja, asal unik
                if (!$existing && !$namaAmbigu) {
                    $existing = $mapByNamaUnik->get($this->normalize($nama));
                }

                // Nama ambigu (ada pegawai lain nama sama) DAN ruangan di Excel berbeda dari
                // semua kemungkinan di DB -> tidak bisa dipastikan ini orang yang mana.
                // Minta diisi id_petugas supaya tidak salah update.
                if (!$existing && $namaAmbigu) {
                    $this->errors[] =
                        "Baris {$baris}: Nama '{$nama}' ditemukan lebih dari satu pegawai dengan nama sama " .
                        "di sistem, dan tidak cocok dengan ruangan manapun. Isi ID Petugas untuk memastikan data yang dimaksud.";
                    continue;
                }
                // id_petugas TIDAK disertakan ke $data -> tidak menimpa nilai id_petugas
                // yang mungkin sudah terisi manual di database sebelumnya.
            }

            if ($existing) {
                $this->toUpdate[] = [
                    'id' => $existing->id,
                    'data' => $data,
                ];
            } else {
                // Cegah bentrok dengan id_petugas milik pegawai yang sudah nonaktif (soft-deleted)
                if ($idPetugas !== '' && $deletedIdPetugas->has($this->normalize($idPetugas))) {
                    $this->errors[] =
                        "Baris {$baris}: ID Petugas '{$idPetugas}' terdaftar pada pegawai yang sudah nonaktif. " .
                        "Aktifkan kembali secara manual jika memang pegawai yang sama.";
                    continue;
                }

                $data['id_petugas'] = $idPetugas !== '' ? $idPetugas : null;
                $data['created_at'] = $now;
                $this->toInsert[] = $data;
            }
        }

        $this->summary = [
            'update' => count($this->toUpdate),
            'insert' => count($this->toInsert),
        ];
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
        $text = mb_strtolower($text);
        $text = preg_replace('/\s+/', ' ', $text);

        return $text;
    }
}
