@extends('layouts.app')

@section('title', 'Data Pegawai')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0">
            <strong>Master Data Pegawai per Ruangan</strong>
        </h1>

        <div class="d-flex gap-2">
            @if (in_array(auth()->user()->role?->code, ['admin', 'karu', 'koordinator_karu']))
                <a href="{{ route('ruang-tunggu.index') }}" class="btn btn-outline-warning">
                    🚪 Ruang Tunggu
                </a>
            @endif

            @if (auth()->user()->role?->code === 'admin')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPegawai"
                    onclick="siapkanFormTambah()">
                    ➕ Tambah Pegawai
                </button>
                <a href="{{ route('pegawai.export') }}" class="btn btn-outline-success">
                    📤 Download Data Lengkap
                </a>
            @endif
        </div>
    </div>

    @if (auth()->user()->role?->code === 'admin')
        <div class="card border-primary mb-4">
            <div class="card-header bg-primary text-white">
                <strong>Import Data Pegawai</strong>
            </div>

            <div class="card-body">

                <div class="alert alert-info mb-3">
                    <strong>Cara kerja import</strong>
                    <ul class="mb-0">
                        <li>Baris dengan <strong>ID Petugas</strong> yang sudah ada di sistem akan
                            <strong>diperbarui</strong> (bukan dihapus-diganti).
                        </li>
                        <li>Baris tanpa ID Petugas dicocokkan otomatis berdasarkan nama + ruangan.</li>
                        <li>Baris yang tidak cocok dengan data manapun akan ditambahkan sebagai <strong>pegawai
                                baru</strong>.</li>
                        <li>Pegawai yang tidak muncul di file <strong>tidak akan dihapus</strong> — nonaktifkan manual
                            lewat tombol Hapus jika perlu.</li>
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
                                onclick="return confirm('Data pegawai akan diperbarui/ditambahkan sesuai isi file Excel. Lanjutkan?')">
                                📥 Import Excel </button>

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

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Gagal menyimpan data:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
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
                    @if ($r && auth()->user()->role?->code === 'admin')
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
                                <th width="22%">Nama Pegawai</th>
                                <th width="18%">ID Petugas</th>
                                <th>Jabatan</th>
                                <th>Resiko</th>
                                <th>Emergency</th>
                                <th width="20%" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($pegawaiList as $index => $p)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="fw-bold">{{ $p->nama }}</td>

                                    <td width="18%">
                                        @if (in_array(auth()->user()->role?->code, ['admin', 'karu', 'koordinator_karu']))
                                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="18"
                                                class="form-control form-control-sm input-id-petugas"
                                                data-id="{{ $p->id }}" value="{{ $p->id_petugas }}"
                                                placeholder="NIP / NIPTT / NIK">
                                        @else
                                            {{ $p->id_petugas ?: '-' }}
                                        @endif
                                    </td>

                                    <td>{{ $p->jabatan }}</td>
                                    <td>{{ $p->risk }}</td>
                                    <td>{{ $p->emergency }}</td>

                                    <td class="text-center">

                                        @if ($p->status === 'pindah')
                                            {{-- Pegawai sedang dalam proses pindah --}}
                                            <span class="badge bg-warning text-dark d-block mb-1">
                                                Menunggu diterima di
                                                {{ $p->ruanganTujuan->nama_ruangan ?? '-' }}
                                            </span>

                                            @if (auth()->user()->role?->code === 'admin')
                                                <form action="{{ route('pegawai.pindah.batal', $p->id) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm"
                                                        onclick="return confirm('Batalkan pengajuan pindah pegawai ini?')">
                                                        Batalkan Pindah
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">Menunggu konfirmasi</span>
                                            @endif
                                        @else
                                            @if (auth()->user()->role?->code === 'admin')
                                                <button type="button" class="btn btn-warning btn-sm text-dark"
                                                    data-bs-toggle="modal" data-bs-target="#modalPegawai"
                                                    onclick="siapkanFormEdit(
                                                        {{ $p->id }},
                                                        {{ Js::from($p->nama) }},
                                                        {{ Js::from($p->id_petugas) }},
                                                        {{ Js::from($p->jabatan) }},
                                                        {{ Js::from($p->ruangan_id) }},
                                                        {{ Js::from($p->pendidikan_non_formal) }},
                                                        {{ Js::from($p->gaji_pokok) }}
                                                    )">
                                                    Edit
                                                </button>

                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalPindah"
                                                    onclick="siapkanFormPindah({{ $p->id }}, '{{ $p->nama }}', {{ $p->ruangan_id }})">
                                                    Pindah
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
                                            @elseif(in_array(auth()->user()->role?->code, ['karu', 'koordinator_karu']))
                                                <span class="badge bg-info d-block mb-1">Dapat Mengisi ID
                                                    Petugas</span>

                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalPindah"
                                                    onclick="siapkanFormPindah({{ $p->id }}, '{{ $p->nama }}', {{ $p->ruangan_id }})">
                                                    Ajukan Pindah
                                                </button>
                                            @else
                                                <span class="text-muted">Read Only</span>
                                            @endif
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

    {{-- MODAL TAMBAH/EDIT PEGAWAI --}}
    @if (auth()->user()->role?->code === 'admin')
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
                                <input type="text" name="id_petugas" id="inputIdPetugas" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label>Jabatan</label>
                                <input type="text" name="jabatan" id="inputJabatan" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label>Ruangan</label>
                                <select name="ruangan_id" id="inputRuangan" class="form-select" required>
                                    <option value="">-- Pilih Ruangan --</option>
                                    @foreach ($ruanganDropdown as $rd)
                                        <option value="{{ $rd->id }}">
                                            {{ $rd->nama_ruangan }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- FIX: field ini wajib diisi menurut controller, tapi sebelumnya tidak ada di form --}}
                            <div class="mb-3">
                                <label>Pendidikan Non Formal</label>
                                <input type="text" name="pendidikan_non_formal" id="inputPendidikanNonFormal"
                                    class="form-control">
                            </div>

                            <div class="mb-3">
                                <label>Gaji Pokok</label>
                                <input type="number" name="gaji_pokok" id="inputGajiPokok" class="form-control"
                                    min="0" step="1000" required>
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

    {{-- MODAL PINDAH RUANGAN --}}
    @if (in_array(auth()->user()->role?->code, ['admin', 'karu', 'koordinator_karu']))
        <div class="modal fade" id="modalPindah" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Ajukan Pindah Ruangan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form id="formPindah" method="POST">
                        @csrf

                        <div class="modal-body">
                            <p>
                                Pegawai: <strong id="pindahNamaPegawai"></strong>
                            </p>

                            <div class="mb-3">
                                <label>Ruangan Tujuan</label>
                                {{-- FIX: pakai $ruanganDropdown (semua ruangan, tidak difilter role)
                                 bukan $ruangan (yang difilter untuk karu/koordinator_karu). --}}
                                <select name="ruangan_tujuan_id" id="pindahRuanganTujuan" class="form-select" required>
                                    <option value="">-- Pilih Ruangan Tujuan --</option>
                                    @foreach ($ruanganDropdown as $r)
                                        <option value="{{ $r->id }}" data-ruangan-asal="{{ $r->id }}">
                                            {{ $r->nama_ruangan }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Ruangan asal pegawai tidak akan muncul sebagai
                                    pilihan.</small>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Ajukan Pindah</button>
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
            document.getElementById('inputPendidikanNonFormal').value = '';
            document.getElementById('inputGajiPokok').value = '';
        }

        function siapkanFormEdit(id, nama, id_petugas, jabatan, ruangan_id, pendidikan_non_formal, gaji_pokok) {
            document.getElementById('modalPegawaiLabel').innerText = 'Edit Pegawai';
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('formPegawai').action = `/pegawai/${id}`;

            document.getElementById('inputNama').value = nama;
            document.getElementById('inputIdPetugas').value = id_petugas;
            document.getElementById('inputJabatan').value = jabatan;
            document.getElementById('inputRuangan').value = ruangan_id;
            document.getElementById('inputPendidikanNonFormal').value = pendidikan_non_formal;
            document.getElementById('inputGajiPokok').value = gaji_pokok;
        }

        function siapkanFormPindah(id, nama, ruanganAsalId) {
            document.getElementById('pindahNamaPegawai').innerText = nama;
            document.getElementById('formPindah').action = `/pegawai/${id}/pindah`;

            const select = document.getElementById('pindahRuanganTujuan');
            select.value = '';

            // Sembunyikan / disable ruangan asal dari pilihan tujuan
            Array.from(select.options).forEach(opt => {
                if (opt.value !== '' && parseInt(opt.dataset.ruanganAsal) === ruanganAsalId) {
                    opt.disabled = true;
                    if (!opt.textContent.includes('(ruangan saat ini)')) {
                        opt.textContent += ' (ruangan saat ini)';
                    }
                } else {
                    opt.disabled = false;
                    opt.textContent = opt.textContent.replace(' (ruangan saat ini)', '');
                }
            });
        }

        // FIX: sebelumnya class selector & data-field di sini tidak cocok dengan HTML
        // (".select-risk-emergency" vs "select-resiko-emergency", data-field "risk" vs "resiko")
        // sehingga perubahan dropdown resiko/emergency di header ruangan tidak pernah tersimpan.
        document.querySelectorAll('.select-resiko-emergency').forEach(function(select) {
            select.addEventListener('change', function() {
                const id = this.dataset.id;

                // ambil select pasangannya (resiko/emergency) di card header yang sama
                const card = this.closest('.card-header');
                const resikoSelect = card.querySelector('[data-field="resiko"]');
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
                            resiko: resikoSelect.value,
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
