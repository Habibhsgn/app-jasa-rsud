@extends('layouts.app')

@section('title', 'Master Ruangan & Persentase')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><strong>Master Ruangan & Persentase</strong></h1>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        {{-- BAGIAN KIRI: FORM TAMBAH RUANGAN BARU --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Tambah Ruangan Baru</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('master.ruangan.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Ruangan</label>
                            <input type="text" name="nama_ruangan" class="form-control" placeholder="Contoh: IGD" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Tambah Ruangan</button>
                    </form>
                    <div class="mt-3 text-muted small">
                        *Ruangan yang baru ditambahkan akan memiliki persentase default 0%. Silakan atur pada tabel di samping.
                    </div>
                </div>
            </div>
        </div>

        {{-- BAGIAN KANAN: TABEL BULK EDIT PERSENTASE --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Atur Persentase Jasa (Harus 100%)</h5>
                    <span class="badge bg-{{ round($totalPersen, 2) == 100.00 ? 'success' : 'danger' }} fs-6">
                        Total Saat Ini: {{ round($totalPersen, 2) }}%
                    </span>
                </div>
                <div class="card-body">
                    <form action="{{ route('master.ruangan.updateBulk') }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-secondary text-center">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Nama Ruangan</th>
                                        <th width="25%">Persentase (%)</th>
                                        <th width="10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ruangan as $index => $r)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <input type="hidden" name="ruangan_id[]" value="{{ $r->id }}">
                                                <input type="text" name="nama_ruangan[]" class="form-control form-control-sm text-uppercase" value="{{ $r->nama_ruangan }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="persen_default[]" class="form-control form-control-sm text-center input-persen" value="{{ (float)$r->persen_default }}" required>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm" onclick="hapusRuangan({{ $r->id }}, '{{ $r->nama_ruangan }}')">
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold bg-light">
                                        <td colspan="2" class="text-end">TOTAL KESELURUHAN:</td>
                                        <td class="text-center">
                                            <span id="indikatorTotal" class="{{ round($totalPersen, 2) == 100.00 ? 'text-success' : 'text-danger' }}">
                                                {{ (float)$totalPersen }}%
                                            </span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-danger fw-bold" id="pesanError" style="display: {{ round($totalPersen, 2) == 100.00 ? 'none' : 'block' }}">
                                *Tombol simpan mati. Total harus pas 100%.
                            </small>
                            <button type="submit" id="btnSimpanMassal" class="btn btn-success px-4" {{ round($totalPersen, 2) == 100.00 ? '' : 'disabled' }}>
                                💾 Simpan Perubahan Massal
                            </button>
                        </div>
                    </form>

                    {{-- Form Rahasia untuk Delete --}}
                    <form id="formDelete" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let persenInputs = document.querySelectorAll('.input-persen');
            let indikatorTotal = document.getElementById('indikatorTotal');
            let btnSimpan = document.getElementById('btnSimpanMassal');
            let pesanError = document.getElementById('pesanError');

            // Kalkulasi real-time saat angka persen diubah
            persenInputs.forEach(input => {
                input.addEventListener('input', function() {
                    let total = 0;
                    persenInputs.forEach(p => {
                        total += parseFloat(p.value) || 0;
                    });

                    // Format ke 2 desimal
                    let totalFormatted = total.toFixed(2);
                    indikatorTotal.innerText = totalFormatted + '%';

                    // Cek jika pas 100%
                    if (totalFormatted === "100.00") {
                        indikatorTotal.className = 'text-success';
                        btnSimpan.disabled = false;
                        pesanError.style.display = 'none';
                    } else {
                        indikatorTotal.className = 'text-danger';
                        btnSimpan.disabled = true;
                        pesanError.style.display = 'block';
                    }
                });
            });
        });

        // Konfirmasi Hapus Ruangan
        function hapusRuangan(id, nama) {
            if (confirm(`Peringatan: Menghapus ruangan "${nama}" mungkin mengubah total persentase. Yakin ingin menghapus?`)) {
                let formDelete = document.getElementById('formDelete');
                formDelete.action = `/master-ruangan/${id}`;
                formDelete.submit();
            }
        }
    </script>
@endsection