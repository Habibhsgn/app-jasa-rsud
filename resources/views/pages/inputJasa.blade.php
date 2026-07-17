@extends('layouts.app')
@section('title', 'Input Jasa & Generate')

@section('content')

    <h1 class="h3 mb-3">
        <strong>Input Jasa & Pembagian Ruangan</strong>
    </h1>

    {{-- ALERT --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- FORM INPUT --}}
    <div class="card">
        <div class="card-body">

            <form id="formSimpanTotal" action="{{ route('jasa.storeTotal') }}" method="POST">

                @csrf

                <div class="row align-items-end">

                    {{-- PERIODE --}}
                    <div class="col-md-3">
                        <label class="form-label">Periode (Bulan)</label>
                        <input type="month" name="periode" class="form-control" required>
                    </div>

                    {{-- TOTAL --}}
                    <div class="col-md-4">
                        <label class="form-label">Total Jasa (Rp)</label>
                        <input type="text" name="total_jasa" class="form-control fw-bold fs-5" required>
                    </div>

                    {{-- KETERANGAN (BARU) --}}
                    <div class="col-md-3">
                        <label class="form-label">Jenis Jasa</label>
                        <select name="keterangan" class="form-select" required>
                            <option value="REGULER">JASA REGULER</option>
                            <option value="PENDING">JASA PENDING</option>
                        </select>
                    </div>

                    {{-- BUTTON --}}
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            ⚡ Simpan & Generate
                        </button>
                    </div>

                </div>

            </form>

        </div>
    </div>

    {{-- LIST PERIODE --}}
    @foreach ($periodes as $periode)
        <div class="card mt-4 border">

            {{-- HEADER --}}
            <div class="card-header d-flex justify-content-between align-items-center">

                <div>
                    <h5 class="mb-0">
                        Periode: {{ \Carbon\Carbon::parse($periode->periode)->translatedFormat('F Y') }}
                    </h5>

                    <small class="text-muted">
                        Total: Rp {{ number_format($periode->total_jasa, 0, ',', '.') }}
                    </small>
                    <br>
                    {{-- 🔥 BADGE KETERANGAN --}}
                    @if ($periode->keterangan == 'REGULER')
                        <span class="badge bg-primary">JASA REGULER</span>
                    @else
                        <span class="badge bg-success">JASA PENDING</span>
                    @endif
                </div>

                <div class="d-flex gap-2 align-items-center">



                    {{-- STATUS --}}
                    <span class="badge bg-{{ $periode->status == 'draft' ? 'secondary' : 'warning text-dark' }}">
                        {{ strtoupper(str_replace('_', ' ', $periode->status)) }}
                    </span>

                    {{-- DELETE --}}
                    @if ($periode->status == 'draft')
                        <form action="{{ route('jasa.destroyPeriode', $periode->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus draft?')">
                                Hapus
                            </button>
                        </form>
                    @endif

                </div>

            </div>

            {{-- BODY --}}
            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-striped table-sm align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Ruangan</th>
                                <th class="text-center">Persen</th>
                                <th class="text-end">Nominal</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            @php
                                $totalPersen = 0;
                                $totalNominal = 0;
                            @endphp

                            @forelse ($periode->pembagianRuangan as $index => $item)
                                @php
                                    $totalPersen += $item->persen;
                                    $totalNominal += $item->nominal;
                                @endphp

                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>

                                    <td class="fw-bold">
                                        {{ $item->ruangan->nama_ruangan ?? '-' }}
                                    </td>

                                    <td class="text-center">
                                        {{ $item->persen }}%
                                    </td>

                                    <td class="text-end">
                                        Rp {{ number_format($item->nominal, 0, ',', '.') }}
                                    </td>

                                    <td class="text-center">
                                        @if ($item->status == 'draft')
                                            <span class="badge bg-secondary">Draft</span>
                                        @elseif($item->status == 'selesai')
                                            <span class="badge bg-success">Selesai</span>
                                        @elseif($item->status == 'revisi')
                                            <span class="badge bg-danger">Revisi</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Proses</span>
                                        @endif
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        Tidak ada data
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                        <tfoot>
                            <tr class="fw-bold bg-light">
                                <td colspan="2" class="text-end">TOTAL</td>
                                <td class="text-center text-primary">
                                    {{ $totalPersen }}%
                                </td>
                                <td class="text-end text-success">
                                    Rp {{ number_format($totalNominal, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>

                    </table>

                </div>

                {{-- BUTTON KIRIM --}}
                @if ($periode->status == 'draft')
                    <div class="mt-4 text-end">

                        <button class="btn btn-success px-5 py-2 fw-bold" onclick="selesaiPembagian({{ $periode->id }})">

                            🔥 Kunci & Kirim ke KARU

                        </button>

                    </div>
                @endif

            </div>

        </div>
    @endforeach

    {{-- SCRIPT --}}
    <script>
        let inputTotalJasa = document.querySelector('input[name="total_jasa"]');

        if (inputTotalJasa) {
            inputTotalJasa.addEventListener('input', function() {
                let val = this.value.replace(/[^0-9]/g, '');
                this.value = val ? new Intl.NumberFormat('id-ID').format(val) : '';
            });
        }

        function selesaiPembagian(id) {

            if (!confirm('Yakin kirim ke KARU?')) return;

            fetch(`/jasa/selesai-pembagian/${id}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    }
                })
                .then(res => res.json())
                .then(res => {

                    if (res.status === 'success') {
                        alert('Berhasil dikirim');
                        location.reload();
                    } else {
                        alert(res.message);
                    }

                });

        }
    </script>

@endsection
