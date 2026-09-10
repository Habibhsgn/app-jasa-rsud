@extends('layouts.app')

@section('title', 'Detail Review — ' . $periodeInfo->periode_label)

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0"><strong>Review Periode: {{ $periodeInfo->periode_label }}</strong></h1>
        <a href="{{ route('management.index.scoring.index') }}" class="btn btn-outline-secondary btn-sm">
            <i data-feather="arrow-left" class="me-1"></i>
            Kembali
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Section 1: Ruangan (Regular Pegawai) --}}
    @if ($ruangans && $ruangans->count())
        <div class="mb-4">
            <h5 class="mb-3 text-primary"><i data-feather="home" class="me-1"></i> Ruangan</h5>
            @foreach ($ruangans as $item)
                @include('pages.management.partials.index-scoring-review-ruangan', ['item' => $item, 'periodeInfo' => $periodeInfo])
            @endforeach
        </div>
    @endif

    {{-- Section 2: Top Leader --}}
    @if ($top_leaders && $top_leaders->count())
        <div class="mb-4">
            <h5 class="mb-3 text-info"><i data-feather="users" class="me-1"></i> Top Leader</h5>
            @foreach ($top_leaders as $group)
                @include('pages.management.partials.index-scoring-review-top-leader', ['group' => $group, 'periodeInfo' => $periodeInfo])
            @endforeach
        </div>
    @endif

    @if ((!$ruangans || !$ruangans->count()) && (!$top_leaders || !$top_leaders->count()))
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i data-feather="clipboard" style="width:70px;height:70px" class="text-secondary mb-3"></i>
                <h5 class="mb-2">Tidak Ada Data</h5>
                <p class="text-muted mb-0">Tidak ada pengajuan untuk periode ini.</p>
            </div>
        </div>
    @endif
@endsection