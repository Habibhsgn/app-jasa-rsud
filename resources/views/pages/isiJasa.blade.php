@extends('layouts.app')

@section('title', 'Isi Jasa Pegawai')

@section('content')
    <h1 class="h3 mb-3"><strong>Isi Jasa Pegawai</strong></h1>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Form Filter Pencarian --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('karu.jasa') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Pilih Bulan</label>
                        <select name="bulan" class="form-select">
                            @php
                                $namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            @endphp
                            @foreach ($namaBulan as $index => $nama)
                                @php $val = str_pad($index + 1, 2, '0', STR_PAD_LEFT); @endphp
                                <option value="{{ $val }}" {{ $bulan == $val ? 'selected' : '' }}>
                                    {{ $nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Pilih Tahun</label>
                        <select name="tahun" class="form-select">
                            @php $thnSekarang = date('Y'); @endphp
                            @foreach (range($thnSekarang - 2, $thnSekarang + 1) as $thn)
                                <option value="{{ $thn }}" {{ $tahun == $thn ? 'selected' : '' }}>
                                    {{ $thn }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <button type="submit" class="btn btn-primary">
                            <i class="align-middle" data-feather="search"></i> Cari Data
                        </button>
                        <a href="{{ route('karu.jasa') }}" class="btn btn-outline-secondary">
                            Bulan Ini
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Content --}}
    @if($data->isEmpty())
        {{-- Tampilan jika data tidak ditemukan --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <i class="text-secondary" style="width: 64px; height: 64px;" data-feather="alert-circle"></i>
                </div>
                <h4 class="text-muted">Data Tidak Ditemukan</h4>
                <p class="text-secondary mb-0">
                    Belum ada penugasan pengisian jasa untuk periode 
                    <strong>{{ $namaBulan[(int)$bulan - 1] }} {{ $tahun }}</strong>.
                </p>
            </div>
        </div>
    @else
        {{-- Loop Data Jasa Ruangan --}}
        @foreach ($data as $item)
            @php
                $isLocked = in_array($item->status, ['verifikasi', 'selesai']);
            @endphp

            <div class="card mb-4 border-{{ $item->status == 'revisi' ? 'danger' : 'default' }} shadow-sm"
                data-nominal="{{ $item->nominal }}">
                <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
                    <div>
                        <span class="text-muted small">Periode:</span> <strong>{{ $item->periode->periode ?? '-' }}</strong> |
                        <span class="text-muted small">Ruangan:</span> <strong>{{ $item->ruangan->nama_ruangan ?? '-' }}</strong> |
                        <span class="text-muted small">Alokasi:</span> <strong class="text-primary">Rp {{ number_format($item->nominal, 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        @if ($item->status == 'verifikasi')
                            <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                        @elseif($item->status == 'selesai')
                            <span class="badge bg-success">Disetujui / Selesai</span>
                        @elseif($item->status == 'revisi')
                            <span class="badge bg-danger">Perlu Revisi</span>
                        @else
                            <span class="badge bg-secondary">Draft</span>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    @if ($item->status == 'revisi' && $item->catatan)
                        <div class="alert alert-danger mb-3">
                            <strong><i data-feather="info" class="me-1"></i> Catatan Revisi:</strong><br>
                            {{ $item->catatan }}
                        </div>
                    @endif
                    
                    <form action="{{ route('karu.jasa.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="jasa_ruangan_id" value="{{ $item->id }}">

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Pegawai</th>
                                        <th>Jabatan</th>
                                        <th width="12%">Persen (%)</th>
                                        <th width="18%">Nominal (Rp)</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($item->pegawai as $p)
                                        @php
                                            $jp = \App\Models\JasaPegawai::where('jasa_ruangan_id', $item->id)
                                                ->where('pegawai_id', $p->id)
                                                ->first();
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $p->nama }}</strong><br>
                                                <small class="text-muted">ID: {{ $p->id_petugas }}</small>
                                                <input type="hidden" name="pegawai_id[]" value="{{ $p->id }}">
                                            </td>
                                            <td>{{ $p->jabatan }}</td>
                                            <td>
                                                <input type="number" step="0.01" name="persen[]" class="form-control persen"
                                                    value="{{ $jp ? (float) $jp->persen : '' }}"
                                                    {{ $isLocked ? 'readonly' : '' }} required>
                                            </td>
                                            <td>
                                                <input type="text" name="nominal[]" class="form-control nominal text-end fw-bold"
                                                    value="{{ $jp ? number_format($jp->nominal, 0, ',', '.') : '' }}"
                                                    {{ $isLocked ? 'readonly' : '' }} required>
                                            </td>
                                            <td>
                                                <input type="text" name="keterangan[]" class="form-control"
                                                    value="{{ $jp ? $jp->keterangan : '' }}"
                                                    {{ $isLocked ? 'readonly' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 p-3 bg-light border rounded shadow-sm">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <p class="mb-1">Total Persen: <span class="totalPersen fw-bold text-primary">0</span> %</p>
                                    <p class="mb-0">Total Terbagi: <span class="fw-bold text-success">Rp <span class="totalNominal">0</span></span></p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h4 class="mb-0">Sisa Alokasi: <span class="sisaNominal fw-bold text-danger">0</span></h4>
                                </div>
                            </div>
                        </div>

                        @if (!$isLocked)
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i data-feather="save" class="me-1 small"></i> Simpan Draft
                                </button>
                            </div>
                        @endif
                    </form>

                    @if (!$isLocked)
                        <form action="{{ route('karu.jasa.submit') }}" method="POST" class="mt-2 form-submit">
                            @csrf
                            <input type="hidden" name="jasa_ruangan_id" value="{{ $item->id }}">
                            <button type="submit" class="btn btn-success btn-submit w-100 py-2" disabled>
                                <i data-feather="check-circle" class="me-1"></i> Kunci & Submit Pembagian
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    {{-- Script Kalkulasi --}}
    <script>
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }

        function parseRupiah(str) {
            let clean = str.toString().replace(/[^0-9]/g, '');
            return parseInt(clean) || 0;
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.card[data-nominal]').forEach(card => {
                let persenInputs = card.querySelectorAll('.persen');
                let nominalInputs = card.querySelectorAll('.nominal');
                let totalPersenText = card.querySelector('.totalPersen');
                let totalNominalText = card.querySelector('.totalNominal');
                let sisaNominalText = card.querySelector('.sisaNominal');
                let btnSubmit = card.querySelector('.btn-submit');
                let nominalRuangan = parseInt(card.dataset.nominal) || 0;

                function hitungTotal() {
                    let totalPersen = 0;
                    let totalNominal = 0;

                    nominalInputs.forEach((nomInput, i) => {
                        let nominal = parseRupiah(nomInput.value);
                        let persen = parseFloat(persenInputs[i].value) || 0;
                        totalNominal += nominal;
                        totalPersen += persen;
                    });

                    let sisa = nominalRuangan - totalNominal;

                    totalPersenText.innerText = totalPersen.toFixed(2);
                    totalNominalText.innerText = formatRupiah(totalNominal);
                    sisaNominalText.innerText = 'Rp ' + formatRupiah(sisa);

                    if (sisa === 0) {
                        sisaNominalText.classList.replace('text-danger', 'text-success');
                        if(btnSubmit) btnSubmit.disabled = false;
                    } else {
                        sisaNominalText.classList.replace('text-success', 'text-danger');
                        if(btnSubmit) btnSubmit.disabled = true;
                    }
                }

                persenInputs.forEach((input, i) => {
                    input.addEventListener('input', function() {
                        let persen = parseFloat(this.value) || 0;
                        let nominal = Math.round((persen / 100) * nominalRuangan);
                        nominalInputs[i].value = formatRupiah(nominal);
                        hitungTotal();
                    });
                });

                nominalInputs.forEach((input, i) => {
                    input.addEventListener('input', function() {
                        let nominal = parseRupiah(this.value);
                        this.value = formatRupiah(nominal);
                        let persen = (nominal / nominalRuangan) * 100;
                        persenInputs[i].value = persen.toFixed(2);
                        hitungTotal();
                    });
                });

                hitungTotal();

                let formSubmit = card.querySelector('.form-submit');
                if (formSubmit) {
                    formSubmit.addEventListener('submit', function(e) {
                        if (!confirm('Apakah Anda yakin pembagian sudah pas? Sisa dana Rp 0. Data yang disubmit TIDAK BISA diubah lagi.')) {
                            e.preventDefault();
                        }
                    });
                }
            });
        });
    </script>
@endsection