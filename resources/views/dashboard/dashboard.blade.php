@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="h3 mb-3"><strong>Analytics</strong> Dashboard</h1>

    <div class="container-fluid p-0">

        {{-- STAT CARD --}}
        <div class="row">

            <div class="col-xl-3 col-md-6">
                <div class="card bg-primary text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ $labelPegawai }}</h5>
                        <h2>{{ $totalPegawai }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Ruangan</h5>
                        <h2>{{ $totalRuangan }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-6">
                <div class="card bg-warning text-dark mb-3">
                    <div class="card-body">
                        <h5>Proses Karu</h5>
                        <h2>{{ $totalProses }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-6">
                <div class="card bg-info text-white mb-3">
                    <div class="card-body">
                        <h5>Verifikasi</h5>
                        <h2>{{ $totalVerifikasi }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-6">
                <div class="card bg-secondary text-white mb-3">
                    <div class="card-body">
                        <h5>Selesai</h5>
                        <h2>{{ $totalSelesai }}</h2>
                    </div>
                </div>
            </div>

        </div>

        {{-- CALENDAR + TABLE --}}
        <div class="row">

            {{-- Calendar --}}
            <div class="col-12 col-md-6 col-xxl-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Calendar</h5>
                    </div>
                    <div class="card-body d-flex">
                        <div class="align-self-center w-100">
                            <div id="datetimepicker-dashboard"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Latest Projects --}}
            <div class="col-12 col-lg-8 d-flex">
                <div style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover my-0">
                        <thead style="position: sticky; top: 0; background: white;">
                            <tr>
                                <th>Periode</th>
                                <th class="d-none d-xl-table-cell">Ruangan</th>
                                <th class="d-none d-xl-table-cell">Nominal</th>
                                <th>Status</th>
                                <th class="d-none d-md-table-cell text-center">Persen</th>
                                <th class="d-none d-md-table-cell">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $item)
                                <tr>
                                    <td>{{ $item->periode->periode ?? 'N/A' }}</td>
                                    <td class="d-none d-xl-table-cell">
                                        {{ $item->ruangan->nama_ruangan ?? 'N/A' }}
                                    </td>
                                    <td class="d-none d-xl-table-cell">
                                        Rp {{ number_format($item->nominal, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        @if ($item->status == 'selesai')
                                            <span class="badge bg-success">Selesai</span>
                                        @elseif($item->status == 'proses_karu')
                                            <span class="badge bg-warning">Proses Karu</span>
                                        @elseif($item->status == 'verifikasi')
                                            <span class="badge bg-info">Verifikasi</span>
                                        @endif
                                    </td>
                                    <td class="d-none d-md-table-cell text-center">
                                        {{ (float) $item->persen }}%
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <small class="text-muted">{{ $item->catatan ?? '-' }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        Data pembagian jasa tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection
