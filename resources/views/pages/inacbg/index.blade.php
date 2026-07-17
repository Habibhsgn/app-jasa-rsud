@extends('layouts.app')

@section('title', 'Data INA-CBG')

@section('content')
    <h1 class="h3 mb-3"><strong>Data INA-CBG</strong></h1>

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

    {{-- [ Stat Cards ] start --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-2 text-muted">Total Klaim</h6>
                            <h4 class="mb-0">{{ number_format($stats['total']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-2 text-muted">Pending</h6>
                            <h4 class="mb-0 text-warning">{{ number_format($stats['pending']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-2 text-muted">Disetujui</h6>
                            <h4 class="mb-0 text-success">{{ number_format($stats['disetujui']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- [ Stat Cards ] end --}}

    {{-- [ Upload Form ] start --}}
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Import Data INA-CBG</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('inacbg.import') }}" method="POST" enctype="multipart/form-data" id="formImport">
                @csrf
                <div class="row g-3 align-items-start">
                    <div class="col-md-5">
                        <label for="file_inacbg" class="form-label fw-bold">
                            File INA-CBG <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control @error('file_inacbg') is-invalid @enderror"
                            id="file_inacbg" name="file_inacbg" accept=".xlsx,.xls" required>
                        @error('file_inacbg')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">File Excel (.xlsx) hasil grouper INA-CBG.</div>
                    </div>
                    <div class="col-md-5">
                        <label for="file_feedback" class="form-label fw-bold">
                            File Feedback PDF (Opsional)
                        </label>
                        <input type="file"
                            class="form-control @error('file_feedback') is-invalid @enderror @error('file_feedback.*') is-invalid @enderror"
                            id="file_feedback" name="file_feedback[]" accept=".pdf" multiple>
                        @error('file_feedback')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @error('file_feedback.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Upload maksimal <strong>2 file PDF</strong> Feedback INA-CBG
                            (Rawat Jalan dan Rawat Inap). Sistem akan mengonversinya
                            menjadi Excel secara otomatis.
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label invisible d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100" id="btnImport">
                            Import
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    {{-- [ Upload Form ] end --}}

    {{-- [ Loading Modal ] start --}}
    <div class="modal fade" id="importLoadingModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary mb-4" style="width:3rem;height:3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mb-2">Sedang Mengimpor Data</h5>
                    <p class="text-muted mb-0">Mohon tunggu hingga proses selesai.</p>
                    <div class="small text-muted mt-3">Jangan menutup halaman atau me-refresh browser.</div>
                </div>
            </div>
        </div>
    </div>
    {{-- [ Loading Modal ] end --}}

    {{-- [ Filter & Search ] start --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('inacbg.index') }}" class="row align-items-end g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Filter Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">Cari</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control"
                            placeholder="Cari SEP, MRN, Nama, INA-CBG..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            Cari
                        </button>
                        @if (request('search'))
                            <a href="{{ route('inacbg.index') }}" class="btn btn-outline-danger">
                                &times;
                            </a>
                        @endif
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="text-muted small">
                        Menampilkan {{ $claims->firstItem() ?? 0 }} - {{ $claims->lastItem() ?? 0 }}
                        dari {{ $claims->total() }} data
                    </span>
                </div>
            </form>
        </div>
    </div>
    {{-- [ Filter & Search ] end --}}

    {{-- [ Data Table ] start --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>
                                <a href="{{ route('inacbg.index', array_merge(request()->query(), ['sort' => 'sep', 'dir' => request('sort') == 'sep' && request('dir') == 'asc' ? 'desc' : 'asc'])) }}"
                                    class="text-dark text-decoration-none">
                                    SEP
                                </a>
                            </th>
                            <th>
                                <a href="{{ route('inacbg.index', array_merge(request()->query(), ['sort' => 'nama_pasien', 'dir' => request('sort') == 'nama_pasien' && request('dir') == 'asc' ? 'desc' : 'asc'])) }}"
                                    class="text-dark text-decoration-none">
                                    Nama Pasien
                                </a>
                            </th>
                            <th>MRN</th>
                            <th>INA-CBG</th>
                            <th>
                                <a href="{{ route('inacbg.index', array_merge(request()->query(), ['sort' => 'total_tarif', 'dir' => request('sort') == 'total_tarif' && request('dir') == 'asc' ? 'desc' : 'asc'])) }}"
                                    class="text-dark text-decoration-none">
                                    Total Tarif
                                </a>
                            </th>
                            <th>Biaya Diajukan</th>
                            <th>Biaya Disetujui</th>
                            <th>Tgl. Verifikasi</th>
                            <th>
                                <a href="{{ route('inacbg.index', array_merge(request()->query(), ['sort' => 'status', 'dir' => request('sort') == 'status' && request('dir') == 'asc' ? 'desc' : 'asc'])) }}"
                                    class="text-dark text-decoration-none">
                                    Status
                                </a>
                            </th>
                            <th class="">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($claims as $claim)
                            <tr>
                                <td>
                                    <a href="{{ route('inacbg.show', $claim) }}" class="text-primary">
                                        {{ $claim->sep ?: '-' }}
                                    </a>
                                </td>
                                <td class="fw-semibold">{{ $claim->nama_pasien ?: '-' }}</td>
                                <td>{{ $claim->mrn ?: '-' }}</td>
                                <td><code>{{ $claim->inacbg ?: '-' }}</code></td>
                                <td class="">
                                    {{ $claim->total_tarif ? number_format($claim->total_tarif, 0) : '-' }}</td>
                                <td class="">
                                    {{ $claim->biaya_diajukan ? number_format($claim->biaya_diajukan, 0) : '-' }}</td>
                                <td class="">
                                    {{ $claim->biaya_disetujui ? number_format($claim->biaya_disetujui, 0) : '-' }}</td>
                                <td>{{ $claim->tgl_verifikasi ? $claim->tgl_verifikasi->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @if ($claim->status == 'disetujui')
                                        <span class="badge bg-success">Disetujui</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td class="">
                                    <div class="d-flex justify-content-start gap-1">
                                        <a href="{{ route('inacbg.show', $claim) }}"
                                            class="btn btn-sm btn-outline-primary" title="Detail">
                                            Detail
                                        </a>
                                        @if ($claim->status == 'pending')
                                            <form action="{{ route('inacbg.update-status', $claim) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('Setujui klaim ini?')">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="disetujui">
                                                <button type="submit" class="btn btn-sm btn-outline-success"
                                                    title="Setujui">
                                                    Setujui
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('inacbg.update-status', $claim) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Kembalikan status ke pending?')">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" class="btn btn-sm btn-outline-warning"
                                                    title="Pending-kan">
                                                    Pending-kan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    Belum ada data INA-CBG.
                                    <br>
                                    <small>Silakan upload file Excel menggunakan form di atas.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($claims->hasPages())
            <div class="card-footer bg-white">
                {{ $claims->links() }}
            </div>
        @endif
    </div>
    {{-- [ Data Table ] end --}}

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var formImport = document.getElementById('formImport');
            var btnImport = document.getElementById('btnImport');
            var fileInacbg = document.getElementById('file_inacbg');

            if (formImport) {
                formImport.addEventListener('submit', function(e) {
                    // Pastikan file wajib sudah dipilih sebelum menampilkan modal
                    if (!fileInacbg.value) {
                        return;
                    }

                    var loadingModalEl = document.getElementById('importLoadingModal');
                    var loadingModal = new bootstrap.Modal(loadingModalEl);
                    loadingModal.show();

                    btnImport.disabled = true;
                    btnImport.innerText = 'Mengimpor...';
                });
            }
        });
    </script>
@endpush
