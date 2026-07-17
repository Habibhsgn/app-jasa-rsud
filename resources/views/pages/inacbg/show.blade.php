@extends('layouts.app')

@section('title', 'Detail Klaim INA-CBG')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/inacbg-show.css') }}">
@endpush

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1"><strong>Detail Klaim INA-CBG</strong></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('inacbg.index') }}">INA-CBG</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('inacbg.index') }}" class="btn btn-outline-secondary">
            <i data-feather="arrow-left" class="me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    {{-- Status & Aksi --}}
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <strong>Status:</strong>
                    @if ($inacbgClaim->status == 'disetujui')
                        <span class="badge bg-success ms-2"><i data-feather="circle-check" class="me-1"></i>Disetujui</span>
                    @else
                        <span class="badge bg-warning ms-2"><i data-feather="clock" class="me-1"></i>Pending</span>
                    @endif

                    @if ($inacbgClaim->tgl_verifikasi)
                        <span class="ms-3 text-muted small">
                            <i data-feather="calendar" class="me-1"></i>Verifikasi: {{ $inacbgClaim->tgl_verifikasi->format('d/m/Y') }}
                        </span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @if ($inacbgClaim->status == 'pending')
                    <form action="{{ route('inacbg.update-status', $inacbgClaim) }}" method="POST" class="d-inline" onsubmit="return confirm('Setujui klaim ini?')">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="disetujui">
                        <button type="submit" class="btn btn-success btn-sm"><i data-feather="check" class="me-1"></i>Setujui</button>
                    </form>
                    @else
                    <form action="{{ route('inacbg.update-status', $inacbgClaim) }}" method="POST" class="d-inline" onsubmit="return confirm('Kembalikan ke pending?')">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="pending">
                        <button type="submit" class="btn btn-warning btn-sm"><i data-feather="rotate-clockwise-2" class="me-1"></i>Pending</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Perhitungan Jasa Staff --}}
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i data-feather="chart-pie" class="me-2 text-primary"></i>Perhitungan Jasa Staff</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Total Jasa:</strong> Rp {{ number_format($jasaData['totalJasa'], 2) }} |
                    <strong>Jasa Staff ({{ $jasaData['persenStaff'] }}%):</strong> Rp {{ number_format($jasaData['jasaStaff'], 2) }}
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Jabatan</th>
                                <th>Persen</th>
                                <th class="text-end">Jumlah Jasa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jasaData['breakdown'] as $item)
                                <tr>
                                    <td>{{ $item['label'] }}</td>
                                    <td>{{ number_format($item['persen'], 2) }}%</td>
                                    <td class="text-end">Rp {{ number_format($item['jumlah'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-light">
                                <td class="fw-bold">Total</td>
                                <td>100%</td>
                                <td class="text-end fw-bold">Rp {{ number_format($jasaData['grandTotalStaff'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Informasi Pasien --}}
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="user" class="me-2 text-primary"></i>Informasi Pasien</h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Nama Pasien</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->nama_pasien ?: '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">MRN</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->mrn ?: '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">SEP</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->sep ?: '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">DPJP</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->dpjp ?: '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Payor ID</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->payor_id ?: '-' }}</div></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="calendar" class="me-2 text-primary"></i>Demografi & Rawat</h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Tanggal Lahir</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->birth_date ? $inacbgClaim->birth_date->format('d/m/Y') : '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Usia</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->umur_tahun ? $inacbgClaim->umur_tahun . ' tahun' : '' }} {{ $inacbgClaim->umur_hari ? $inacbgClaim->umur_hari . ' hari' : '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Jenis Kelamin</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->sex == 'L' ? 'Laki-laki' : ($inacbgClaim->sex == 'P' ? 'Perempuan' : '-') }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Kelas RS</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->kelas_rs ?: '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Kelas Rawat</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->kelas_rawat ?: '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Admisi</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->admission_date ? $inacbgClaim->admission_date->format('d/m/Y') : '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Discharge</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->discharge_date ? $inacbgClaim->discharge_date->format('d/m/Y') : '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">LOS</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->los ? $inacbgClaim->los . ' hari' : '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Discharge Status</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->discharge_status ?: '-' }}</div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- INA-CBG & Tarif --}}
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="code" class="me-2 text-primary"></i>INA-CBG Grouper</h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Kode INA-CBG</div></div>
                    <div class="col-8"><div class="fw-semibold"><code>{{ $inacbgClaim->inacbg ?: '-' }}</code></div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Deskripsi</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->deskripsi_inacbg ?: '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Subacute</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->subacute ?: '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Chronic</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->chronic ?: '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Versi INA-CBG</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->versi_inacbg ?: '-' }}</div></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Versi Grouper</div></div>
                    <div class="col-8"><div class="fw-semibold">{{ $inacbgClaim->versi_grouper ?: '-' }}</div></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="coin" class="me-2 text-primary"></i>Tarif</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr><td>Tarif INA-CBG</td><td class="text-end">Rp {{ number_format($inacbgClaim->tarif_inacbg, 2) }}</td></tr>
                            <tr><td>Tarif Subacute</td><td class="text-end">Rp {{ number_format($inacbgClaim->tarif_subacute, 2) }}</td></tr>
                            <tr><td>Tarif Chronic</td><td class="text-end">Rp {{ number_format($inacbgClaim->tarif_chronic, 2) }}</td></tr>
                            <tr><td>SP</td><td class="text-end">Rp {{ number_format($inacbgClaim->tarif_sp, 2) }}</td></tr>
                            <tr><td>SR</td><td class="text-end">Rp {{ number_format($inacbgClaim->tarif_sr, 2) }}</td></tr>
                            <tr><td>SI</td><td class="text-end">Rp {{ number_format($inacbgClaim->tarif_si, 2) }}</td></tr>
                            <tr><td>SD</td><td class="text-end">Rp {{ number_format($inacbgClaim->tarif_sd, 2) }}</td></tr>
                            <tr class="border-top"><td class="fw-bold">Total Tarif</td><td class="text-end fw-bold">Rp {{ number_format($inacbgClaim->total_tarif, 2) }}</td></tr>
                            <tr><td class="fw-bold">Tarif RS</td><td class="text-end fw-bold">Rp {{ number_format($inacbgClaim->tarif_rs, 2) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="circle-check" class="me-2 text-primary"></i>Verifikasi & Klaim</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr><td>Tgl. Verifikasi</td><td class="text-end">{{ $inacbgClaim->tgl_verifikasi ? $inacbgClaim->tgl_verifikasi->format('d/m/Y') : '-' }}</td></tr>
                            <tr><td>Biaya Riil RS</td><td class="text-end">Rp {{ number_format($inacbgClaim->biaya_riil_rs, 2) }}</td></tr>
                            <tr><td>Biaya Diajukan</td><td class="text-end">Rp {{ number_format($inacbgClaim->biaya_diajukan, 2) }}</td></tr>
                            <tr><td>Biaya Disetujui</td><td class="text-end">Rp {{ number_format($inacbgClaim->biaya_disetujui, 2) }}</td></tr>
                            <tr class="border-top">
                                <td class="fw-bold">Status</td>
                                <td class="text-end">
                                    @if ($inacbgClaim->status == 'disetujui')
                                        <span class="badge bg-success">Disetujui</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Diagnosis & Prosedur --}}
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="notes" class="me-2 text-primary"></i>Diagnosis & Prosedur</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="text-muted small">Daftar Diagnosis</label>
                        <div class="p-3 bg-light rounded mt-1" style="max-height: 200px; overflow-y: auto;">
                            {!! $inacbgClaim->diaglist ? nl2br(e($inacbgClaim->diaglist)) : '<span class="text-muted">-</span>' !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Daftar Prosedur</label>
                        <div class="p-3 bg-light rounded mt-1" style="max-height: 200px; overflow-y: auto;">
                            {!! $inacbgClaim->proclist ? nl2br(e($inacbgClaim->proclist)) : '<span class="text-muted">-</span>' !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- iDRG --}}
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="chart-bar" class="me-2 text-primary"></i>iDRG</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2"><div class="text-muted small">MDC</div><div class="fw-semibold">{{ $inacbgClaim->idrg_mdc_number ? "{$inacbgClaim->idrg_mdc_number} - {$inacbgClaim->idrg_mdc_description}" : '-' }}</div></div>
                    <div class="col-md-3 mb-2"><div class="text-muted small">DRG</div><div class="fw-semibold">{{ $inacbgClaim->idrg_drg_code ? "{$inacbgClaim->idrg_drg_code} - {$inacbgClaim->idrg_drg_description}" : '-' }}</div></div>
                    <div class="col-md-2 mb-2"><div class="text-muted small">Cost Weight</div><div class="fw-semibold">{{ $inacbgClaim->idrg_cost_weight ?: '-' }}</div></div>
                    <div class="col-md-2 mb-2"><div class="text-muted small">Total Cost Weight</div><div class="fw-semibold">{{ $inacbgClaim->idrg_total_cost_weight ?: '-' }}</div></div>
                    <div class="col-md-2 mb-2"><div class="text-muted small">Total Tarif</div><div class="fw-semibold">Rp {{ number_format($inacbgClaim->idrg_total_tarif, 2) }}</div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Komponen Biaya RS --}}
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="receipt" class="me-2 text-primary"></i>Komponen Biaya Rumah Sakit</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Komponen</th>
                                <th class="text-end" style="width: 200px;">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $komponen = [
                                    'Prosedur Non Bedah' => $inacbgClaim->prosedur_non_bedah,
                                    'Prosedur Bedah' => $inacbgClaim->prosedur_bedah,
                                    'Konsultasi' => $inacbgClaim->konsultasi,
                                    'Tenaga Ahli' => $inacbgClaim->tenaga_ahli,
                                    'Keperawatan' => $inacbgClaim->keperawatan,
                                    'Penunjang' => $inacbgClaim->penunjang,
                                    'Radiologi' => $inacbgClaim->radiologi,
                                    'Laboratorium' => $inacbgClaim->laboratorium,
                                    'Pelayanan Darah' => $inacbgClaim->pelayanan_darah,
                                    'Rehabilitasi' => $inacbgClaim->rehabilitasi,
                                    'Kamar Akomodasi' => $inacbgClaim->kamar_akomodasi,
                                    'Rawat Intensif' => $inacbgClaim->rawat_intensif,
                                    'Obat' => $inacbgClaim->obat,
                                    'Alkes' => $inacbgClaim->alkes,
                                    'BMHP' => $inacbgClaim->bmhp,
                                    'Sewa Alat' => $inacbgClaim->sewa_alat,
                                    'Obat Kronis' => $inacbgClaim->obat_kronis,
                                    'Obat Kemo' => $inacbgClaim->obat_kemo,
                                ];
                            @endphp
                            @foreach ($komponen as $label => $nilai)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-end">Rp {{ number_format($nilai, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
