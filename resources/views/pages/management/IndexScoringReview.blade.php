@extends('layouts.app')

@section('title', 'Review Index Scoring')

@section('content')
    <h1 class="h3 mb-3"><strong>Review Pengajuan Index Scoring</strong></h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Menunggu Review --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i data-feather="clock" class="me-2"></i>
                Menunggu Review ({{ $menunggu->count() }})
            </h5>
        </div>
        <div class="card-body p-0">
            @if ($menunggu->count())
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Periode</th>
                            <th>Jumlah Ruangan</th>
                            <th>Jumlah Pegawai</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($menunggu as $p)
                            <tr>
                                <td>{{ $p->periode_label }}</td>
                                <td>{{ $p->jumlah_ruangan }}</td>
                                <td>{{ $p->jumlah_pegawai }}</td>
                                <td class="text-end">
                                    <a href="{{ route('management.index.scoring.show', $p->periode) }}"
                                        class="btn btn-sm btn-primary">
                                        <i data-feather="eye" class="me-1"></i>
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted text-center py-4 mb-0">Tidak ada pengajuan yang menunggu review.</p>
            @endif
        </div>
    </div>

    {{-- Riwayat --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i data-feather="archive" class="me-2"></i>
                Riwayat Periode
            </h5>
        </div>
        <div class="card-body p-0">
            @if ($riwayat->count())
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Periode</th>
                            <th>Jumlah Ruangan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($riwayat as $p)
                            <tr>
                                <td>{{ $p->periode_label }}</td>
                                <td>{{ $p->jumlah_ruangan }}</td>
                                <td class="text-end">
                                    <a href="{{ route('management.index.scoring.show', $p->periode) }}"
                                        class="btn btn-sm btn-outline-secondary">
                                        <i data-feather="eye" class="me-1"></i>
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted text-center py-4 mb-0">Belum ada riwayat.</p>
            @endif
        </div>
    </div>
@endsection
