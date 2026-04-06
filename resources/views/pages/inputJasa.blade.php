@extends('layouts.app')
@section('title', 'Input Jasa & Generate')

@section('content')
    <h1 class="h3 mb-3"><strong>Input Jasa & Pembagian Ruangan</strong></h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">

            {{-- FORM RAHASIA UNTUK DELETE DRAFT (Diletakkan di luar form utama agar tidak nested error) --}}
            @if (isset($periode) && $periode->status == 'draft')
                <form id="formDelete" action="{{ route('jasa.destroyPeriode', $periode->id) }}" method="POST"
                    style="display: none;">
                    @csrf
                    @method('DELETE')
                </form>
            @endif

            {{-- FORM TOTAL JASA --}}
            <form id="formSimpanTotal" action="{{ route('jasa.storeTotal') }}" method="POST">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Periode (Bulan)</label>
                        <input type="month" name="periode" class="form-control" value="{{ $periode->periode ?? '' }}"
                            {{ $periode ? 'readonly' : '' }} required>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Total Jasa (Rp)</label>
                        <input type="text" name="total_jasa" class="form-control fw-bold fs-5"
                            value="{{ isset($periode) ? number_format($periode->total_jasa, 0, ',', '.') : '' }}"
                            {{ $periode ? 'readonly' : '' }} required>
                    </div>

                    <div class="col-md-4">
                        @if (!isset($periode))
                            <button type="submit" form="formSimpanTotal" class="btn btn-primary w-100 py-2">
                                ⚡ Simpan & Generate Otomatis
                            </button>
                        @else
                            @if ($periode->status == 'draft')
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-secondary w-100" disabled>Telah
                                        Digenerate</button>

                                    {{-- Tombol Batalkan Draft (Memanggil formDelete di atas) --}}
                                    <button type="submit" form="formDelete" class="btn btn-danger h-100"
                                        onclick="return confirm('Yakin ingin membatalkan & menghapus draft ini secara permanen?')"
                                        title="Hapus Draft & Ulangi">
                                        Hapus Draft
                                    </button>
                                </div>
                            @else
                                <button type="button" class="btn btn-success w-100 py-2" disabled>Telah Diproses
                                    KARU</button>
                            @endif
                        @endif
                    </div>
                </div>
            </form>

            {{-- HASIL PEMBAGIAN OTOMATIS --}}
            @if ($periode && $dataPembagian->count() > 0)
                <hr class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        @if ($periode->status == 'draft')
                            Hasil Pembagian Otomatis (Master)
                        @else
                            Monitoring Progres Pengisian KARU
                        @endif
                    </h5>
                    <span class="badge bg-{{ $periode->status == 'draft' ? 'secondary' : 'warning text-dark' }} fs-6">
                        Status Master: {{ strtoupper(str_replace('_', ' ', $periode->status)) }}
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th>Nama Ruangan</th>
                                <th width="15%" class="text-center">Persentase</th>
                                <th width="20%" class="text-end">Nominal Jasa (Rp)</th>
                                <th width="15%" class="text-center">Status KARU</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalPersen = 0;
                                $totalNominal = 0;
                            @endphp
                            @foreach ($dataPembagian as $index => $item)
                                @php
                                    $totalPersen += $item->persen;
                                    $totalNominal += $item->nominal;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="fw-bold">{{ $item->ruangan->nama_ruangan ?? 'Ruangan Dihapus' }}</td>
                                    <td class="text-center">{{ (float) $item->persen }}%</td>
                                    <td class="text-end">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        {{-- Indikator Status Tiap Ruangan --}}
                                        @if ($item->status == 'draft')
                                            <span class="badge bg-secondary">Draft</span>
                                        @elseif($item->status == 'selesai' || $item->status == 'verifikasi')
                                            <span class="badge bg-success">✔ Selesai</span>
                                        @elseif($item->status == 'revisi')
                                            <span class="badge bg-danger">Revisi</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Proses Isi...</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold bg-light">
                                <td colspan="2" class="text-end">TOTAL KESELURUHAN:</td>
                                <td class="text-center text-primary">{{ (float) $totalPersen }}%</td>
                                <td class="text-end text-success fs-5">Rp {{ number_format($totalNominal, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-4 text-end">
                    @if ($periode->status == 'draft')
                        <button id="btnSelesai" class="btn btn-success px-5 py-2 fw-bold" onclick="selesaiPembagian()">
                            <i class="align-middle" data-feather="send"></i> Kunci & Kirim Penugasan ke Seluruh KARU
                        </button>
                    @endif
                </div>
            @endif

        </div>
    </div>

    <script>
        // Auto-format pemisah ribuan saat mengetik di input Total Jasa
        let inputTotalJasa = document.querySelector('input[name="total_jasa"]');
        if (inputTotalJasa && !inputTotalJasa.readOnly) {
            inputTotalJasa.addEventListener('input', function(e) {
                let numericValue = this.value.replace(/[^0-9]/g, '');
                if (numericValue) {
                    this.value = new Intl.NumberFormat('id-ID').format(numericValue);
                } else {
                    this.value = '';
                }
            });
        }

        // Fungsi Submit Bulk ke KARU
        function selesaiPembagian() {
            if (!confirm(
                    'Apakah Anda yakin data pembagian otomatis ini sudah benar? Setelah dikirim, data tidak bisa dihapus lagi.'
                    )) return;

            let btn = document.getElementById('btnSelesai');
            btn.disabled = true;
            btn.innerText = 'Mengirim...';

            let periodeId = {{ $periode->id ?? 0 }};

            fetch(`/jasa/selesai-pembagian/${periodeId}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        alert('Sukses! Penugasan telah dikirim ke masing-masing KARU.');
                        location.reload();
                    } else {
                        alert(res.message);
                        btn.disabled = false;
                        btn.innerText = 'Kunci & Kirim Penugasan ke Seluruh KARU';
                    }
                }).catch(err => {
                    alert('Terjadi kesalahan jaringan.');
                    btn.disabled = false;
                    btn.innerText = 'Kunci & Kirim Penugasan ke Seluruh KARU';
                });
        }
    </script>
@endsection
