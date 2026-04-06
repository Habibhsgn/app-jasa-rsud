@extends('layouts.app')

@section('title', 'Laporan Jasa Pegawai')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><strong>Laporan Hasil Pembagian Jasa Pegawai</strong></h1>
        <div>
            <a href="{{ route('laporan.jasa.export') }}" class="btn btn-success">📗 Download Excel</a>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Cetak Laporan</button>
        </div>
    </div>

    @if ($data->isEmpty())
        <div class="alert alert-info">Belum ada data pembagian jasa yang disubmit (selesai).</div>
    @endif

    @foreach ($data as $item)
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <div>
                    <strong>Periode:</strong> {{ $item->periode->periode ?? '-' }} |
                    <strong>Ruangan:</strong> {{ $item->ruangan->nama_ruangan ?? '-' }}
                </div>
                <div>
                    <strong>Alokasi Ruangan: Rp {{ number_format($item->nominal, 0, ',', '.') }}</strong>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th>Nama Pegawai</th>
                                <th>Jabatan</th>
                                <th width="15%">Persentase</th>
                                <th width="20%">Nominal Terimakan</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalPersen = 0;
                                $totalNominal = 0;
                            @endphp
                            @foreach ($item->pegawai as $index => $p)
                                @php
                                    $jp = \App\Models\JasaPegawai::where('jasa_ruangan_id', $item->id)
                                        ->where('pegawai_id', $p->id)
                                        ->first();

                                    $persen = $jp ? $jp->persen : 0;
                                    $nominal = $jp ? $jp->nominal : 0;

                                    $totalPersen += $persen;
                                    $totalNominal += $nominal;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $p->nama }}</td>
                                    <td>{{ $p->jabatan }}</td>
                                    <td>{{ (float) $persen }}%</td>
                                    <td>Rp {{ number_format($nominal, 0, ',', '.') }}</td>
                                    <td>{{ $jp ? $jp->keterangan : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold bg-light">
                                <td colspan="3" class="text-end">TOTAL KESELURUHAN:</td>
                                <td>{{ (float) $totalPersen }}%</td>
                                <td class="text-success">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    <style>
        /* Styling sederhana agar rapi saat dicetak / print */
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

            .btn {
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
        }
    </style>
@endsection
