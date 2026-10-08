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

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===================== FORM INPUT ===================== --}}
    <div class="card">
        <div class="card-body">

            <form id="formSimpanTotal" action="{{ route('jasa.storeTotal') }}" method="POST">
                @csrf

                <div class="row g-3 align-items-end">

                    {{-- PERIODE --}}
                    <div class="col-md-3">
                        <label class="form-label">Periode (Bulan)</label>
                        <input type="month" name="periode" id="periode" class="form-control"
                            value="{{ old('periode') }}" required>
                    </div>

                    {{-- TOTAL --}}
                    <div class="col-md-4">
                        <label class="form-label">Total Jasa (Rp)</label>
                        <input type="text" name="total_jasa" class="form-control fw-bold fs-5"
                            value="{{ old('total_jasa') }}" required>
                    </div>

                    {{-- JENIS --}}
                    <div class="col-md-3">
                        <label class="form-label">Jenis Jasa</label>
                        <select name="keterangan" id="keterangan" class="form-select" required>
                            <option value="REGULER" @selected(old('keterangan') == 'REGULER')>JASA REGULER</option>
                            <option value="PENDING" @selected(old('keterangan') == 'PENDING')>JASA PENDING</option>
                        </select>
                    </div>

                    {{-- BUTTON --}}
                    <div class="col-md-2">
                        <button type="submit" id="btnGenerate" class="btn btn-primary w-100 py-2">
                            ⚡ Simpan & Generate
                        </button>
                    </div>

                </div>

                {{-- INFO OTOMATIS SUMBER DATA --}}
                <div id="infoSumber" class="mt-3 mb-0 small" style="display:none"></div>

            </form>

        </div>
    </div>

    {{-- ===================== MODAL PERINGATAN ===================== --}}
    <button type="button" id="btnBukaPeringatanGenerate" class="d-none" data-bs-toggle="modal"
        data-bs-target="#modalPeringatanGenerate"></button>

    <div class="modal fade" id="modalPeringatanGenerate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Peringatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body" id="isiPeringatanGenerate"></div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btnLanjutGenerate" class="btn btn-primary">Ya, Generate</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== LIST PERIODE ===================== --}}
    @foreach ($periodes as $periode)
        <div class="card mt-4 border">

            {{-- HEADER --}}
            <div class="card-header d-flex justify-content-between align-items-center">

                <div>
                    <h5 class="mb-0">
                        Periode: {{ \Carbon\Carbon::parse($periode->periode . '-01')->translatedFormat('F Y') }}
                    </h5>

                    <small class="text-muted">
                        Total: Rp {{ number_format($periode->total_jasa, 0, ',', '.') }}
                    </small>
                    <br>

                    @if ($periode->keterangan == 'REGULER')
                        <span class="badge bg-primary">JASA REGULER</span>
                    @else
                        <span class="badge bg-success">JASA PENDING</span>
                        <small class="text-muted ms-1">
                            Data ruangan &amp; pegawai mengikuti Jasa Reguler
                            {{ \Carbon\Carbon::parse($periode->periode . '-01')->translatedFormat('F Y') }}
                        </small>
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
                                        @if ($item->ruangan && !$item->ruangan->is_active)
                                            <span class="badge bg-light text-muted border ms-1">nonaktif</span>
                                        @endif
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
                                        @elseif ($item->status == 'selesai')
                                            <span class="badge bg-success">Selesai</span>
                                        @elseif ($item->status == 'revisi')
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
                                    {{ round($totalPersen, 2) }}%
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
                        <button class="btn btn-success px-5 py-2 fw-bold"
                            onclick="selesaiPembagian({{ $periode->id }})">
                            🔥 Kunci & Kirim ke KARU
                        </button>
                    </div>
                @endif

            </div>

        </div>
    @endforeach

    {{-- ===================== SCRIPT ===================== --}}
    <script>
        // Format rupiah saat mengetik
        const inputTotalJasa = document.querySelector('input[name="total_jasa"]');

        if (inputTotalJasa) {
            inputTotalJasa.addEventListener('input', function() {
                const val = this.value.replace(/[^0-9]/g, '');
                this.value = val ? new Intl.NumberFormat('id-ID').format(val) : '';
            });
        }

        // Kunci & kirim ke KARU
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
                })
                .catch(() => alert('Terjadi kesalahan saat mengirim data.'));
        }

        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById('formSimpanTotal');
            const btnLanjut = document.getElementById('btnLanjutGenerate');
            const btnGen = document.getElementById('btnGenerate');
            const inpPeriode = document.getElementById('periode');
            const selKet = document.getElementById('keterangan');
            const info = document.getElementById('infoSumber');
            const isiModal = document.getElementById('isiPeringatanGenerate');

            // { "2026-01": "selesai", "2026-02": "draft", ... }
            const reguler = @json($periodeReguler);

            function labelBulan(ym) {
                if (!ym) return '';
                const [y, m] = ym.split('-');
                return new Date(y, m - 1, 1).toLocaleDateString('id-ID', {
                    month: 'long',
                    year: 'numeric'
                });
            }

            function setInfo(kelas, html) {
                info.style.display = 'block';
                info.className = 'alert ' + kelas + ' mt-3 mb-0 small';
                info.innerHTML = html;
            }

            function updateInfo() {
                const ym = inpPeriode.value;
                btnGen.disabled = false;

                if (selKet.value !== 'PENDING') {
                    setInfo('alert-light border',
                        'Pembagian mengikuti data <strong>Master Ruangan</strong> saat ini.');
                    return;
                }

                if (!ym) {
                    setInfo('alert-light border', 'Pilih bulan terlebih dahulu.');
                    return;
                }

                const status = reguler[ym];
                const bulan = labelBulan(ym);

                if (!status) {
                    setInfo('alert-danger',
                        'Belum ada jasa <strong>REGULER ' + bulan + '</strong>. Pending tidak bisa dibuat.');
                    btnGen.disabled = true;
                } else if (status === 'draft') {
                    setInfo('alert-warning',
                        'Jasa <strong>REGULER ' + bulan +
                        '</strong> masih draft. Selesaikan dulu sebelum membuat pending.');
                    btnGen.disabled = true;
                } else {
                    setInfo('alert-success',
                        '✔ Daftar ruangan &amp; pegawai mengikuti <strong>Reguler ' + bulan +
                        '</strong>. Pembagian per pegawai akan diisi oleh KARU.');
                }
            }

            inpPeriode.addEventListener('change', updateInfo);
            selKet.addEventListener('change', updateInfo);
            updateInfo();

            // Tampilkan modal konfirmasi sebelum submit
            form.addEventListener('submit', function(e) {
                if (form.dataset.confirmed === '1') return;
                e.preventDefault();

                const bulan = labelBulan(inpPeriode.value);

                isiModal.innerHTML = selKet.value === 'PENDING' ?
                    'Jasa <strong>PENDING ' + bulan +
                    '</strong> akan menyalin <strong>daftar ruangan dan pegawai</strong> dari ' +
                    '<strong>Reguler ' + bulan +
                    '</strong>. Persen &amp; nominal per pegawai dikosongkan dan diisi oleh KARU.' :
                    'Pastikan data ruangan dan persentase <strong>penerima jasa 30%</strong> di menu ' +
                    '<strong>Master Ruangan</strong> sudah benar sebelum generate. Hasil generate akan mengikuti data tersebut.';
                document.getElementById('btnBukaPeringatanGenerate').click();
            });

            btnLanjut.addEventListener('click', function() {
                this.disabled = true;
                this.innerText = 'Memproses...';
                form.dataset.confirmed = '1';
                form.submit();
            });
        });
    </script>

@endsection
