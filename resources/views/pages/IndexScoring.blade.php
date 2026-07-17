@extends('layouts.app')

@section('title', 'Index Scoring')

@section('content')
    <h1 class="h3 mb-3"><strong>Index Scoring</strong></h1>

    {{-- Flash messages --}}
    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Form Pengajuan --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i data-feather="file-plus" class="me-2"></i>
                Pengajuan Index Scoring
            </h5>
        </div>

        <div class="card-body">
            <form action="{{ route('index.scoring.create') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            Periode Pengajuan
                        </label>
                        <input type="month" name="periode" class="form-control"
                            max="{{ now()->subMonth()->format('Y-m') }}" required>
                        <small class="text-muted">
                            Periode yang dapat dipilih maksimal bulan sebelumnya.
                        </small>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success">
                            <i data-feather="plus"></i>
                            Buat Pengajuan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Loop Periode (setiap periode = kartu terpisah, dengan form sendiri) --}}
    @if (isset($data) && $data->count())
        @foreach ($data as $periodeItem)
            <div class="mb-5">

                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0 me-2">Periode: {{ $periodeItem->periode_label }}</h5>

                    @if ($periodeItem->status_pengajuan == 'draft')
                        <span class="badge bg-warning">Draft</span>
                    @elseif($periodeItem->status_pengajuan == 'submit')
                        <span class="badge bg-success">Sudah Submit</span>
                    @elseif($periodeItem->status_pengajuan == 'verifikasi')
                        <span class="badge bg-info text-dark">Menunggu Verifikasi</span>
                    @elseif($periodeItem->status_pengajuan == 'revisi')
                        <span class="badge bg-danger">Perlu Revisi — Silakan lengkapi &amp; submit ulang</span>
                    @elseif($periodeItem->status_pengajuan == 'selesai')
                        <span class="badge bg-primary">Selesai</span>
                    @endif
                </div>

                @foreach ($periodeItem->ruangans as $item)
                    <div class="card mb-4">
                        <div
                            class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
                            <div>
                                <span class="text-muted small">Ruangan:</span>
                                <strong>{{ $item->ruangan->nama_ruangan ?? '-' }}</strong>
                            </div>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('index.scoring.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="jasa_ruangan_id" value="{{ $item->id }}">
                                <input type="hidden" name="periode" value="{{ $periodeItem->periode }}">

                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle text-center small mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th rowspan="2" class="align-middle text-center" style="min-width:70px;">
                                                    NO</th>
                                                <th rowspan="2" class="align-middle text-center"
                                                    style="min-width:250px;">NAMA</th>

                                                <th colspan="7" class="align-middle text-center">INDEKS SKOR</th>
                                                <th colspan="4" class="align-middle text-center">BOBOT PENGURANG</th>

                                                <th rowspan="2" class="align-middle text-center"
                                                    style="min-width:220px;">
                                                    SIKAP<br>
                                                    <small class="fw-normal">
                                                        (Kriminal, Narkoba,<br>Asusila, Merokok, dll)
                                                    </small>
                                                </th>

                                                <th rowspan="2" class="align-middle text-center"
                                                    style="min-width:170px;">
                                                    JUMLAH<br>
                                                    <small class="fw-normal">
                                                        (Total Skor Pengurangan)
                                                    </small>
                                                </th>

                                                <th rowspan="2" class="align-middle text-center"
                                                    style="min-width:180px;">
                                                    Keterangan
                                                </th>

                                                <th rowspan="2" class="align-middle text-center"
                                                    style="min-width:170px;">
                                                    JUMLAH AKHIR
                                                </th>
                                            </tr>

                                            <tr>
                                                <th class="align-middle text-center" style="min-width:120px;">NIP/NIP3K</th>
                                                <th class="align-middle text-center" style="min-width:160px;">Jabatan</th>
                                                <th class="align-middle text-center" style="min-width:120px;">
                                                    Pend.<br>Formal</th>
                                                <th class="align-middle text-center" style="min-width:140px;">Pend.
                                                    Non<br>Formal</th>
                                                <th class="align-middle text-center" style="min-width:120px;">Gaji<br>Pokok
                                                </th>
                                                <th class="align-middle text-center" style="min-width:110px;">Risk</th>
                                                <th class="align-middle text-center" style="min-width:120px;">Emergency</th>

                                                <th class="align-middle text-center" style="min-width:100px;">Cuti</th>
                                                <th class="align-middle text-center" style="min-width:100px;">Izin</th>
                                                <th class="align-middle text-center" style="min-width:120px;">Tanpa<br>Izin
                                                </th>
                                                <th class="align-middle text-center" style="min-width:100px;">Telat</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($item->pegawai as $i => $p)
                                                @php
                                                    $rowDisabled = $periodeItem->disable_input || empty($p->id_petugas);
                                                @endphp

                                                <tr>
                                                    <td>{{ $i + 1 }}</td>

                                                    <input type="hidden" name="pegawai[{{ $p->id }}][ruangan_id]"
                                                        value="{{ $item->id }}">
                                                    <input type="hidden" name="pegawai[{{ $p->id }}][pegawai_id]"
                                                        value="{{ $p->id }}">

                                                    <td class="text-start">
                                                        <strong>{{ $p->nama }}</strong>
                                                        <input type="hidden" name="pegawai_id[]"
                                                            value="{{ $p->id }}">
                                                    </td>

                                                    <td>
                                                        @if ($p->id_petugas)
                                                            <span class="badge bg-success">{{ $p->id_petugas }}</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark">Lengkapi ID</span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        <select name="pegawai[{{ $p->id }}][jabatan]"
                                                            class="form-select form-select-sm jabatan"
                                                            {{ $rowDisabled ? 'disabled' : '' }}>

                                                            <option value="0"
                                                                {{ $p->jabatan == 0 ? 'selected' : '' }}>-- Pilih Jabatan
                                                                --</option>
                                                            <option value="1"
                                                                {{ $p->jabatan == 1 ? 'selected' : '' }}>(1) Tidak memiliki
                                                                jabatan</option>
                                                            <option value="2"
                                                                {{ $p->jabatan == 2 ? 'selected' : '' }}>(2) Koordinator,
                                                                Admin, Penanggung Jawab, Bendahara</option>
                                                            <option value="3"
                                                                {{ $p->jabatan == 3 ? 'selected' : '' }}>(3) Kepala
                                                                Seksi/Sub Bidang, Kepala Ruangan</option>
                                                            <option value="4"
                                                                {{ $p->jabatan == 4 ? 'selected' : '' }}>(4) Kepala Bidang,
                                                                Kepala Bagian, Kepala Instalasi, IPCLN</option>
                                                            <option value="6"
                                                                {{ $p->jabatan == 6 ? 'selected' : '' }}>(6) Ketua Komite
                                                                Medik, Ketua Komite Keperawatan, Ketua SPI, Wadir</option>
                                                            <option value="8"
                                                                {{ $p->jabatan == 8 ? 'selected' : '' }}>(8) Direktur
                                                            </option>
                                                        </select>
                                                    </td>

                                                    <td>
                                                        <select name="pegawai[{{ $p->id }}][pendidikan_formal]"
                                                            class="form-select form-select-sm text-center pendidikan-formal"
                                                            {{ $rowDisabled ? 'disabled' : '' }}>
                                                            <option value="0"
                                                                {{ $p->pendidikan_formal == 0 ? 'selected' : '' }}>-- Pilih
                                                                Pendidikan --</option>
                                                            <option value="1"
                                                                {{ $p->pendidikan_formal == 1 ? 'selected' : '' }}>SD
                                                            </option>
                                                            <option value="2"
                                                                {{ $p->pendidikan_formal == 2 ? 'selected' : '' }}>SMP
                                                            </option>
                                                            <option value="3"
                                                                {{ $p->pendidikan_formal == 3 ? 'selected' : '' }}>SMA/SMU
                                                            </option>
                                                            <option value="4"
                                                                {{ $p->pendidikan_formal == 4 ? 'selected' : '' }}>D1
                                                            </option>
                                                            <option value="5"
                                                                {{ $p->pendidikan_formal == 5 ? 'selected' : '' }}>D3
                                                            </option>
                                                            <option value="6"
                                                                {{ $p->pendidikan_formal == 6 ? 'selected' : '' }}>S1/D4
                                                            </option>
                                                            <option value="7"
                                                                {{ $p->pendidikan_formal == 7 ? 'selected' : '' }}>Dokter
                                                                Umum / Dokter Gigi / Apoteker / Ners</option>
                                                            <option value="8"
                                                                {{ $p->pendidikan_formal == 8 ? 'selected' : '' }}>S2
                                                            </option>
                                                            <option value="9"
                                                                {{ $p->pendidikan_formal == 9 ? 'selected' : '' }}>Dokter
                                                                Spesialis</option>
                                                            <option value="10"
                                                                {{ $p->pendidikan_formal == 10 ? 'selected' : '' }}>S3 /
                                                                Subspesialis / Konsultan</option>
                                                        </select>
                                                    </td>

                                                    <td>
                                                        <input type="text"
                                                            name="pegawai[{{ $p->id }}][pendidikan_non_formal]"
                                                            class="form-control form-control-sm text-center pendidikan-non-formal"
                                                            value="{{ $p->pendidikan_non_formal }}" readonly>
                                                    </td>

                                                    {{-- Gaji Pokok: tampil format ribuan tanpa desimal, disimpan sebagai integer murni --}}
                                                    <td>
                                                        <input type="text"
                                                            name="pegawai[{{ $p->id }}][gaji_pokok]"
                                                            class="form-control form-control-sm text-center gaji-pokok"
                                                            value="{{ $p->gaji_pokok_display }}" readonly>
                                                    </td>

                                                    <td>
                                                        <input type="text" name="pegawai[{{ $p->id }}][risk]"
                                                            class="form-control form-control-sm text-center bg-light risk"
                                                            value="{{ $p->risk }}" readonly>
                                                    </td>

                                                    <td>
                                                        <input type="text"
                                                            name="pegawai[{{ $p->id }}][emergency]"
                                                            class="form-control form-control-sm text-center bg-light emergency"
                                                            value="{{ $p->emergency }}" readonly>
                                                    </td>

                                                    <td>
                                                        <input type="text" name="pegawai[{{ $p->id }}][cuti]"
                                                            class="form-control form-control-sm text-center cuti"
                                                            value="{{ $p->cuti }}"
                                                            {{ $rowDisabled ? 'disabled' : '' }}>
                                                    </td>

                                                    <td>
                                                        <input type="text" name="pegawai[{{ $p->id }}][izin]"
                                                            class="form-control form-control-sm text-center izin"
                                                            value="{{ $p->izin }}"
                                                            {{ $rowDisabled ? 'disabled' : '' }}>
                                                    </td>

                                                    <td>
                                                        <input type="text"
                                                            name="pegawai[{{ $p->id }}][tanpa_izin]"
                                                            class="form-control form-control-sm text-center tanpa-izin"
                                                            value="{{ $p->tanpa_izin }}"
                                                            {{ $rowDisabled ? 'disabled' : '' }}>
                                                    </td>

                                                    <td>
                                                        <input type="text" name="pegawai[{{ $p->id }}][telat]"
                                                            class="form-control form-control-sm text-center telat"
                                                            value="{{ $p->telat }}"
                                                            {{ $rowDisabled ? 'disabled' : '' }}>
                                                    </td>

                                                    <td>
                                                        <select name="pegawai[{{ $p->id }}][sikap]"
                                                            class="form-select form-select-sm text-center sikap"
                                                            {{ $rowDisabled ? 'disabled' : '' }}>
                                                            <option value="0" {{ $p->sikap == 0 ? 'selected' : '' }}>
                                                                -</option>
                                                            <option value="1" {{ $p->sikap == 1 ? 'selected' : '' }}>
                                                                Terbukti mencuri di RS (-100%)</option>
                                                            <option value="2" {{ $p->sikap == 2 ? 'selected' : '' }}>
                                                                Terbukti narkoba/miras/judi/mesum di RS (-100%)</option>
                                                            <option value="3" {{ $p->sikap == 3 ? 'selected' : '' }}>
                                                                Merokok di lingkungan RS (-25%)</option>
                                                            <option value="4" {{ $p->sikap == 4 ? 'selected' : '' }}>
                                                                Berkelahi di lingkungan RS (-50%)</option>
                                                            <option value="5" {{ $p->sikap == 5 ? 'selected' : '' }}>
                                                                Mogok kerja/menghasut (-100%)</option>
                                                        </select>
                                                    </td>

                                                    <td class="fw-bold">
                                                        <input type="text" name="pegawai[{{ $p->id }}][jumlah]"
                                                            class="form-control form-control-sm text-center jumlah-pengurang jumlah"
                                                            value="{{ $p->jumlah }}" readonly>
                                                    </td>

                                                    <td>
                                                        <input type="text"
                                                            name="pegawai[{{ $p->id }}][keterangan]"
                                                            class="form-control form-control-sm"
                                                            value="{{ $p->keterangan }}"
                                                            {{ $rowDisabled ? 'readonly' : '' }}>
                                                    </td>

                                                    <td class="fw-bold text-primary">
                                                        <input type="text"
                                                            name="pegawai[{{ $p->id }}][jumlah_akhir]"
                                                            class="form-control form-control-sm text-center jumlah-akhir"
                                                            value="{{ $p->jumlah_akhir }}" readonly>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3 p-3 bg-light border rounded shadow-sm">
                                    {{-- Catatan Revisi dari Manajemen --}}
                                    @if ($periodeItem->status_pengajuan == 'revisi' && $periodeItem->catatan_revisi)
                                        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                                            <i data-feather="alert-triangle" class="flex-shrink-0 mt-1"></i>
                                            <div>
                                                <strong>Catatan Revisi dari Manajemen:</strong>
                                                <p class="mb-0 text-danger">{{ $periodeItem->catatan_revisi }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </>

                                <div class="d-flex gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary px-4 btn-draft"
                                        {{ $periodeItem->disable_input ? 'disabled' : '' }}>
                                        <i data-feather="save" class="me-1 small"></i>
                                        Simpan Draft
                                    </button>

                                    <button type="submit" class="btn btn-success px-4 btn-submit"
                                        formaction="{{ route('index.scoring.submit') }}"
                                        {{ $periodeItem->disable_input ? 'disabled' : '' }}>
                                        <i data-feather="check-circle" class="me-1"></i>
                                        Kunci & Submit Pembagian
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i data-feather="clipboard" style="width:70px;height:70px" class="text-secondary mb-3"></i>
                <h5 class="mb-2">Belum Ada Pengajuan</h5>
                <p class="text-muted mb-0">
                    Pilih periode pengajuan terlebih dahulu, kemudian klik
                    <strong>Buat Pengajuan</strong>.
                </p>
            </div>
        </div>
    @endif

    {{-- Script Kalkulasi Index Scoring + Validasi Submit --}}
    <script>
        function getPengurangSikap(kode) {
            switch (parseInt(kode)) {
                case 1:
                    return 100;
                case 2:
                    return 100;
                case 3:
                    return 25;
                case 4:
                    return 50;
                case 5:
                    return 100;
                default:
                    return 0;
            }
        }

        // Mengubah "3.057.300" atau "3057300,50" menjadi angka murni untuk perhitungan
        function toNumber(value) {
            if (value === null || value === undefined) return 0;
            value = value.toString().replace(/\./g, '');
            value = value.replace(/,/g, '.');
            return parseFloat(value) || 0;
        }

        function round2(number) {
            return Math.round(number * 100) / 100;
        }

        function hitungBaris(row) {
            let jabatan = toNumber(row.querySelector('.jabatan').value);
            let pendidikanFormal = toNumber(row.querySelector('.pendidikan-formal').value);
            let pendidikanNonFormal = toNumber(row.querySelector('.pendidikan-non-formal').value);
            let gajiPokok = toNumber(row.querySelector('.gaji-pokok').value);
            let risk = toNumber(row.querySelector('.risk').value);
            let emergency = toNumber(row.querySelector('.emergency').value);
            let cuti = toNumber(row.querySelector('.cuti').value);
            let izin = toNumber(row.querySelector('.izin').value);
            let tanpaIzin = toNumber(row.querySelector('.tanpa-izin').value);
            let telat = toNumber(row.querySelector('.telat').value);
            let kodeSikap = toNumber(row.querySelector('.sikap').value);

            let basicIndex = gajiPokok / 100000;
            let scoreBasic = basicIndex * 1;

            let competency = pendidikanFormal + pendidikanNonFormal;
            let scoreCompetency = competency * 3;

            let scoreRisk = risk * 3;
            let scoreEmergency = emergency * 3;
            let scorePosition = jabatan * 3;

            let performanceIndex = basicIndex * 2;
            let scorePerformance = performanceIndex * 4;

            let totalScore =
                scoreBasic + scoreCompetency + scoreRisk + scoreEmergency + scorePosition + scorePerformance;

            let persenPengurang = 0;

            let totalCutiIzin = cuti + izin;
            let pengurangCutiIzin = 0;
            if (totalCutiIzin > 0) {
                if (totalCutiIzin < 4) pengurangCutiIzin = 8;
                else if (totalCutiIzin <= 8) pengurangCutiIzin = 12;
                else if (totalCutiIzin <= 12) pengurangCutiIzin = 30;
                else pengurangCutiIzin = 50;
            }
            persenPengurang += pengurangCutiIzin;

            let pengurangTanpaIzin = 0;
            if (tanpaIzin >= 1 && tanpaIzin <= 3) pengurangTanpaIzin = 20;
            else if (tanpaIzin >= 4 && tanpaIzin <= 6) pengurangTanpaIzin = 40;
            else if (tanpaIzin >= 7 && tanpaIzin < 30) pengurangTanpaIzin = Math.ceil(tanpaIzin / 7) * 40;
            else if (tanpaIzin >= 30) pengurangTanpaIzin = 100;
            persenPengurang += pengurangTanpaIzin;

            let pengurangTelat = Math.floor(telat / 7) * 3;
            persenPengurang += pengurangTelat;

            let pengurangSikap = getPengurangSikap(kodeSikap);
            persenPengurang += pengurangSikap;

            persenPengurang = Math.min(persenPengurang, 100);

            let jumlahAkhir = totalScore * (1 - persenPengurang / 100);

            row.querySelector('.jumlah').value = round2(totalScore);
            row.querySelector('.jumlah-akhir').value = round2(jumlahAkhir);
        }

        document.addEventListener('DOMContentLoaded', function() {

            // Kalkulasi otomatis tiap baris
            document.querySelectorAll('tbody tr').forEach(function(row) {
                row.querySelectorAll(
                    '.jabatan, .pendidikan-formal, .cuti, .izin, .tanpa-izin, .telat, .sikap'
                ).forEach(function(input) {
                    input.addEventListener('change', function() {
                        hitungBaris(row);
                    });
                });
                hitungBaris(row);
            });

            // Validasi kelengkapan + konfirmasi sebelum submit final
            document.querySelectorAll('form').forEach(function(form) {

                const btnSubmit = form.querySelector('.btn-submit');
                if (!btnSubmit) return;

                btnSubmit.addEventListener('click', function(e) {

                    const belumLengkap = [];

                    form.querySelectorAll('tbody tr').forEach(function(row) {

                        const jabatanSelect = row.querySelector('.jabatan');
                        const formalSelect = row.querySelector('.pendidikan-formal');

                        // Baris terkunci (tidak punya id_petugas / form sudah locked) -> lewati
                        if (!jabatanSelect || jabatanSelect.disabled) return;

                        const namaEl = row.querySelector('td:nth-child(2) strong');
                        const nama = namaEl ? namaEl.textContent.trim() : 'Pegawai';

                        if (jabatanSelect.value === '0' || formalSelect.value === '0') {
                            belumLengkap.push(nama);
                        }
                    });

                    if (belumLengkap.length > 0) {
                        e.preventDefault();
                        alert(
                            'Tidak bisa submit final. Lengkapi dulu Jabatan & Pendidikan Formal untuk:\n- ' +
                            belumLengkap.join('\n- ')
                        );
                        return false;
                    }

                    const konfirmasi = confirm(
                        'Setelah disimpan final, data TIDAK BISA DIEDIT kembali \n\n' +
                        'Pastikan seluruh data sudah benar. Lanjutkan submit final?'
                    );

                    if (!konfirmasi) {
                        e.preventDefault();
                        return false;
                    }
                });
            });

        });
    </script>
@endsection
