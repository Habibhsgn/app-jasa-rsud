@extends('layouts.app')

@section('title', 'Isi Jasa Pegawai')

@section('content')
    <h1 class="h3 mb-3"><strong>Isi Jasa Pegawai</strong></h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @foreach ($data as $item)
        @php
            // Kunci form HANYA JIKA statusnya verifikasi atau selesai.
            $isLocked = in_array($item->status, ['verifikasi', 'selesai']);
        @endphp

        <div class="card mb-4 border-{{ $item->status == 'revisi' ? 'danger' : 'default' }}"
            data-nominal="{{ $item->nominal }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>Periode:</strong> {{ $item->periode->periode ?? '-' }} |
                    <strong>Ruangan:</strong> {{ $item->ruangan->nama_ruangan ?? '-' }} |
                    <strong>Alokasi Ruangan:</strong> Rp {{ number_format($item->nominal, 0, ',', '.') }}
                </div>
                <div>
                    @if ($item->status == 'verifikasi')
                        <span class="badge bg-warning text-dark">Menunggu Verifikasi Manajemen</span>
                    @elseif($item->status == 'selesai')
                        <span class="badge bg-success">Disetujui / Selesai</span>
                    @elseif($item->status == 'revisi')
                        <span class="badge bg-danger">Perlu Revisi</span>
                    @else
                        <span class="badge bg-secondary">Draft (Bisa Diedit)</span>
                    @endif
                </div>
            </div>

            <div class="card-body">
                @if ($item->status == 'revisi' && $item->catatan)
                    <div class="alert alert-danger">
                        <strong>Catatan Revisi dari Manajemen:</strong><br>
                        {{ $item->catatan }}
                    </div>
                @endif
                
                <form action="{{ route('karu.jasa.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="jasa_ruangan_id" value="{{ $item->id }}">

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>ID Petugas</th>
                                    <th>Jabatan</th>
                                    <th width="15%">Persentase (%)</th>
                                    <th width="20%">Nominal (Rp)</th>
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
                                            {{ $p->nama }}
                                            <input type="hidden" name="pegawai_id[]" value="{{ $p->id }}">
                                        </td>
                                        <td>{{ $p->id_petugas }}</td>
                                        <td>{{ $p->jabatan }}</td>
                                        <td>
                                            <input type="number" step="0.01" name="persen[]" class="form-control persen"
                                                value="{{ $jp ? (float) $jp->persen : '' }}"
                                                {{ $isLocked ? 'readonly' : '' }} required>
                                        </td>
                                        <td>
                                            {{-- Atribut readonly dicopot di sini agar bisa input 2 arah --}}
                                            <input type="text" name="nominal[]" class="form-control nominal"
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

                    <div class="mt-2 p-3 bg-light border rounded">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Total Persen Terbagi: <span class="totalPersen text-primary">0</span> %</strong><br>
                                <strong>Total Nominal Terbagi: Rp <span class="totalNominal text-success">0</span></strong>
                            </div>
                            <div class="col-md-6 text-end fs-5">
                                {{-- Indikator Sisa Dana --}}
                                <strong>Sisa Alokasi: <span class="sisaNominal text-danger">0</span></strong>
                            </div>
                        </div>
                    </div>

                    @if (!$isLocked)
                        <button type="submit" class="btn btn-primary mt-3">Simpan Draft</button>
                    @endif
                </form>

                @if (!$isLocked)
                    <form action="{{ route('karu.jasa.submit') }}" method="POST" class="mt-2 form-submit">
                        @csrf
                        <input type="hidden" name="jasa_ruangan_id" value="{{ $item->id }}">
                        <button type="submit" class="btn btn-success btn-submit w-100" disabled>Kunci & Submit Pembagian</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach

    <script>
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }

        // Fungsi membersihkan titik dari string angka
        function parseRupiah(str) {
            let clean = str.toString().replace(/[^0-9]/g, '');
            return parseInt(clean) || 0;
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.card').forEach(card => {
                let persenInputs = card.querySelectorAll('.persen');
                let nominalInputs = card.querySelectorAll('.nominal');
                
                let totalPersenText = card.querySelector('.totalPersen');
                let totalNominalText = card.querySelector('.totalNominal');
                let sisaNominalText = card.querySelector('.sisaNominal');
                let btnSubmit = card.querySelector('.btn-submit');
                
                let nominalRuangan = parseInt(card.dataset.nominal) || 0;

                // Fungsi Kalkulasi Global
                function hitungTotal() {
                    let totalPersen = 0;
                    let totalNominal = 0;

                    // Hitung total dari input nominal yang ada (karena nominal adalah patokan mutlak)
                    nominalInputs.forEach((nomInput, i) => {
                        let nominal = parseRupiah(nomInput.value);
                        let persen = parseFloat(persenInputs[i].value) || 0;

                        totalNominal += nominal;
                        totalPersen += persen;
                    });

                    let sisa = nominalRuangan - totalNominal;

                    // Update UI Teks
                    totalPersenText.innerText = totalPersen.toFixed(2);
                    totalNominalText.innerText = formatRupiah(totalNominal);
                    
                    // Update warna & teks sisa dana
                    sisaNominalText.innerText = 'Rp ' + formatRupiah(sisa);
                    if (sisa === 0) {
                        sisaNominalText.classList.replace('text-danger', 'text-success');
                    } else {
                        sisaNominalText.classList.replace('text-success', 'text-danger');
                    }

                    // Syarat Mutlak Submit: Sisa harus persis 0
                    if (btnSubmit) {
                        if (sisa === 0) {
                            btnSubmit.disabled = false;
                        } else {
                            btnSubmit.disabled = true;
                        }
                    }
                }

                // Event Listener 1: Jika user merubah input PERSEN
                persenInputs.forEach((input, i) => {
                    input.addEventListener('input', function() {
                        if (this.value === '') {
                            nominalInputs[i].value = '';
                        } else {
                            let persen = parseFloat(this.value) || 0;
                            let nominal = Math.round((persen / 100) * nominalRuangan);
                            nominalInputs[i].value = formatRupiah(nominal);
                        }
                        hitungTotal();
                    });
                });

                // Event Listener 2: Jika user merubah input NOMINAL
                nominalInputs.forEach((input, i) => {
                    input.addEventListener('input', function() {
                        let nominal = parseRupiah(this.value);
                        
                        if (this.value === '') {
                            persenInputs[i].value = '';
                            this.value = '';
                        } else {
                            this.value = formatRupiah(nominal); // Format langsung saat ngetik
                            let persen = (nominal / nominalRuangan) * 100;
                            persenInputs[i].value = persen.toFixed(2); // Otomatis hitung persen
                        }
                        hitungTotal();
                    });
                });

                // Inisialisasi hitungan saat halaman pertama load
                hitungTotal();

                // Konfirmasi sebelum submit
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