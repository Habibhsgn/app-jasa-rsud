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
                            <input type="text" name="nama_ruangan" class="form-control text-uppercase"
                                placeholder="Contoh: IGD" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bidang</label>

                            <select name="bidang_id" class="form-select" required>
                                <option value="">-- Pilih Bidang --</option>

                                @foreach ($bidang as $b)
                                    <option value="{{ $b->id }}">
                                        {{ $b->nama_bidang }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Risk Index</label>
                            <select name="resiko" class="form-select" required>
                                <option value="">-- Pilih Risk Index --</option>
                                <option value="1">1 - Administratif / Perkantoran</option>
                                <option value="2">2 - Rawat Jalan, Gizi, IPSRS, Rehab Medik, Diagnostik, CSSD,
                                    Ambulance, HD, Farmasi, UTDRS</option>
                                <option value="4">4 - Rawat Inap, Laboratorium PK/PA, VK</option>
                                <option value="6">6 - Isolasi, Bedah Sentral, IGD, ICU, HCU, ICCU, NICU, PICU, Poli
                                    Paru, Laundry, Forensik, Radiologi, IPAL, Kemoterapi</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Emergency Index</label>
                            <select name="emergency" class="form-select" required>
                                <option value="">-- Pilih Emergency Index --</option>
                                <option value="1">1 - Administrasi Perkantoran</option>
                                <option value="2">2 - Administrasi Keuangan, Gizi, Farmasi, Rawat Jalan, Rehab Medik,
                                    IPSRS, Gigi & Mulut, Forensik</option>
                                <option value="4">4 - Rawat Inap, Laboratorium PK/PA, CSSD, IPAL, Hemodialisa,
                                    Kemoterapi, UTDRS</option>
                                <option value="6">6 - Bedah Central, VK, Rawat Inap Menular, ICU/ICCU/NICU/PICU, IGD,
                                    Laundry, Radiologi</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Tambah Ruangan
                        </button>
                    </form>

                    <div class="mt-3 text-muted small">
                        *Ruangan yang baru ditambahkan akan memiliki persentase default <strong>0%</strong>. Silakan atur
                        persentase pada tabel di samping.
                    </div>
                </div>
            </div>
        </div>

        {{-- BAGIAN KANAN: TABEL BULK EDIT PERSENTASE --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Master Ruangan</h5>

                    <span class="badge bg-{{ round($totalPersen, 2) == 100 ? 'success' : 'danger' }} fs-6">
                        Total Persentase Aktif : {{ number_format($totalPersen, 2) }}%
                    </span>
                </div>

                <div class="card-body">

                    <form action="{{ route('master.ruangan.updateBulk') }}" method="POST">
                        @csrf

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">

                                <thead class="table-secondary text-center">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Nama Ruangan</th>
                                        <th width="15%">Persentase (%)</th>
                                        <th width="12%">Risk</th>
                                        <th width="12%">Emergency</th>
                                        <th width="18%">Bidang</th>
                                        <th width="15%">Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($ruangan as $index => $r)
                                        <tr class="{{ !$r->is_active ? 'table-secondary' : '' }}">

                                            <td class="text-center">{{ $index + 1 }}</td>

                                            <td>
                                                <input type="hidden" name="ruangan_id[]" value="{{ $r->id }}">

                                                <input type="text" name="nama_ruangan[]"
                                                    class="form-control form-control-sm text-uppercase"
                                                    value="{{ $r->nama_ruangan }}" {{ !$r->is_active ? 'readonly' : '' }}>
                                            </td>

                                            <td>
                                                <input type="number" step="0.01" name="persen_default[]"
                                                    class="form-control form-control-sm text-center input-persen"
                                                    value="{{ $r->persen_default }}"
                                                    {{ !$r->is_active ? 'readonly' : '' }}>
                                            </td>

                                            <td>
                                                <select name="resiko[]" class="form-select form-select-sm"
                                                    {{ !$r->is_active ? 'disabled' : '' }}>
                                                    <option value="1" {{ $r->resiko == 0 ? 'selected' : '' }}>0
                                                    </option>
                                                    <option value="1" {{ $r->resiko == 1 ? 'selected' : '' }}>1
                                                    </option>
                                                    <option value="2" {{ $r->resiko == 2 ? 'selected' : '' }}>2
                                                    </option>
                                                    <option value="4" {{ $r->resiko == 4 ? 'selected' : '' }}>4
                                                    </option>
                                                    <option value="6" {{ $r->resiko == 6 ? 'selected' : '' }}>6
                                                    </option>
                                                </select>

                                                @if (!$r->is_active)
                                                    <input type="hidden" name="resiko[]" value="{{ $r->resiko }}">
                                                @endif
                                            </td>

                                            <td>
                                                <select name="emergency[]" class="form-select form-select-sm"
                                                    {{ !$r->is_active ? 'disabled' : '' }}>
                                                    <option value="1" {{ $r->emergency == 0 ? 'selected' : '' }}>0
                                                    </option>
                                                    <option value="1" {{ $r->emergency == 1 ? 'selected' : '' }}>1
                                                    </option>
                                                    <option value="2" {{ $r->emergency == 2 ? 'selected' : '' }}>2
                                                    </option>
                                                    <option value="4" {{ $r->emergency == 4 ? 'selected' : '' }}>4
                                                    </option>
                                                    <option value="6" {{ $r->emergency == 6 ? 'selected' : '' }}>6
                                                    </option>
                                                </select>

                                                @if (!$r->is_active)
                                                    <input type="hidden" name="emergency[]" value="{{ $r->emergency }}">
                                                @endif
                                            </td>

                                            <td>

                                                <select name="bidang_id[]" class="form-select form-select-sm"
                                                    {{ !$r->is_active ? 'disabled' : '' }}>

                                                    @foreach ($bidang as $b)
                                                        <option value="{{ $b->id }}"
                                                            {{ $r->bidang_id == $b->id ? 'selected' : '' }}>

                                                            {{ $b->nama_bidang }}

                                                        </option>
                                                    @endforeach

                                                </select>

                                                @if (!$r->is_active)
                                                    <input type="hidden" name="bidang_id[]" value="{{ $r->bidang_id }}">
                                                @endif

                                            </td>

                                            <td class="text-center">

                                                <span class="badge bg-{{ $r->is_active ? 'success' : 'secondary' }}">
                                                    {{ $r->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </span>

                                                <br>

                                                <a href="{{ route('master.ruangan.toggleStatus', $r->id) }}"
                                                    class="btn btn-outline-primary btn-sm mt-2"
                                                    onclick="return confirm('Ubah status ruangan {{ $r->nama_ruangan }}?')">

                                                    {{ $r->is_active ? 'Nonaktifkan' : 'Aktifkan' }}

                                                </a>

                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>

                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="2" class="text-end">
                                            TOTAL PERSENTASE
                                        </td>

                                        <td class="text-center">
                                            <span id="indikatorTotal"
                                                class="{{ round($totalPersen, 2) == 100 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($totalPersen, 2) }}%
                                            </span>
                                        </td>

                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>

                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">

                            <small id="pesanError" class="text-danger fw-bold"
                                style="display: {{ round($totalPersen, 2) == 100 ? 'none' : 'block' }}">

                                *Total persentase seluruh ruangan aktif harus tepat 100%.

                            </small>

                            <button id="btnSimpanMassal" type="submit" class="btn btn-success"
                                {{ round($totalPersen, 2) == 100 ? '' : 'disabled' }}>

                                <i class="align-middle" data-feather="save"></i>
                                Simpan Perubahan

                            </button>

                        </div>

                    </form>

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
                if (confirm(
                        `Peringatan: Menghapus ruangan "${nama}" mungkin mengubah total persentase. Yakin ingin menghapus?`)) {
                    let formDelete = document.getElementById('formDelete');
                    formDelete.action = `/master-ruangan/${id}`;
                    formDelete.submit();
                }
            }

            function toggleStatus(id, nama) {

                if (!confirm('Ubah status ruangan "' + nama + '" ?')) {
                    return;
                }

                const form = document.getElementById('toggleStatusForm');
                form.action = "{{ url('master-ruangan') }}/" + id + "/toggle-status";
                form.submit();

            }
        </script>
    @endsection
