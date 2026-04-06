@extends('layouts.app')

@section('title', 'Data Pegawai')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><strong>Master Data Pegawai per Ruangan</strong></h1>
        {{-- Tombol Pemicu Modal Tambah --}}
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPegawai" onclick="siapkanFormTambah()">
            ➕ Tambah Pegawai
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- LOOPING PER RUANGAN --}}
    @forelse ($pegawaiGrouped as $namaRuangan => $pegawaiList)
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between py-2">
                <h5 class="mb-0 text-white fw-bold">🏥 RUANGAN: {{ $namaRuangan }}</h5>
                <span class="badge bg-light text-dark">{{ $pegawaiList->count() }} Pegawai</span>
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
                                    <td>{{ $p->id_petugas }}</td>
                                    <td>{{ $p->jabatan }}</td>
                                    <td class="text-center">
                                        {{-- Tombol Pemicu Modal Edit --}}
                                        <button type="button" class="btn btn-warning btn-sm text-dark" 
                                            data-bs-toggle="modal" data-bs-target="#modalPegawai"
                                            onclick="siapkanFormEdit({{ $p->id }}, '{{ $p->nama }}', '{{ $p->id_petugas }}', '{{ $p->jabatan }}', '{{ $p->ruangan_id }}')">
                                            Edit
                                        </button>

                                        <form action="{{ route('pegawai.destroy', $p->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus pegawai ini?')">
                                                Hapus
                                            </button>
                                        </form>
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

    {{-- MODAL BOOTSTRAP --}}
    <div class="modal fade" id="modalPegawai" tabindex="-1" aria-labelledby="modalPegawaiLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalPegawaiLabel">Tambah Pegawai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="formPegawai" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" id="inputNama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ID Petugas / NIP / NIK</label>
                            <input type="text" name="id_petugas" id="inputIdPetugas" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="jabatan" id="inputJabatan" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Penempatan Ruangan</label>
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
                        <button type="submit" class="btn btn-primary" id="btnSubmit">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPT PENGISIAN FORM --}}
    <script>
        function siapkanFormTambah() {
            document.getElementById('modalPegawaiLabel').innerText = 'Tambah Pegawai Baru';
            document.getElementById('formMethod').value = 'POST'; 
            document.getElementById('formPegawai').action = "{{ route('pegawai.store') }}"; 
            
            document.getElementById('inputNama').value = '';
            document.getElementById('inputIdPetugas').value = '';
            document.getElementById('inputJabatan').value = '';
            document.getElementById('inputRuangan').value = '';
            
            document.getElementById('btnSubmit').innerText = 'Simpan Data';
            document.getElementById('btnSubmit').className = 'btn btn-primary';
        }

        function siapkanFormEdit(id, nama, id_petugas, jabatan, ruangan_id) {
            document.getElementById('modalPegawaiLabel').innerText = 'Edit Data Pegawai';
            document.getElementById('formMethod').value = 'PUT'; 
            document.getElementById('formPegawai').action = `/pegawai/${id}`; 
            
            document.getElementById('inputNama').value = nama;
            document.getElementById('inputIdPetugas').value = id_petugas;
            document.getElementById('inputJabatan').value = jabatan;
            document.getElementById('inputRuangan').value = ruangan_id;

            document.getElementById('btnSubmit').innerText = 'Update Data';
            document.getElementById('btnSubmit').className = 'btn btn-warning text-dark fw-bold';
        }
    </script>
@endsection