@extends('layouts.app')

@section('title', 'Detail Klaim INA-CBG')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/inacbg-show.css') }}">
@endpush

@section('content')
    @php
        $c = $inacbgClaim;
        $isAll = $tab === 'semua';
        $cfg = $isAll ? null : \App\Models\InacbgClaim::SECTIONS[$tab];
        $sec = $isAll ? null : $sections[$tab] ?? null;
        $backUrl = route('inacbg.index', ['tab' => $tab]);
        $rp = fn($v) => 'Rp ' . number_format((float) $v, 2);

        $pasien = [
            'Nama Pasien' => $c->nama_pasien,
            'MRN' => $c->mrn,
            'SEP' => $c->sep,
            'DPJP' => $c->dpjp,
            'Payor ID' => $c->payor_id,
        ];

        $usia = trim(($c->umur_tahun ? $c->umur_tahun . ' tahun' : '') . ' ' . ($c->umur_hari ? $c->umur_hari . ' hari' : ''));
        $demografi = [
            'Tanggal Lahir' => $c->birth_date ? $c->birth_date->format('d/m/Y') : null,
            'Usia' => $usia,
            'Jenis Kelamin' => $c->sex == 'L' ? 'Laki-laki' : ($c->sex == 'P' ? 'Perempuan' : null),
            'Kelas RS' => $c->kelas_rs,
            'Kelas Rawat' => $c->kelas_rawat,
            'Admisi' => $c->admission_date ? $c->admission_date->format('d/m/Y') : null,
            'Discharge' => $c->discharge_date ? $c->discharge_date->format('d/m/Y') : null,
            'LOS' => $c->los ? $c->los . ' hari' : null,
            'Discharge Status' => $c->discharge_status,
        ];
    @endphp

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">
                <strong>{{ $isAll ? 'Detail Klaim INA-CBG' : 'Keterangan ' . $cfg['label'] }}</strong>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $backUrl }}">INA-CBG{{ $isAll ? '' : ' · ' . $cfg['label'] }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detail</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                <i data-feather="arrow-left" class="me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if (!$isAll)
        {{-- =========================================================
             TAMPILAN TAB SEKSI: fokus ke keterangan seksi tsb
             ========================================================= --}}
        {{-- Ringkasan SEP --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3 col-lg"><div class="text-muted small">Pasien</div><div class="fw-semibold">{{ $c->nama_pasien ?: '-' }}</div></div>
                    <div class="col-6 col-md-3 col-lg"><div class="text-muted small">SEP</div><div class="fw-semibold">{{ $c->sep ?: '-' }}</div></div>
                    <div class="col-6 col-md-3 col-lg"><div class="text-muted small">MRN</div><div class="fw-semibold">{{ $c->mrn ?: '-' }}</div></div>
                    <div class="col-6 col-md-3 col-lg"><div class="text-muted small">DPJP</div><div class="fw-semibold">{{ $c->dpjp ?: '-' }}</div></div>
                    <div class="col-6 col-md-3 col-lg"><div class="text-muted small">Tgl. pulang</div><div class="fw-semibold">{{ $c->discharge_date ? $c->discharge_date->format('d/m/Y') : '-' }}</div></div>
                    <div class="col-6 col-md-3 col-lg"><div class="text-muted small">INA-CBG</div><div class="fw-semibold"><code>{{ $c->inacbg ?: '-' }}</code></div></div>
                    <div class="col-6 col-md-3 col-lg">
                        <div class="text-muted small">Biaya {{ $cfg['label'] }}</div>
                        <div class="fs-5 fw-semibold text-{{ $cfg['color'] }}">{{ $rp($c->{$cfg['column']}) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Keterangan seksi --}}
            <div class="col-lg-8 mb-4">
                @if ($sec)
                    <div class="card border-0 border-top border-3 border-{{ $cfg['color'] }} shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i data-feather="{{ $cfg['icon'] }}" class="me-2 text-{{ $cfg['color'] }}"></i>Keterangan {{ $cfg['label'] }}
                            </h6>
                            @if ($sec['data'])
                                <span class="badge text-bg-success">Terisi</span>
                            @else
                                <span class="badge text-bg-warning">Belum diisi</span>
                            @endif
                        </div>
                        <div class="card-body">
                            @if ($sec['canEdit'])
                                <form action="{{ route('inacbg.update-keterangan', [$c, $tab]) }}" method="POST">
                                    @csrf @method('PUT')
                                    <label for="teks" class="form-label">Keterangan</label>
                                    <textarea id="teks" name="teks" rows="7" maxlength="2000" required
                                        class="form-control @error('teks') is-invalid @enderror"
                                        placeholder="Tulis keterangan {{ $cfg['label'] }} untuk SEP ini...">{{ old('teks', $sec['data']['teks'] ?? '') }}</textarea>
                                    @error('teks')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted"><span id="ketCount">0</span>/2000 karakter</small>
                                        <button type="submit" class="btn btn-{{ $cfg['color'] }}">Simpan keterangan</button>
                                    </div>
                                </form>
                            @elseif ($sec['data'])
                                <div class="p-3 bg-light rounded">{!! nl2br(e($sec['data']['teks'])) !!}</div>
                            @else
                                <p class="text-muted mb-0">Belum ada keterangan.</p>
                            @endif

                            @if ($sec['data'])
                                <div class="text-muted small mt-3 pt-3 border-top">
                                    Terakhir diisi oleh <strong>{{ $sec['data']['user_name'] ?? '-' }}</strong>
                                    @if (!empty($sec['data']['updated_at']))
                                        pada {{ \Carbon\Carbon::parse($sec['data']['updated_at'])->format('d/m/Y H:i') }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="alert alert-light border mb-0">
                        Klaim ini tidak memiliki biaya {{ $cfg['label'] }}, jadi tidak ada keterangan yang perlu diisi.
                    </div>
                @endif
            </div>

            {{-- Demografi & rawat --}}
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0"><i data-feather="calendar" class="me-2 text-primary"></i>Demografi & Rawat</h6>
                    </div>
                    <div class="card-body">
                        @foreach ($demografi as $label => $val)
                            <div class="row mb-2">
                                <div class="col-5"><div class="text-muted small">{{ $label }}</div></div>
                                <div class="col-7"><div class="fw-semibold">{{ $val ?: '-' }}</div></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Diagnosis & Prosedur --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="file-text" class="me-2 text-primary"></i>Diagnosis & Prosedur</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="text-muted small">Daftar Diagnosis</label>
                        <div class="p-3 bg-light rounded mt-1" style="max-height: 200px; overflow-y: auto;">
                            {!! $c->diaglist ? nl2br(e($c->diaglist)) : '<span class="text-muted">-</span>' !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Daftar Prosedur</label>
                        <div class="p-3 bg-light rounded mt-1" style="max-height: 200px; overflow-y: auto;">
                            {!! $c->proclist ? nl2br(e($c->proclist)) : '<span class="text-muted">-</span>' !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Tab "Semua": data lengkap + semua keterangan --}}
        @include('pages.inacbg.partials.show-semua')
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var teks = document.getElementById('teks');
            var count = document.getElementById('ketCount');
            if (teks && count) {
                var update = function() { count.textContent = teks.value.length; };
                teks.addEventListener('input', update);
                update();
            }
        });
    </script>
@endpush