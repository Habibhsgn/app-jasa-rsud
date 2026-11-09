@extends('layouts.app')

@section('title', 'Data INA-CBG')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/inacbg-index.css') }}">
@endpush

@section('content')
    @php
        $sectionList = \App\Models\InacbgClaim::SECTIONS;
        $isAll = $tab === 'semua';
        $isRaber = $tab === 'raber';
        $isSection = !$isAll && !$isRaber;
        $cfg = $isSection ? $sectionList[$tab] : null;
        $st = $isSection ? $tabStats[$tab] ?? ['total' => 0, 'filled' => 0, 'empty' => 0] : null;
        $pct = $st && $st['total'] ? round(($st['filled'] / $st['total']) * 100) : 0;
        $canEditTab = $isSection && in_array($tab, $editable, true);
        $tabQuery = request()->except(['page', 'ket', 'sort', 'dir', 'tindakan', 'dpjp_status']);
        $sortUrl = fn($f) => route(
            'inacbg.index',
            array_merge(request()->query(), [
                'tab' => $tab,
                'sort' => $f,
                'dir' => request('sort') == $f && request('dir') == 'asc' ? 'desc' : 'asc',
            ]),
        );
        $sortMark = fn($f) => request('sort') == $f ? (request('dir') == 'asc' ? ' ▲' : ' ▼') : '';
    @endphp

    <h1 class="h3 mb-3"><strong>Data INA-CBG</strong></h1>

    {{-- Flash messages (hasil form biasa / import) --}}
    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- [ Tab seksi ] — dimuat ulang diam-diam setelah aksi --}}
    <div id="inacbg-tabs">
        <ul class="nav nav-tabs mb-4 flex-nowrap overflow-auto">
            @if ($canAll)
                <li class="nav-item">
                    <a class="nav-link text-nowrap {{ $isAll ? 'active' : '' }}"
                        href="{{ route('inacbg.index', array_merge($tabQuery, ['tab' => 'semua'])) }}">
                        <i data-feather="list" class="me-1"></i>Semua
                        <span class="badge rounded-pill text-bg-secondary ms-1">{{ number_format($tabStats['semua'] ?? 0) }}</span>
                    </a>
                </li>
            @endif
            @if ($canRaber)
                <li class="nav-item">
                    <a class="nav-link text-nowrap {{ $isRaber ? 'active' : '' }}"
                        href="{{ route('inacbg.index', array_merge($tabQuery, ['tab' => 'raber'])) }}">
                        <i data-feather="users" class="me-1 text-info"></i>Raber
                        <span class="badge rounded-pill text-bg-info ms-1" title="Diubah / total raber">
                            {{ number_format($tabStats['raber']['diubah'] ?? 0) }}/{{ number_format($tabStats['raber']['total'] ?? 0) }}
                        </span>
                    </a>
                </li>
            @endif
            @foreach ($sectionList as $key => $sec)
                @if (in_array($key, $allowed, true))
                    <li class="nav-item">
                        <a class="nav-link text-nowrap {{ $tab === $key ? 'active' : '' }}"
                            href="{{ route('inacbg.index', array_merge($tabQuery, ['tab' => $key])) }}">
                            <i data-feather="{{ $sec['icon'] }}" class="me-1 text-{{ $sec['color'] }}"></i>{{ $sec['label'] }}
                            <span class="badge rounded-pill text-bg-{{ $sec['color'] }} ms-1" title="Terisi / total SEP">
                                {{ number_format($tabStats[$key]['filled'] ?? 0) }}/{{ number_format($tabStats[$key]['total'] ?? 0) }}
                            </span>
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>

    {{-- [ Tab Semua: stat cards, import — tidak ikut dimuat ulang ] --}}
    @if ($isAll)
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-2 text-muted">Total Klaim</h6>
                        <h4 class="mb-0">{{ number_format($stats['total']) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-2 text-muted">Pending</h6>
                        <h4 class="mb-0 text-warning">{{ number_format($stats['pending']) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-2 text-muted">Disetujui</h6>
                        <h4 class="mb-0 text-success">{{ number_format($stats['disetujui']) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        @if ($canAll)
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
                                    File Feedback PDF <span class="text-danger">*</span>
                                </label>
                                <input type="file" required
                                    class="form-control @error('file_feedback') is-invalid @enderror @error('file_feedback.*') is-invalid @enderror"
                                    id="file_feedback" name="file_feedback[]" accept=".pdf">
                                @error('file_feedback')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('file_feedback.*')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Upload <strong> file PDF</strong> Feedback INA-CBG.</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label invisible d-none d-md-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100" id="btnImport">Import</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- [ Loading Modal ] --}}
        <div class="modal fade" id="importLoadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
            aria-hidden="true">
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
    @endif

    {{-- [ Filter & Search ] — diterapkan langsung (tanpa reload), cari memakai debounce --}}
    <div class="card mb-4" id="inacbg-filter">
        <div class="card-header bg-white">
            <h6 class="mb-0 text-muted">Filter</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('inacbg.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="col-md-2">
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-select">
                        <option value="">Semua Bulan</option>
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($i)->locale('id')->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-select">
                        <option value="">Semua Tahun</option>
                        @foreach ($availableYears as $y)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    </select>
                </div>
                {{-- Filter khusus seksi (dulu tombol di panel progres) --}}
                @if ($isRaber)
                    @php $rs = $tabStats['raber'] ?? ['total' => 0, 'diubah' => 0, 'default' => 0]; @endphp
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Dokter Utama</label>
                        <select name="dpjp_status" class="form-select">
                            <option value="" {{ request('dpjp_status') == '' ? 'selected' : '' }}>Semua ({{ number_format($rs['total']) }})</option>
                            <option value="default" {{ request('dpjp_status') === 'default' ? 'selected' : '' }}>Default ({{ number_format($rs['default']) }})</option>
                            <option value="diubah" {{ request('dpjp_status') === 'diubah' ? 'selected' : '' }}>Diubah ({{ number_format($rs['diubah']) }})</option>
                        </select>
                    </div>
                @elseif ($isSection)
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Keterangan</label>
                        <select name="ket" class="form-select">
                            <option value="" {{ request('ket') == '' ? 'selected' : '' }}>Semua ({{ number_format($st['total']) }})</option>
                            <option value="belum" {{ request('ket') === 'belum' ? 'selected' : '' }}>Belum diisi ({{ number_format($st['empty']) }})</option>
                            <option value="sudah" {{ request('ket') === 'sudah' ? 'selected' : '' }}>Sudah diisi ({{ number_format($st['filled']) }})</option>
                        </select>
                    </div>
                    @if ($tab === 'radiologi' && $tindakanStats)
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tindakan</label>
                            <select name="tindakan" class="form-select">
                                <option value="" {{ request('tindakan') == '' ? 'selected' : '' }}>Semua ({{ number_format($tindakanStats['total']) }})</option>
                                <option value="rontgen" {{ request('tindakan') === 'rontgen' ? 'selected' : '' }}>Rontgen ({{ number_format($tindakanStats['rontgen']) }})</option>
                                <option value="usg" {{ request('tindakan') === 'usg' ? 'selected' : '' }}>USG ({{ number_format($tindakanStats['usg']) }})</option>
                                <option value="belum" {{ request('tindakan') === 'belum' ? 'selected' : '' }}>Belum dipilih ({{ number_format($tindakanStats['belum']) }})</option>
                            </select>
                        </div>
                    @endif
                @endif
                <div class="col-md" style="min-width: 260px;">
                    <label class="form-label fw-bold">Cari</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" autocomplete="off"
                            placeholder="{{ $isRaber ? 'Cari SEP, MRN, Nama, Dokter...' : 'Cari SEP, MRN, Nama, INA-CBG...' }}"
                            value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">Cari</button>
                        <a href="{{ route('inacbg.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i> Reset
                        </a>
                    </div>
                </div>
                <div class="col-md-auto">
                    <label class="form-label invisible d-none d-md-block">&nbsp;</label>
                    <a id="btnExport" data-base="{{ route('inacbg.export') }}" data-tab="{{ $tab }}"
                        href="{{ route('inacbg.export', array_merge(request()->query(), ['tab' => $tab])) }}"
                        class="btn btn-success">
                        <i class="bi bi-download me-1"></i> Export
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- [ Tabel data ] — dimuat ulang diam-diam setelah aksi, cari, sort, paginasi --}}
    <div id="inacbg-table">
        @include('pages.inacbg.partials.index-table')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/inacbg-ajax.js') }}"></script>
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