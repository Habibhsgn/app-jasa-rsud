@extends('layouts.app')

@section('title', 'Laporan Hasil Index Scoring')

@section('content')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h4 class="mb-1">
                    Laporan Hasil Pembagian Jasa Pegawai
                </h4>

                <p class="text-muted mb-0">
                    Data pengajuan yang telah selesai dan disetujui.
                </p>
            </div>

            <div class="d-flex gap-2">

                {{-- FILTER --}}
                <form method="GET" action="{{ route('laporan.index.scoring.index') }}" class="d-flex gap-2">

                    <select name="periode" class="form-select">

                        <option value="">
                            Semua Periode
                        </option>

                        @foreach ($periodeList as $periode)
                            <option value="{{ $periode }}" {{ request('periode') == $periode ? 'selected' : '' }}>

                                {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}

                            </option>
                        @endforeach

                    </select>

                    <button type="submit" class="btn btn-primary">

                        <i data-feather="filter" class="me-1"></i>
                        Filter

                    </button>

                </form>


                {{-- EXPORT --}}
                <a href="{{ route('laporan.index.scoring.export', [
                    'periode' => request('periode'),
                ]) }}"
                    class="btn btn-success">

                    <i data-feather="file-text" class="me-1"></i>
                    Download Excel

                </a>


                {{-- PRINT --}}
                <button type="button" onclick="window.print()" class="btn btn-secondary">

                    <i data-feather="printer" class="me-1"></i>
                    Cetak

                </button>

            </div>

        </div>


        {{-- EMPTY --}}
        @if ($data->isEmpty())
            <div class="alert alert-info">

                <i data-feather="info" class="me-1"></i>

                Belum ada data pengajuan yang telah selesai.

            </div>
        @endif


        {{-- DATA --}}
        @foreach ($data as $item)
            <div class="card mb-4 border-success">

                {{-- HEADER --}}
                <div
                    class="card-header bg-success text-white
                d-flex justify-content-between align-items-center">

                    <div>

                        <strong>
                            Periode:
                        </strong>

                        {{ $item->periode_label }}

                        <span class="mx-2">|</span>

                        <strong>
                            Ruangan:
                        </strong>

                        {{ $item->ruangan->nama_ruangan ?? '-' }}

                    </div>


                    <span class="badge bg-light text-success">

                        Selesai

                    </span>

                </div>


                <div class="card-body">

                    {{-- INFO --}}
                    <div class="row mb-3">

                        <div class="col-md-4">

                            <small class="text-muted">
                                Jumlah Pegawai
                            </small>

                            <div class="fw-bold">
                                {{ $item->jumlah_pegawai }} Pegawai
                            </div>

                        </div>


                        <div class="col-md-4">

                            <small class="text-muted">
                                Risk
                            </small>

                            <div class="fw-bold">
                                {{ $item->ruangan->resiko ?? '-' }}
                            </div>

                        </div>


                        <div class="col-md-4">

                            <small class="text-muted">
                                Emergency
                            </small>

                            <div class="fw-bold">
                                {{ $item->ruangan->emergency ?? '-' }}
                            </div>

                        </div>

                    </div>


                    {{-- TABLE --}}
                    <div class="table-responsive">

                        <table
                            class="table table-bordered
                        table-striped table-sm
                        align-middle">

                            <thead class="table-light text-center">

                                <tr>

                                    <th>No</th>

                                    <th>Nama</th>

                                    <th>NIP/NIP3K</th>

                                    <th>Jabatan</th>

                                    <th>Pend. Formal</th>

                                    <th>Pend. Non Formal</th>

                                    <th>Gaji Pokok</th>

                                    <th>Risk</th>

                                    <th>Emergency</th>

                                    <th>Cuti</th>

                                    <th>Izin</th>

                                    <th>Tanpa Izin</th>

                                    <th>Telat</th>

                                    <th>Sikap</th>

                                    <th>Jumlah</th>

                                    <th>Keterangan</th>

                                    <th>Jumlah Akhir</th>

                                </tr>

                            </thead>


                            <tbody>

                                @foreach ($item->pegawai as $index => $p)
                                    <tr>

                                        <td class="text-center">
                                            {{ $index + 1 }}
                                        </td>

                                        <td>
                                            {{ $p->pegawai->nama ?? '-' }}
                                        </td>

                                        <td class="text-center">
                                            {{ $p->pegawai->id_petugas ?? '-' }}
                                        </td>

                                        <td>
                                            {{ $p->jabatan ?? '-' }}
                                        </td>

                                        <td class="text-center">
                                            {{ $p->pendidikan_formal ?? '-' }}
                                        </td>

                                        <td class="text-center">
                                            {{ $p->pendidikan_non_formal ?? '-' }}
                                        </td>

                                        <td class="text-end">
                                            Rp
                                            {{ number_format($p->gaji_pokok ?? 0, 0, ',', '.') }}
                                        </td>

                                        <td class="text-center">
                                            {{ $p->risk ?? 0 }}
                                        </td>

                                        <td class="text-center">
                                            {{ $p->emergency ?? 0 }}
                                        </td>

                                        <td class="text-center">
                                            {{ $p->cuti ?? 0 }}
                                        </td>

                                        <td class="text-center">
                                            {{ $p->izin ?? 0 }}
                                        </td>

                                        <td class="text-center">
                                            {{ $p->tanpa_izin ?? 0 }}
                                        </td>

                                        <td class="text-center">
                                            {{ $p->telat ?? 0 }}
                                        </td>

                                        <td class="text-center">
                                            {{ $p->sikap ?? 0 }}
                                        </td>

                                        <td class="text-center fw-bold">
                                            {{ number_format($p->jumlah ?? 0, 2, ',', '.') }}
                                        </td>

                                        <td>
                                            {{ $p->keterangan ?: '-' }}
                                        </td>

                                        <td class="text-center fw-bold text-primary">
                                            {{ number_format($p->jumlah_akhir ?? 0, 2, ',', '.') }}
                                        </td>

                                    </tr>
                                @endforeach

                            </tbody>


                            <tfoot>

                                <tr class="fw-bold bg-light">

                                    <td colspan="14" class="text-end">

                                        TOTAL

                                    </td>

                                    <td class="text-center">

                                        {{ number_format($item->total_jumlah, 2, ',', '.') }}

                                    </td>

                                    <td></td>

                                    <td class="text-center text-primary">

                                        {{ number_format($item->total_jumlah_akhir, 2, ',', '.') }}

                                    </td>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>
        @endforeach

    </div>


    <style>
        @media print {

            body * {
                visibility: hidden;
            }

            .content,
            .content * {
                visibility: visible;
            }

            .content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .btn,
            form {
                display: none !important;
            }

            .card {
                border: 1px solid #000 !important;
                margin-bottom: 20px !important;
                page-break-inside: avoid;
            }

            .card-header {
                background-color: #f8f9fa !important;
                color: #000 !important;
                border-bottom: 2px solid #000 !important;
            }

            .table {
                font-size: 9px;
            }

            .table th,
            .table td {
                padding: 3px;
            }

            @page {
                size: landscape;
                margin: 10mm;
            }

        }
    </style>

@endsection
