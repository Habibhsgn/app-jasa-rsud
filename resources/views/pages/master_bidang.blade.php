@extends('layouts.app')

@section('title', 'Master Bidang')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <strong>Master Bidang</strong>
        </h1>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">

        {{-- FORM TAMBAH --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        Tambah Bidang Baru
                    </h5>
                </div>

                <div class="card-body">

                    <form action="{{ route('master.bidang.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">
                                Kode Bidang
                            </label>

                            <input type="text" name="kode_bidang" class="form-control text-uppercase"
                                placeholder="Contoh : MNJ-RI" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Nama Bidang
                            </label>

                            <input type="text" name="nama_bidang" class="form-control text-uppercase"
                                placeholder="Contoh : RAWAT INAP" required>
                        </div>

                        <button class="btn btn-primary w-100">
                            Tambah Bidang
                        </button>

                    </form>

                </div>
            </div>
        </div>

        {{-- TABEL --}}
        <div class="col-md-8">

            <div class="card">

                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        Daftar Bidang
                    </h5>
                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead class="table-secondary text-center">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">Kode</th>
                                    <th>Nama Bidang</th>
                                    <th width="15%">Status</th>
                                    <th width="18%">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($bidang as $index => $b)
                                    <tr class="{{ !$b->is_active ? 'table-secondary' : '' }}">

                                        <td class="text-center">
                                            {{ $index + 1 }}
                                        </td>

                                        <td>
                                            {{ $b->kode_bidang }}
                                        </td>

                                        <td>
                                            {{ $b->nama_bidang }}
                                        </td>

                                        <td class="text-center">

                                            <span class="badge bg-{{ $b->is_active ? 'success' : 'secondary' }}">
                                                {{ $b->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>

                                        </td>

                                        <td class="text-center">

                                            <button class="btn btn-warning btn-sm text-dark" data-bs-toggle="modal"
                                                data-bs-target="#modalBidang"
                                                onclick="siapkanFormEdit(
                                                    {{ $b->id }},
                                                    {{ Js::from($b->kode_bidang) }},
                                                    {{ Js::from($b->nama_bidang) }}
                                                )">

                                                Edit

                                            </button>

                                            <form action="{{ route('master.bidang.toggleStatus', $b->id) }}" method="POST"
                                                class="d-inline">

                                                @csrf

                                                <button type="submit" class="btn btn-outline-primary btn-sm"
                                                    onclick="return confirm('Ubah status bidang?')">

                                                    {{ $b->is_active ? 'Nonaktifkan' : 'Aktifkan' }}

                                                </button>

                                            </form>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            Belum ada data bidang.
                                        </td>
                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- MODAL EDIT --}}
    <div class="modal fade" id="modalBidang" tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="modalBidangLabel">
                        Edit Bidang
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>

                </div>

                <form id="formBidang" method="POST">

                    @csrf

                    <input type="hidden" name="_method" id="formMethod" value="PUT">

                    <div class="modal-body">

                        <div class="mb-3">

                            <label>Kode Bidang</label>

                            <input type="text" id="inputKode" name="kode_bidang" class="form-control text-uppercase"
                                required>

                        </div>

                        <div class="mb-3">

                            <label>Nama Bidang</label>

                            <input type="text" id="inputNama" name="nama_bidang" class="form-control text-uppercase"
                                required>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                            Batal

                        </button>

                        <button class="btn btn-primary">

                            Simpan

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        function siapkanFormEdit(id, kode, nama) {

            document.getElementById('modalBidangLabel').innerText = 'Edit Bidang';

            document.getElementById('formMethod').value = 'PUT';

            document.getElementById('formBidang').action = '/master-bidang/' + id;

            document.getElementById('inputKode').value = kode;

            document.getElementById('inputNama').value = nama;

        }
    </script>
@endpush
