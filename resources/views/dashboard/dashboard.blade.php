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

        {{-- GROUPED TABLE PER PERIODE --}}
        <div class="row">
            <div class="col-12">

                @forelse($data as $periode => $items)

                    <div class="card mb-3 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Periode {{ $periode }}</strong>

                            <button class="btn btn-sm btn-primary" data-bs-toggle="collapse"
                                data-bs-target="#p{{ md5($periode) }}">
                                Detail
                            </button>
                        </div>

                        <div id="p{{ md5($periode) }}" class="collapse">
                            <div class="table-responsive">

                                <table class="table table-hover my-0">
                                    <thead>
                                        <tr>
                                            <th>Ruangan</th>

                                            @if ($koordinator)
                                                <th>Nominal</th>
                                            @endif

                                            <th>Status</th>
                                            @if (!$koordinator)
                                                <th class="text-center">Persen</th>
                                            @endif
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($items as $item)
                                            <tr>
                                                <td>{{ $item->ruangan->nama_ruangan ?? 'N/A' }}</td>

                                                {{-- NOMINAL HIDDEN UNTUK KOORDINATOR --}}
                                                @if ($koordinator)
                                                    <td>
                                                        Rp {{ number_format($item->nominal, 0, ',', '.') }}
                                                    </td>
                                                @endif

                                                <td>
                                                    @if ($item->status == 'selesai')
                                                        <span class="badge bg-success">Selesai</span>
                                                    @elseif($item->status == 'proses_karu')
                                                        <span class="badge bg-warning">Proses Karu</span>
                                                    @elseif($item->status == 'verifikasi')
                                                        <span class="badge bg-info">Verifikasi</span>
                                                    @endif
                                                </td>

                                                @if (!$koordinator)
                                                    <td class="text-center">
                                                        {{ (float) $item->persen }}%
                                                    </td>
                                                @endif
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $item->catatan ?? '-' }}
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>

                                </table>

                            </div>
                        </div>
                    </div>

                @empty
                    <div class="card">
                        <div class="card-body text-center text-muted">
                            Tidak ada data jasa ditemukan.
                        </div>
                    </div>
                @endforelse

            </div>
        </div>

    </div>
@endsection
