@extends('layouts.app')

@section('title', 'Data INA-CBG')

@section('content')
    @php
        $sectionList = \App\Models\InacbgClaim::SECTIONS;
        $isAll = $tab === 'semua';
        $cfg = $isAll ? null : $sectionList[$tab];
        $st = $isAll ? null : ($tabStats[$tab] ?? ['total' => 0, 'filled' => 0, 'empty' => 0]);
        $pct = $st && $st['total'] ? round(($st['filled'] / $st['total']) * 100) : 0;
        $canEditTab = !$isAll && in_array($tab, $editable, true);
        $tabQuery = request()->except(['page', 'ket', 'sort', 'dir']);
        $sortUrl = fn($f) => route('inacbg.index', array_merge(request()->query(), [
            'tab' => $tab,
            'sort' => $f,
            'dir' => request('sort') == $f && request('dir') == 'asc' ? 'desc' : 'asc',
        ]));
        $sortMark = fn($f) => request('sort') == $f ? (request('dir') == 'asc' ? ' ▲' : ' ▼') : '';
    @endphp

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

    {{-- [ Tab seksi ] --}}
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

    @if ($isAll)
        {{-- [ Stat Cards ] --}}
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

        {{-- [ Upload Form ] --}}
        @if ($canAll)
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Import Data INA-CBG</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('inacbg.import') }}" method="POST" enctype="multipart/form-data"
                        id="formImport">
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
    @else
        {{-- [ Panel progres seksi ] --}}
        <div class="card mb-4 border-start border-4 border-{{ $cfg['color'] }}">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="flex-grow-1" style="min-width: 260px;">
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <span class="fw-semibold">Keterangan {{ $cfg['label'] }}</span>
                            <span class="text-muted small">
                                {{ number_format($st['filled']) }} dari {{ number_format($st['total']) }} SEP terisi ({{ $pct }}%)
                            </span>
                        </div>
                        <div class="progress" style="height: 8px;" role="progressbar" aria-valuenow="{{ $pct }}"
                            aria-valuemin="0" aria-valuemax="100"
                            aria-label="Kemajuan pengisian keterangan {{ $cfg['label'] }}">
                            <div class="progress-bar bg-{{ $cfg['color'] }}" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="text-muted small mt-2">Angka mengikuti filter bulan dan tahun.</div>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Filter keterangan">
                        <a href="{{ route('inacbg.index', array_merge(request()->except(['page', 'ket']), ['tab' => $tab])) }}"
                            class="btn {{ !request('ket') ? 'btn-' . $cfg['color'] : 'btn-outline-' . $cfg['color'] }}">
                            Semua ({{ number_format($st['total']) }})
                        </a>
                        <a href="{{ route('inacbg.index', array_merge(request()->except('page'), ['tab' => $tab, 'ket' => 'belum'])) }}"
                            class="btn {{ request('ket') === 'belum' ? 'btn-' . $cfg['color'] : 'btn-outline-' . $cfg['color'] }}">
                            Belum diisi ({{ number_format($st['empty']) }})
                        </a>
                        <a href="{{ route('inacbg.index', array_merge(request()->except('page'), ['tab' => $tab, 'ket' => 'sudah'])) }}"
                            class="btn {{ request('ket') === 'sudah' ? 'btn-' . $cfg['color'] : 'btn-outline-' . $cfg['color'] }}">
                            Sudah diisi ({{ number_format($st['filled']) }})
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- [ Filter & Search ] --}}
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0 text-muted">Filter Periode</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('inacbg.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                @if (!$isAll && request()->filled('ket'))
                    <input type="hidden" name="ket" value="{{ request('ket') }}">
                @endif
                <div class="col-md-3">
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-select" onchange="this.form.submit()">
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
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Tahun</option>
                        @foreach ($availableYears as $y)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Filter Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Cari</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control"
                            placeholder="Cari SEP, MRN, Nama, INA-CBG..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">Cari</button>
                        <a href="{{ route('inacbg.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i> Reset
                        </a>
                    </div>
                </div>
                <div class="col-md-1">
                    <label class="form-label invisible d-none d-md-block">&nbsp;</label>
                    <a href="{{ route('inacbg.export', array_merge(request()->query(), ['tab' => $tab])) }}"
                        class="btn btn-success w-100">
                        <i class="bi bi-download me-1"></i> Export
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- [ Tabel data ] --}}
    @include('pages.inacbg.partials.index-table')
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