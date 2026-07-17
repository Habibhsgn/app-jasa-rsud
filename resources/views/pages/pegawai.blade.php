@extends('layouts.app')

@section('title', 'Data Pegawai')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <strong>Master Data Pegawai per Ruangan</strong>
        </h1>

        @if (auth()->user()->role === 'admin')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPegawai"
                onclick="siapkanFormTambah()">
                ➕ Tambah Pegawai
            </button>
        @endif
    </div>

    @if (auth()->user()->role === 'admin')
        <div class="card border-primary mb-4">
            <div class="card-header bg-primary text-white">
                <strong>Import Data Pegawai</strong>
            </div>

            <div class="card-body">

                <div class="alert alert-warning mb-3">
                    <strong>Perhatian</strong>
                    <ul class="mb-0">
                        <li>Import akan <strong>menghapus seluruh data pegawai</strong> yang ada.</li>
                        <li>Data akan diganti dengan isi file Excel.</li>
                        <li>Pastikan seluruh ruangan pada Excel sudah tersedia pada master ruangan.</li>
                    </ul>
                </div>

                <form action="{{ route('pegawai.import') }}" method="POST" enctype="multipart/form-data">

                    @csrf

                    <div class="row">

                        <div class="col-md-8">

                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>

                        </div>

                        <div class="col-md-4 d-grid">

                            <button class="btn btn-success"
                                onclick="return confirm('Semua data pegawai akan diganti dengan data dari Excel. Lanjutkan?')">

                                📥 Import Excel

                            </button>

                        </div>

                    </div>

                </form>

            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('import_errors'))

        <div class="alert alert-danger">

            <strong>Import gagal.</strong>

            <ul class="mb-0 mt-2">

                @foreach (session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif

    {{-- LOOPING PER RUANGAN --}}
    @forelse ($pegawaiGrouped as $ruanganId => $pegawaiList)
        @php
            $r = $ruangan->firstWhere('id', $ruanganId);
            $namaRuangan = $r->nama_ruangan ?? 'TANPA RUANGAN';
        @endphp

        <div class="card mb-4">
            <div
                class="card-header bg-secondary text-white d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
                <h5 class="mb-0 text-white fw-bold">🏥 RUANGAN: {{ $namaRuangan }}</h5>

                <div class="d-flex align-items-center gap-2">
                    @if ($r && auth()->user()->role === 'admin')
                        <label class="text-white small mb-0">Resiko</label>
                        <select class="form-select form-select-sm select-resiko-emergency" style="width:70px"
                            data-id="{{ $r->id }}" data-field="resiko">
                            @foreach ([1, 2, 4, 6] as $opt)
                                <option value="{{ $opt }}" @selected($r->resiko == $opt)>{{ $opt }}
                                </option>
                            @endforeach
                        </select>

                        <label class="text-white small mb-0">Emergency</label>
                        <select class="form-select form-select-sm select-resiko-emergency" style="width:70px"
                            data-id="{{ $r->id }}" data-field="emergency">
                            @foreach ([1, 2, 4, 6] as $opt)
                                <option value="{{ $opt }}" @selected($r->emergency == $opt)>{{ $opt }}
                                </option>
                            @endforeach
                        </select>
                    @elseif ($r)
                        <span class="badge bg-light text-dark">Resiko: {{ $r->resiko }}</span>
                        <span class="badge bg-light text-dark">Emergency: {{ $r->emergency }}</span>
                    @endif

                    <span class="badge bg-light text-dark">{{ $pegawaiList->count() }} Pegawai</span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="25%">Nama Pegawai</th>
                                <th width="20%">ID Petugas</th>
                                <th>Jabatan</th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($pegawaiList as $index => $p)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="fw-bold">{{ $p->nama }}</td>

                                    <td width="20%">
                                        @if (in_array(auth()->user()->role, ['admin', 'karu', 'koordinator_karu']))
                                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="18"
                                                class="form-control form-control-sm input-id-petugas"
                                                data-id="{{ $p->id }}" value="{{ $p->id_petugas }}"
                                                placeholder="NIP / NIPTT / NIK">
                                        @else
                                            {{ $p->id_petugas ?: '-' }}
                                        @endif
                                    </td>

                                    <td>{{ $p->jabatan }}</td>

                                    <td class="text-center">
                                        @if (auth()->user()->role === 'admin')
                                            <button type="button" class="btn btn-warning btn-sm text-dark"
                                                data-bs-toggle="modal" data-bs-target="#modalPegawai"
                                                onclick="siapkanFormEdit(
                                            {{ $p->id }},
                                            '{{ $p->nama }}',
                                            '{{ $p->id_petugas }}',
                                            '{{ $p->jabatan }}',
                                            '{{ $p->ruangan_id }}'
                                        )">
                                                Edit
                                            </button>

                                            <form action="{{ route('pegawai.destroy', $p->id) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Yakin ingin menghapus pegawai ini?')">
                                                    Hapus
                                                </button>
                                            </form>
                                        @elseif(in_array(auth()->user()->role, ['karu', 'koordinator_karu']))
                                            <span class="badge bg-info">Dapat Mengisi ID Petugas</span>
                                        @else
                                            <span class="text-muted">Read Only</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @empty
        <div class="alert alert-info text-center py-4">
            Belum ada data pegawai yang terdaftar di sistem.
        </div>
    @endforelse

    {{-- MODAL --}}
    @if (auth()->user()->role === 'admin')
        <div class="modal fade" id="modalPegawai" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalPegawaiLabel">Tambah Pegawai</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form id="formPegawai" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">

                        <div class="modal-body">

                            <div class="mb-3">
                                <label>Nama</label>
                                <input type="text" name="nama" id="inputNama" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label>ID Petugas</label>
                                <input type="text" name="id_petugas" id="inputIdPetugas" class="form-control"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label>Jabatan</label>
                                <input type="text" name="jabatan" id="inputJabatan" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label>Ruangan</label>
                                <select name="ruangan_id" id="inputRuangan" class="form-select" required>
                                    <option value="">-- Pilih Ruangan --</option>
                                    @foreach ($ruangan as $r)
                                        <option value="{{ $r->id }}">{{ $r->nama_ruangan }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btnSubmit">Simpan</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    @endif


    {{-- SCRIPT --}}
    <script>
        function siapkanFormTambah() {
            document.getElementById('modalPegawaiLabel').innerText = 'Tambah Pegawai';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('formPegawai').action = "{{ route('pegawai.store') }}";

            document.getElementById('inputNama').value = '';
            document.getElementById('inputIdPetugas').value = '';
            document.getElementById('inputJabatan').value = '';
            document.getElementById('inputRuangan').value = '';
        }

        function siapkanFormEdit(id, nama, id_petugas, jabatan, ruangan_id) {
            document.getElementById('modalPegawaiLabel').innerText = 'Edit Pegawai';
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('formPegawai').action = `/pegawai/${id}`;

            document.getElementById('inputNama').value = nama;
            document.getElementById('inputIdPetugas').value = id_petugas;
            document.getElementById('inputJabatan').value = jabatan;
            document.getElementById('inputRuangan').value = ruangan_id;
        }

        document.querySelectorAll('.select-risk-emergency').forEach(function(select) {
            select.addEventListener('change', function() {
                const id = this.dataset.id;
                const field = this.dataset.field;
                const value = this.value;

                // ambil select pasangannya (risk/emergency) di card yang sama
                const card = this.closest('.card-header');
                const riskSelect = card.querySelector('[data-field="risk"]');
                const emergencySelect = card.querySelector('[data-field="emergency"]');

                this.disabled = true;

                fetch(`/ruangan/${id}/risk-emergency`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            risk: riskSelect.value,
                            emergency: emergencySelect.value
                        })
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (!res.success) {
                            alert('Gagal menyimpan perubahan.');
                        }
                    })
                    .catch(() => {
                        alert('Terjadi kesalahan saat menyimpan.');
                    })
                    .finally(() => {
                        this.disabled = false;
                    });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('.input-id-petugas').forEach(function(input) {

                let oldValue = input.value;

                input.addEventListener('focus', function() {
                    oldValue = this.value;
                });

                input.addEventListener('blur', function() {

                    if (this.value === oldValue) {
                        return;
                    }

                    saveIdPetugas(this);

                });

                input.addEventListener('keydown', function(e) {

                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.blur();
                    }

                });

            });

        });

        function saveIdPetugas(input) {

            const id = input.dataset.id;

            const idPetugas = input.value.trim();

            input.disabled = true;

            fetch(`/pegawai/${id}/id-petugas`, {

                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },

                    body: JSON.stringify({
                        id_petugas: idPetugas
                    })

                })
                .then(res => res.json())
                .then(res => {

                    if (res.success) {

                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');

                        setTimeout(() => {
                            input.classList.remove('is-valid');
                        }, 1500);

                    } else {

                        input.classList.add('is-invalid');

                    }

                })
                .catch(() => {

                    input.classList.add('is-invalid');

                })
                .finally(() => {

                    input.disabled = false;

                });

        }
        document.querySelectorAll('.input-id-petugas').forEach(function(input) {

            input.addEventListener('input', function() {

                // Hanya angka
                this.value = this.value.replace(/\D/g, '');

                // Maksimal 18 digit
                this.value = this.value.slice(0, 18);

            });

        });
    </script>

@endsection
