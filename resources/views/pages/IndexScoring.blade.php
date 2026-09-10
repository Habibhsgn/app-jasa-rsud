@extends('layouts.app')

@section('title', 'Index Scoring')

@section('content')
    <h1 class="h3 mb-3"><strong>Index Scoring</strong></h1>

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

    {{-- Form Pengajuan --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i data-feather="file-plus" class="me-2"></i>
                Pengajuan Index Scoring
            </h5>
        </div>

        <div class="card-body">
            <form action="{{ route('index.scoring.create') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            Periode Pengajuan
                        </label>
                        <input type="month" name="periode" class="form-control"
                            max="{{ now()->subMonth()->format('Y-m') }}" required>
                        <small class="text-muted">
                            Periode yang dapat dipilih maksimal bulan sebelumnya.
                        </small>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success">
                            <i data-feather="plus"></i>
                            Buat Pengajuan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Loop Periode (setiap periode = kartu terpisah, dengan form sendiri per ruangan) --}}
    @if (isset($data) && $data->count())
        @foreach ($data as $periodeItem)
            <div class="mb-5">

                <h5 class="mb-2">Periode: {{ $periodeItem->periode_label }}</h5>

                {{-- Section 1: Regular Pegawai per Ruangan --}}
                @foreach ($periodeItem->ruangans as $item)
                    @include('pages.partials.index-scoring-ruangan-form', [
                        'periodeItem' => $periodeItem,
                        'item' => $item,
                    ])
                @endforeach

                {{-- Section 2: Top Leader (tanpa ruangan) --}}
                @if ($periodeItem->top_leaders && $periodeItem->top_leaders->count())
                    @foreach ($periodeItem->top_leaders as $group)
                        @include('pages.partials.index-scoring-top-leader-form', [
                            'periodeItem' => $periodeItem,
                            'group' => $group,
                        ])
                    @endforeach
                @endif

            </div>
        @endforeach
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i data-feather="clipboard" style="width:70px;height:70px" class="text-secondary mb-3"></i>
                <h5 class="mb-2">Belum Ada Pengajuan</h5>
                <p class="text-muted mb-0">
                    Pilih periode pengajuan terlebih dahulu, kemudian klik
                    <strong>Buat Pengajuan</strong>.
                </p>
            </div>
        </div>
    @endif

    {{-- Script Kalkulasi Index Scoring + Validasi Submit --}}
    @include('pages.partials.index-scoring-scripts')

@endsection