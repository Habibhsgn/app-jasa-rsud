@extends('layouts.app')
@section('title', 'Perhitungan Top Leader')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0"><strong>Perhitungan</strong> Top Leader</h1>
            <p class="text-muted mb-0 small">Alokasi jasa untuk jajaran top leader berdasarkan periode klaim INA-CBG</p>
        </div>
    </div>

    <div class="container-fluid p-0">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Filter Periode -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 text-muted">Filter Periode</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
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
                    <div class="col-md-4">
                        <label class="form-label">Tahun</label>
                        <select name="tahun" class="form-select">
                            <option value="">Semua Tahun</option>
                            @foreach ($availableYears as $y)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            Filter
                        </button>
                        <a href="{{ route('top-leader.perhitungan') }}" class="btn btn-outline-secondary flex-fill">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm border-start border-primary border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-2 text-uppercase small">Total Jasa Disetujui</h6>
                        <h4 class="mb-1">Rp {{ number_format($totalJasaDisetujui, 0, ',', '.') }}</h4>
                        <span class="badge text-bg-light text-muted fw-normal">
                            {{ $bulan ? \Carbon\Carbon::create()->month((int) $bulan)->format('F') : 'Semua Bulan' }}
                            {{ $tahun ?: 'Semua Tahun' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm border-start border-info border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-2 text-uppercase small">Nilai Persen Jasa ({{ $persenJasa * 100 }}%)</h6>
                        <h4 class="mb-1">Rp {{ number_format($nilaiPersenJasa, 0, ',', '.') }}</h4>
                        <span class="badge text-bg-light text-muted fw-normal">Total Jasa × {{ $persenJasa * 100 }}%</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm border-start border-warning border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-2 text-uppercase small">Total Top Leader ({{ $persenTopLeader }}%)</h6>
                        <h4 class="mb-1">Rp {{ number_format($totalTopLeader, 0, ',', '.') }}</h4>
                        <span class="badge text-bg-light text-muted fw-normal">Nilai Persen Jasa ×
                            {{ $persenTopLeader }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Perhitungan per Posisi -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detail Alokasi per Posisi</h5>
                <span class="badge text-bg-secondary">{{ count($calculations) }} Posisi</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Posisi</th>
                                <th class="text-end">Persen</th>
                                <th style="width: 20%">Proporsi</th>
                                <th class="text-end">Alokasi Total</th>
                                <th class="text-center">Jumlah Orang</th>
                                <th class="text-end">Per Orang</th>
                                <th>Anggota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($calculations as $posisi => $data)
                                <tr>
                                    <td><strong>{{ $posisi }}</strong></td>
                                    <td class="text-end">{{ number_format($data['persen'], 1, ',', '.') }}%</td>
                                    <td>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" role="progressbar"
                                                style="width: {{ $data['persen'] }}%"
                                                aria-valuenow="{{ $data['persen'] }}" aria-valuemin="0"
                                                aria-valuemax="100"></div>
                                        </div>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($data['alokasi_total'], 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if ($data['jumlah_orang'] > 0)
                                            <span
                                                class="badge rounded-pill text-bg-success">{{ $data['jumlah_orang'] }}</span>
                                        @else
                                            <span class="badge rounded-pill text-bg-warning">Kosong</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($data['jumlah_orang'] > 0)
                                            <strong>Rp {{ number_format($data['per_orang'], 0, ',', '.') }}</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($data['anggota']->count() > 0)
                                            <ul class="mb-0 ps-3 small">
                                                @foreach ($data['anggota'] as $anggota)
                                                    <li>{{ $anggota->nama }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted small fst-italic">Belum ada anggota</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>TOTAL</th>
                                <th class="text-end">100%</th>
                                <th></th>
                                <th class="text-end">Rp {{ number_format($totalTopLeader, 0, ',', '.') }}</th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Rumus Perhitungan -->
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Cara Perhitungan</h5>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li class="mb-2">
                        <strong>Total Jasa Disetujui</strong> — Jumlah seluruh biaya klaim yang statusnya
                        sudah <em>disetujui</em>
                        @if ($bulan || $tahun)
                            , untuk periode
                            <strong>
                                {{ $bulan ? \Carbon\Carbon::create()->month((int) $bulan)->format('F') : 'semua bulan' }}
                                {{ $tahun ?: 'semua tahun' }}
                            </strong>
                        @else
                            dari seluruh periode
                        @endif
                        .
                    </li>
                    <li class="mb-2">
                        <strong>Nilai Persen Jasa</strong> — Total Jasa Disetujui dikalikan
                        {{ $persenJasa * 100 }}% (persentase ini diatur lewat pengaturan
                        <code>persen_jasa</code>).
                    </li>
                    <li class="mb-2">
                        <strong>Total Top Leader</strong> — Nilai Persen Jasa di atas dikalikan lagi
                        {{ $persenTopLeader }}% (diatur lewat pengaturan <code>persen_jasa_top_leader</code>).
                        Angka inilah yang menjadi dana yang akan dibagi ke seluruh jajaran top leader (dianggap 100%).
                    </li>
                    <li class="mb-2">
                        <strong>Alokasi per Posisi</strong> — Dana Total Top Leader dibagi ke tiap posisi
                        (Direktur, Kabid, Kasie, dst.) sesuai persentase masing-masing posisi
                        (diatur lewat pengaturan <code>top_leader.*</code>).
                    </li>
                    <li>
                        <strong>Per Orang</strong> — Alokasi tiap posisi dibagi rata dengan jumlah orang
                        yang menjabat posisi tersebut. Jika hanya ada 1 orang, dia menerima seluruh
                        alokasi posisi itu.
                    </li>
                </ol>
            </div>
        </div>
    </div>
@endsection
