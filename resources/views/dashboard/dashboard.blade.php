@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <h1 class="h3 mb-3">
        <strong>Analytics</strong> Dashboard
    </h1>

    <div class="container-fluid p-0">

        {{-- ====================================================== --}}
        {{-- STATISTIC CARD --}}
        {{-- ====================================================== --}}

        <div class="row">

            {{-- TOTAL PEGAWAI --}}

            <div class="col-xl-3 col-md-6">

                <div class="card bg-primary text-white mb-3">

                    <div class="card-body">

                        <h5 class="card-title">
                            {{ $labelPegawai }}
                        </h5>

                        <h2>
                            {{ $totalPegawai }}
                        </h2>

                    </div>

                </div>

            </div>


            {{-- TOTAL RUANGAN --}}

            <div class="col-xl-3 col-md-6">

                <div class="card bg-success text-white mb-3">

                    <div class="card-body">

                        <h5 class="card-title">
                            Total Ruangan
                        </h5>

                        <h2>
                            {{ $totalRuangan }}
                        </h2>

                    </div>

                </div>

            </div>


            {{-- PROSES KARU --}}

            <div class="col-xl-2 col-md-6">

                <div class="card bg-warning text-dark mb-3">

                    <div class="card-body">

                        <h5>
                            Proses Karu
                        </h5>

                        <h2>
                            {{ $totalProses }}
                        </h2>

                    </div>

                </div>

            </div>


            {{-- VERIFIKASI --}}

            <div class="col-xl-2 col-md-6">

                <div class="card bg-info text-white mb-3">

                    <div class="card-body">

                        <h5>
                            Verifikasi
                        </h5>

                        <h2>
                            {{ $totalVerifikasi }}
                        </h2>

                    </div>

                </div>

            </div>


            {{-- SELESAI --}}

            <div class="col-xl-2 col-md-6">

                <div class="card bg-secondary text-white mb-3">

                    <div class="card-body">

                        <h5>
                            Selesai
                        </h5>

                        <h2>
                            {{ $totalSelesai }}
                        </h2>

                    </div>

                </div>

            </div>

        </div>


        {{-- ====================================================== --}}
        {{-- DATA DASHBOARD --}}
        {{-- ====================================================== --}}

        <div class="row">

            {{-- ================================================== --}}
            {{-- KOLOM KIRI : PROGRESS JASA --}}
            {{-- ================================================== --}}

            <div class="col-lg-6">

                <div class="card shadow-sm mb-4">

                    <div class="card-header bg-success text-white">

                        <h5 class="mb-0">

                            Progress Jasa Ruangan

                        </h5>

                    </div>


                    <div class="card-body">

                        @forelse($data as $periode => $items)

                            @php

                                $jasaCollapseId = 'jasa_' . md5($periode);

                            @endphp


                            <div class="card mb-3 border-success">

                                <div
                                    class="card-header
                                    d-flex
                                    justify-content-between
                                    align-items-center">

                                    <strong>

                                        Periode
                                        {{ $periode }}

                                    </strong>


                                    <button
                                        class="btn
                                        btn-sm
                                        btn-success"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#{{ $jasaCollapseId }}"
                                        aria-expanded="false" aria-controls="{{ $jasaCollapseId }}">

                                        <span class="collapse-text">

                                            Expand

                                        </span>

                                    </button>

                                </div>


                                {{-- COLLAPSE JASA --}}

                                <div id="{{ $jasaCollapseId }}" class="collapse">

                                    <div class="table-responsive">

                                        <table
                                            class="table
                                            table-hover
                                            table-sm
                                            mb-0">

                                            <thead class="table-light">

                                                <tr>

                                                    <th>
                                                        Ruangan
                                                    </th>


                                                    @if ($koordinator)
                                                        <th>
                                                            Nominal
                                                        </th>
                                                    @endif


                                                    <th>
                                                        Status
                                                    </th>


                                                    @if (!$koordinator)
                                                        <th class="text-center">
                                                            Persen
                                                        </th>
                                                    @endif


                                                    <th>
                                                        Catatan
                                                    </th>

                                                </tr>

                                            </thead>


                                            <tbody>

                                                @foreach ($items as $item)
                                                    <tr>

                                                        {{-- RUANGAN --}}

                                                        <td>

                                                            {{ $item->ruangan?->nama_ruangan ?? 'N/A' }}

                                                        </td>


                                                        {{-- NOMINAL --}}

                                                        @if ($koordinator)
                                                            <td>

                                                                Rp
                                                                {{ number_format($item->nominal ?? 0, 0, ',', '.') }}

                                                            </td>
                                                        @endif


                                                        {{-- STATUS --}}

                                                        <td>

                                                            @if ($item->status === 'selesai')
                                                                <span
                                                                    class="badge
                                                                    bg-success">

                                                                    Selesai

                                                                </span>
                                                            @elseif ($item->status === 'proses_karu')
                                                                <span
                                                                    class="badge
                                                                    bg-warning
                                                                    text-dark">

                                                                    Proses Karu

                                                                </span>
                                                            @elseif ($item->status === 'verifikasi')
                                                                <span
                                                                    class="badge
                                                                    bg-info
                                                                    text-dark">

                                                                    Verifikasi

                                                                </span>
                                                            @else
                                                                <span
                                                                    class="badge
                                                                    bg-secondary">

                                                                    {{ $item->status }}

                                                                </span>
                                                            @endif

                                                        </td>


                                                        {{-- PERSEN --}}

                                                        @if (!$koordinator)
                                                            <td
                                                                class="
                                                                text-center">

                                                                {{ (float) ($item->persen ?? 0) }}%

                                                            </td>
                                                        @endif


                                                        {{-- CATATAN --}}

                                                        <td>

                                                            <small
                                                                class="
                                                                text-muted">

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

                            <div
                                class="alert
                                alert-light
                                text-center
                                text-muted">

                                Tidak ada data jasa ditemukan.

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>


            {{-- ================================================== --}}
            {{-- KOLOM KANAN : INDEX SCORING --}}
            {{-- ================================================== --}}

            <div class="col-lg-6">

                <div class="card shadow-sm mb-4">

                    <div class="card-header bg-primary text-white">

                        <h5 class="mb-0">

                            Progress Index Scoring

                        </h5>

                    </div>


                    <div class="card-body">

                        @forelse(
                                $dataIndexScoring
                                as $bidang => $periodeGroups
                            )

                            @php

                                $bidangCollapseId = 'bidang_' . md5($bidang);

                            @endphp


                            {{-- ================================== --}}
                            {{-- CARD BIDANG --}}
                            {{-- ================================== --}}

                            <div
                                class="card
                                mb-3
                                border-primary">

                                <div
                                    class="card-header
                                    bg-light
                                    d-flex
                                    justify-content-between
                                    align-items-center">

                                    <strong>

                                        Bidang:
                                        {{ $bidang }}

                                    </strong>


                                    <button
                                        class="btn
                                        btn-sm
                                        btn-primary"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#{{ $bidangCollapseId }}"
                                        aria-expanded="false" aria-controls="{{ $bidangCollapseId }}">

                                        <span class="collapse-text">

                                            Expand

                                        </span>

                                    </button>

                                </div>


                                {{-- COLLAPSE BIDANG --}}

                                <div id="{{ $bidangCollapseId }}" class="collapse">

                                    <div class="card-body">

                                        @foreach ($periodeGroups as $periode => $rooms)
                                            @php

                                                $periodeCollapseId = 'periode_' . md5($bidang . '_' . $periode);

                                            @endphp


                                            {{-- ================== --}}
                                            {{-- CARD PERIODE --}}
                                            {{-- ================== --}}

                                            <div
                                                class="card
                                                mb-3
                                                border-secondary">

                                                <div
                                                    class="card-header
                                                    d-flex
                                                    justify-content-between
                                                    align-items-center">

                                                    <strong>

                                                        @if ($periode !== 'unknown')
                                                            Periode:

                                                            {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
                                                        @else
                                                            Periode: -
                                                        @endif

                                                    </strong>


                                                    <button
                                                        class="btn
                                                        btn-sm
                                                        btn-outline-secondary"
                                                        type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#{{ $periodeCollapseId }}" aria-expanded="false"
                                                        aria-controls="{{ $periodeCollapseId }}">

                                                        <span class="collapse-text">

                                                            Expand

                                                        </span>

                                                    </button>

                                                </div>


                                                {{-- COLLAPSE PERIODE --}}

                                                <div id="{{ $periodeCollapseId }}" class="collapse">

                                                    <div class="table-responsive">

                                                        <table
                                                            class="table
                                                            table-hover
                                                            table-sm
                                                            mb-0">

                                                            <thead
                                                                class="
                                                                table-light">

                                                                <tr>

                                                                    <th>
                                                                        Ruangan
                                                                    </th>

                                                                    <th>
                                                                        Progress
                                                                    </th>

                                                                    <th>
                                                                        Status
                                                                    </th>

                                                                </tr>

                                                            </thead>


                                                            <tbody>

                                                                @foreach ($rooms as $room)
                                                                    <tr>

                                                                        {{-- RUANGAN --}}

                                                                        <td>

                                                                            <strong>

                                                                                {{ $room->nama_ruangan }}

                                                                            </strong>

                                                                            <br>

                                                                            <small
                                                                                class="
                                                                                text-muted">

                                                                                {{ $room->jumlah_pegawai_index }}

                                                                                /

                                                                                {{ $room->total_pegawai }}

                                                                                Pegawai

                                                                            </small>

                                                                        </td>


                                                                        {{-- PROGRESS --}}

                                                                        <td
                                                                            style="
                                                                            min-width:
                                                                            150px">

                                                                            <div
                                                                                class="
                                                                                d-flex
                                                                                justify-content-between
                                                                                mb-1">

                                                                                <small>

                                                                                    {{ $room->progress }}%

                                                                                </small>

                                                                            </div>


                                                                            <div class="
                                                                                progress"
                                                                                style="
                                                                                height:
                                                                                10px">

                                                                                <div class="
                                                                                    progress-bar

                                                                                    @if ($room->progress == 100) bg-success

                                                                                    @elseif($room->progress >= 50)

                                                                                        bg-primary

                                                                                    @else

                                                                                        bg-warning @endif
                                                                                    "
                                                                                    role="
                                                                                    progressbar"
                                                                                    style="
                                                                                    width:
                                                                                    {{ $room->progress }}%"
                                                                                    aria-valuenow="
                                                                                    {{ $room->progress }}"
                                                                                    aria-valuemin="0" aria-valuemax="100">

                                                                                </div>

                                                                            </div>

                                                                        </td>


                                                                        {{-- STATUS PENGAJUAN --}}

                                                                        <td>

                                                                            @if ($room->status_pengajuan === 'draft')
                                                                                <span
                                                                                    class="
                                                                                    badge
                                                                                    bg-secondary">

                                                                                    Draft

                                                                                </span>
                                                                            @elseif($room->status_pengajuan === 'submit')
                                                                                <span
                                                                                    class="
                                                                                    badge
                                                                                    bg-primary">

                                                                                    Submit

                                                                                </span>
                                                                            @elseif($room->status_pengajuan === 'verifikasi')
                                                                                <span
                                                                                    class="
                                                                                    badge
                                                                                    bg-info
                                                                                    text-dark">

                                                                                    Verifikasi

                                                                                </span>
                                                                            @elseif($room->status_pengajuan === 'revisi')
                                                                                <span
                                                                                    class="
                                                                                    badge
                                                                                    bg-warning
                                                                                    text-dark">

                                                                                    Revisi

                                                                                </span>
                                                                            @elseif($room->status_pengajuan === 'selesai')
                                                                                <span
                                                                                    class="
                                                                                    badge
                                                                                    bg-success">

                                                                                    Selesai

                                                                                </span>
                                                                            @else
                                                                                <span
                                                                                    class="
                                                                                    badge
                                                                                    bg-secondary">

                                                                                    {{ $room->status_pengajuan }}

                                                                                </span>
                                                                            @endif

                                                                        </td>

                                                                    </tr>
                                                                @endforeach

                                                            </tbody>

                                                        </table>

                                                    </div>

                                                </div>

                                            </div>
                                        @endforeach

                                    </div>

                                </div>

                            </div>

                        @empty

                            <div
                                class="alert
                                alert-light
                                text-center
                                text-muted">

                                Belum ada data
                                Index Scoring.

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- SCRIPT COLLAPSE --}}
    {{-- ====================================================== --}}

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {

                document
                    .querySelectorAll('.collapse')
                    .forEach(function(collapseElement) {

                        /*
                        |--------------------------------------------------------------------------
                        | SAAT DIBUKA
                        |--------------------------------------------------------------------------
                        */

                        collapseElement.addEventListener(
                            'show.bs.collapse',
                            function() {

                                const button =
                                    document.querySelector(
                                        '[data-bs-target="#' +
                                        collapseElement.id +
                                        '"]'
                                    );

                                if (!button) {
                                    return;
                                }

                                const text =
                                    button.querySelector(
                                        '.collapse-text'
                                    );

                                if (text) {

                                    text.textContent =
                                        'Minimize';

                                }

                            }
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | SAAT DITUTUP
                        |--------------------------------------------------------------------------
                        */

                        collapseElement.addEventListener(
                            'hide.bs.collapse',
                            function() {

                                const button =
                                    document.querySelector(
                                        '[data-bs-target="#' +
                                        collapseElement.id +
                                        '"]'
                                    );

                                if (!button) {
                                    return;
                                }

                                const text =
                                    button.querySelector(
                                        '.collapse-text'
                                    );

                                if (text) {

                                    text.textContent =
                                        'Expand';

                                }

                            }
                        );

                    });

            }
        );
    </script>

@endsection
