@extends('layouts.app')

@section('title', 'Master Ruangan & Persentase')

@section('content')
    @php
        $totalPas = round($totalPersen, 2) == 100;
    @endphp

    {{-- ===================== HEADER ===================== --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0"><strong>Master Ruangan & Persentase</strong></h1>

        <button type="button" id="btnTambahRuangan" class="btn btn-primary" data-bs-toggle="modal"
            data-bs-target="#modalTambahRuangan">
            <i class="align-middle" data-feather="plus"></i>
            Tambah Ruangan
        </button>
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
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===================== TABEL RUANGAN ===================== --}}
    <div class="card">
        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0">Master Ruangan</h5>

            <span id="badgeTotal" class="badge bg-{{ $totalPas ? 'success' : 'danger' }} fs-6">
                Total Persentase Penerima Jasa 30% : {{ number_format($totalPersen, 2) }}%
            </span>
        </div>

        <div class="card-body">
            <p class="text-muted small mb-3">
                Nyalakan <strong>Penerima 30%</strong> untuk memasukkan ruangan ke pembagian jasa.
                Hanya ruangan yang aktif dan menerima 30% yang dihitung, dan totalnya harus tepat 100%.
            </p>

            <form action="{{ route('master.ruangan.updateBulk') }}" method="POST">
                @csrf

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-secondary text-center">
                            <tr>
                                <th width="4%">No</th>
                                <th>Nama Ruangan</th>
                                <th width="10%">Penerima 30%</th>
                                <th width="12%">Persentase (%)</th>
                                <th width="8%">Risk</th>
                                <th width="8%">Emergency</th>
                                <th width="15%">Bidang</th>
                                <th width="12%">Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($ruangan as $index => $r)
                                @php
                                    $penerima = (bool) $r->penerima_jasa;
                                    $aktif = (bool) $r->is_active;
                                @endphp

                                <tr class="{{ !$aktif ? 'table-secondary' : ($penerima ? '' : 'text-muted') }}">

                                    <td class="text-center">{{ $index + 1 }}</td>

                                    {{-- NAMA --}}
                                    <td>
                                        <input type="hidden" name="ruangan_id[]" value="{{ $r->id }}">

                                        <input type="text" name="nama_ruangan[]"
                                            class="form-control form-control-sm text-uppercase"
                                            value="{{ $r->nama_ruangan }}" {{ !$aktif ? 'readonly' : '' }}>
                                    </td>

                                    {{-- TOGGLE PENERIMA 30% --}}
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block m-0">
                                            <input type="checkbox" class="form-check-input toggle-penerima" role="switch"
                                                style="cursor: pointer; transform: scale(1.3);"
                                                data-nama="{{ $r->nama_ruangan }}"
                                                data-persen="{{ (float) $r->persen_default }}"
                                                data-url-aktifkan="{{ route('master.ruangan.penerima.aktifkan', $r->id) }}"
                                                data-url-nonaktifkan="{{ route('master.ruangan.penerima.nonaktifkan', $r->id) }}"
                                                {{ $penerima ? 'checked' : '' }} {{ !$aktif ? 'disabled' : '' }}
                                                title="{{ !$aktif ? 'Aktifkan ruangan terlebih dahulu' : '' }}">
                                        </div>
                                    </td>

                                    {{-- PERSENTASE --}}
                                    <td>
                                        @if ($penerima)
                                            <input type="number" step="0.01" min="0" max="100"
                                                name="persen_default[]"
                                                class="form-control form-control-sm text-center {{ $aktif ? 'input-persen' : '' }}"
                                                value="{{ $r->persen_default }}" {{ !$aktif ? 'readonly' : '' }}>
                                        @else
                                            {{-- Tetap dikirim agar urutan array sejajar; server tidak mengubah nilainya --}}
                                            <input type="hidden" name="persen_default[]"
                                                value="{{ (float) $r->persen_default }}">
                                            <div class="text-center small">
                                                @if ((float) $r->persen_default > 0)
                                                    <span title="Alokasi terakhir, dipakai lagi jika diaktifkan">
                                                        ({{ number_format($r->persen_default, 2) }}%)
                                                    </span>
                                                @else
                                                    &ndash;
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    {{-- RISK --}}
                                    <td>
                                        <select name="resiko[]" class="form-select form-select-sm"
                                            {{ !$aktif ? 'disabled' : '' }}>
                                            <option value="1" {{ $r->resiko == 0 ? 'selected' : '' }}>0</option>
                                            <option value="1" {{ $r->resiko == 1 ? 'selected' : '' }}>1</option>
                                            <option value="2" {{ $r->resiko == 2 ? 'selected' : '' }}>2</option>
                                            <option value="4" {{ $r->resiko == 4 ? 'selected' : '' }}>4</option>
                                            <option value="6" {{ $r->resiko == 6 ? 'selected' : '' }}>6</option>
                                        </select>

                                        @if (!$aktif)
                                            <input type="hidden" name="resiko[]" value="{{ $r->resiko }}">
                                        @endif
                                    </td>

                                    {{-- EMERGENCY --}}
                                    <td>
                                        <select name="emergency[]" class="form-select form-select-sm"
                                            {{ !$aktif ? 'disabled' : '' }}>
                                            <option value="1" {{ $r->emergency == 0 ? 'selected' : '' }}>0</option>
                                            <option value="1" {{ $r->emergency == 1 ? 'selected' : '' }}>1</option>
                                            <option value="2" {{ $r->emergency == 2 ? 'selected' : '' }}>2</option>
                                            <option value="4" {{ $r->emergency == 4 ? 'selected' : '' }}>4</option>
                                            <option value="6" {{ $r->emergency == 6 ? 'selected' : '' }}>6</option>
                                        </select>

                                        @if (!$aktif)
                                            <input type="hidden" name="emergency[]" value="{{ $r->emergency }}">
                                        @endif
                                    </td>

                                    {{-- BIDANG --}}
                                    <td>
                                        <select name="bidang_id[]" class="form-select form-select-sm"
                                            {{ !$aktif ? 'disabled' : '' }}>
                                            @foreach ($bidang as $b)
                                                <option value="{{ $b->id }}"
                                                    {{ $r->bidang_id == $b->id ? 'selected' : '' }}>
                                                    {{ $b->nama_bidang }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @if (!$aktif)
                                            <input type="hidden" name="bidang_id[]" value="{{ $r->bidang_id }}">
                                        @endif
                                    </td>

                                    {{-- STATUS --}}
                                    <td class="text-center">
                                        <span class="badge bg-{{ $aktif ? 'success' : 'secondary' }}">
                                            {{ $aktif ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                        <br>
                                        <a href="{{ route('master.ruangan.toggleStatus', $r->id) }}"
                                            class="btn btn-outline-primary btn-sm mt-2"
                                            onclick="return confirm('Ubah status ruangan {{ $r->nama_ruangan }}? Perubahan yang belum disimpan akan hilang.')">
                                            {{ $aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </a>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="3" class="text-end">TOTAL PERSENTASE</td>
                                <td class="text-center">
                                    <span id="indikatorTotal" class="{{ $totalPas ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($totalPersen, 2) }}%
                                    </span>
                                </td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small id="pesanError" class="text-danger fw-bold"
                        style="display: {{ $totalPas ? 'none' : 'block' }}">
                        *Total persentase ruangan penerima jasa 30% yang aktif harus tepat 100%.
                    </small>

                    <button id="btnSimpanMassal" type="submit" class="btn btn-success ms-auto"
                        {{ $totalPas ? '' : 'disabled' }}>
                        <i class="align-middle" data-feather="save"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== MODAL: TAMBAH RUANGAN ===================== --}}
    <div class="modal fade" id="modalTambahRuangan" tabindex="-1" aria-labelledby="judulTambahRuangan"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('master.ruangan.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_form" value="tambah">

                    <div class="modal-header">
                        <h5 class="modal-title" id="judulTambahRuangan">Tambah Ruangan Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Ruangan</label>
                            <input type="text" name="nama_ruangan" class="form-control text-uppercase"
                                placeholder="Contoh: IGD" value="{{ old('nama_ruangan') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bidang</label>
                            <select name="bidang_id" class="form-select" required>
                                <option value="">-- Pilih Bidang --</option>
                                @foreach ($bidang as $b)
                                    <option value="{{ $b->id }}"
                                        {{ old('bidang_id') == $b->id ? 'selected' : '' }}>
                                        {{ $b->nama_bidang }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Risk Index</label>
                            <select name="resiko" class="form-select" required>
                                <option value="">-- Pilih Risk Index --</option>
                                <option value="1" {{ old('resiko') == 1 ? 'selected' : '' }}>1 - Administratif /
                                    Perkantoran</option>
                                <option value="2" {{ old('resiko') == 2 ? 'selected' : '' }}>2 - Rawat Jalan, Gizi,
                                    IPSRS, Rehab Medik, Diagnostik, CSSD, Ambulance, HD, Farmasi, UTDRS</option>
                                <option value="4" {{ old('resiko') == 4 ? 'selected' : '' }}>4 - Rawat Inap,
                                    Laboratorium PK/PA, VK</option>
                                <option value="6" {{ old('resiko') == 6 ? 'selected' : '' }}>6 - Isolasi, Bedah
                                    Sentral, IGD, ICU, HCU, ICCU, NICU, PICU, Poli Paru, Laundry, Forensik, Radiologi, IPAL,
                                    Kemoterapi</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Emergency Index</label>
                            <select name="emergency" class="form-select" required>
                                <option value="">-- Pilih Emergency Index --</option>
                                <option value="1" {{ old('emergency') == 1 ? 'selected' : '' }}>1 - Administrasi
                                    Perkantoran</option>
                                <option value="2" {{ old('emergency') == 2 ? 'selected' : '' }}>2 - Administrasi
                                    Keuangan, Gizi, Farmasi, Rawat Jalan, Rehab Medik, IPSRS, Gigi & Mulut, Forensik
                                </option>
                                <option value="4" {{ old('emergency') == 4 ? 'selected' : '' }}>4 - Rawat Inap,
                                    Laboratorium PK/PA, CSSD, IPAL, Hemodialisa, Kemoterapi, UTDRS</option>
                                <option value="6" {{ old('emergency') == 6 ? 'selected' : '' }}>6 - Bedah Central,
                                    VK,
                                    Rawat Inap Menular, ICU/ICCU/NICU/PICU, IGD, Laundry, Radiologi</option>
                            </select>
                        </div>

                        <div class="text-muted small">
                            Ruangan baru belum menerima jasa 30%. Nyalakan <strong>Penerima 30%</strong> di tabel untuk
                            memberi alokasi.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambah Ruangan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL: JADIKAN PENERIMA 30% ===================== --}}
    <button type="button" id="btnBukaModalPenerima" class="d-none" data-bs-toggle="modal"
        data-bs-target="#modalPenerima"></button>

    <div class="modal fade" id="modalPenerima" tabindex="-1" aria-labelledby="judulPenerima" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formPenerima" method="POST">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="judulPenerima">Jadikan Penerima Jasa 30%</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-3">Ruangan: <strong id="mpNama"></strong></p>

                        <table class="table table-sm table-borderless mb-3 bg-light rounded">
                            <tr>
                                <td class="ps-3">Total persentase penerima jasa saat ini</td>
                                <td class="text-end pe-3 fw-bold"><span id="mpTotal"></span>%</td>
                            </tr>
                            <tr>
                                <td class="ps-3">Sisa sampai 100%</td>
                                <td class="text-end pe-3 fw-bold"><span id="mpSisa"></span>%</td>
                            </tr>
                        </table>

                        <label for="mpPersen" class="form-label">Alokasi persentase untuk ruangan ini (%)</label>
                        <input type="number" step="0.01" min="0.01" max="100" name="persen_default"
                            id="mpPersen" class="form-control text-center" required>
                        <div id="mpLama" class="form-text"></div>

                        <div class="mt-3 p-2 rounded border">
                            Total setelah disimpan:
                            <strong id="mpTotalBaru"></strong>
                            <div id="mpCatatan" class="small mt-1"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="mpSimpan" class="btn btn-success">Simpan Alokasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Form tersembunyi untuk mengeluarkan penerima --}}
    <form id="formNonaktifPenerima" method="POST" class="d-none">
        @csrf
    </form>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const TOTAL_SAAT_INI = {{ round((float) $totalPersen, 2) }};

            // ===================== TOTAL REAL-TIME DI TABEL =====================
            const persenInputs = document.querySelectorAll('.input-persen');
            const indikatorTotal = document.getElementById('indikatorTotal');
            const badgeTotal = document.getElementById('badgeTotal');
            const btnSimpan = document.getElementById('btnSimpanMassal');
            const pesanError = document.getElementById('pesanError');

            function hitungTotal() {
                let total = 0;
                persenInputs.forEach(p => total += parseFloat(p.value) || 0);

                const totalFormatted = total.toFixed(2);
                const pas = totalFormatted === "100.00";

                indikatorTotal.innerText = totalFormatted + '%';
                indikatorTotal.className = pas ? 'text-success' : 'text-danger';

                badgeTotal.innerText = 'Total Persentase Penerima Jasa 30% : ' + totalFormatted + '%';
                badgeTotal.className = 'badge fs-6 bg-' + (pas ? 'success' : 'danger');

                btnSimpan.disabled = !pas;
                pesanError.style.display = pas ? 'none' : 'block';
            }

            persenInputs.forEach(input => input.addEventListener('input', hitungTotal));

            // ===================== MODAL PENERIMA =====================
            const formPenerima = document.getElementById('formPenerima');
            const mpNama = document.getElementById('mpNama');
            const mpTotal = document.getElementById('mpTotal');
            const mpSisa = document.getElementById('mpSisa');
            const mpPersen = document.getElementById('mpPersen');
            const mpLama = document.getElementById('mpLama');
            const mpTotalBaru = document.getElementById('mpTotalBaru');
            const mpCatatan = document.getElementById('mpCatatan');
            const mpSimpan = document.getElementById('mpSimpan');
            const SISA = +Math.max(0, 100 - TOTAL_SAAT_INI).toFixed(2);

            function updatePreview() {
                const nilai = parseFloat(mpPersen.value) || 0;
                const totalBaru = TOTAL_SAAT_INI + nilai;
                const selisih = +(totalBaru - 100).toFixed(2);

                mpTotalBaru.innerText = totalBaru.toFixed(2) + '%';

                // ✅ PENCEGAHAN: tidak boleh melebihi 100% dan harus > 0
                mpSimpan.disabled = selisih > 0 || nilai <= 0;

                if (selisih === 0) {
                    mpTotalBaru.className = 'text-success';
                    mpCatatan.className = 'small mt-1 text-success';
                    mpCatatan.innerText = 'Total pas 100%.';
                } else if (selisih > 0) {
                    mpTotalBaru.className = 'text-danger';
                    mpCatatan.className = 'small mt-1 text-danger';
                    mpCatatan.innerText = 'Melebihi 100% sebesar ' + selisih.toFixed(2) +
                        '%. Alokasi maksimal untuk ruangan ini ' + SISA.toFixed(2) + '%.';
                } else {
                    mpTotalBaru.className = 'text-warning';
                    mpCatatan.className = 'small mt-1 text-muted';
                    mpCatatan.innerText = 'Masih kurang ' + Math.abs(selisih).toFixed(2) + '% dari 100%.';
                }
            }

            mpPersen.addEventListener('input', updatePreview);

            // ===================== TOGGLE PENERIMA =====================
            const formNonaktif = document.getElementById('formNonaktifPenerima');

            document.querySelectorAll('.toggle-penerima').forEach(sw => {
                sw.addEventListener('change', function() {
                    const nama = this.dataset.nama;

                    if (this.checked) {
                        // Jangan langsung berubah; tunggu alokasi disimpan lewat modal
                        this.checked = false;

                        const persenLama = parseFloat(this.dataset.persen) || 0;
                        const sisa = SISA;

                        // ✅ PENCEGAHAN: total sudah 100%, tolak tanpa membuka modal
                        if (sisa <= 0) {
                            alert('Tidak bisa menambahkan ' + nama +
                                ' sebagai penerima jasa 30%.\n\n' +
                                'Total persentase penerima jasa sudah ' + TOTAL_SAAT_INI
                                .toFixed(2) + '%.\n' +
                                'Kurangi alokasi ruangan lain lalu simpan, atau keluarkan ruangan lain dari ' +
                                'penerima 30% terlebih dahulu.');
                            return;
                        }

                        mpPersen.max = sisa;
                        formPenerima.action = this.dataset.urlAktifkan;
                        mpNama.innerText = nama;
                        mpTotal.innerText = TOTAL_SAAT_INI.toFixed(2);
                        mpSisa.innerText = sisa.toFixed(2);

                        mpPersen.value = persenLama > 0 ? persenLama : '';
                        mpPersen.placeholder = sisa > 0 ? 'Contoh: ' + sisa.toFixed(2) : '';
                        if (persenLama > sisa) {
                            mpLama.innerText = 'Alokasi sebelumnya ' + persenLama.toFixed(2) +
                                '% melebihi sisa yang tersedia. Turunkan menjadi maksimal ' + sisa
                                .toFixed(2) + '%.';
                        } else if (persenLama > 0) {
                            mpLama.innerText = 'Alokasi sebelumnya: ' + persenLama.toFixed(2) +
                                '%. Boleh diubah.';
                        } else {
                            mpLama.innerText = 'Ruangan ini belum pernah punya alokasi. Maksimal ' +
                                sisa.toFixed(2) + '%.';
                        }

                        updatePreview();
                        document.getElementById('btnBukaModalPenerima').click();
                        setTimeout(() => mpPersen.focus(), 400);
                    } else {
                        if (confirm('Keluarkan ' + nama + ' dari penerima jasa 30%?\n' +
                                'Alokasi persentasenya tetap tersimpan dan akan ditampilkan lagi jika diaktifkan kembali.\n' +
                                'Perubahan yang belum disimpan di tabel akan hilang.')) {
                            formNonaktif.action = this.dataset.urlNonaktifkan;
                            formNonaktif.submit();
                        } else {
                            this.checked = true;
                        }
                    }
                });
            });

            // Buka ulang modal tambah jika validasi tambah ruangan gagal
            @if ($errors->any() && old('_form') === 'tambah')
                document.getElementById('btnTambahRuangan').click();
            @endif
        });

        // Konfirmasi Hapus Ruangan (fungsi lama, tidak diubah)
        function hapusRuangan(id, nama) {
            if (confirm(
                    `Peringatan: Menghapus ruangan "${nama}" mungkin mengubah total persentase. Yakin ingin menghapus?`)) {
                let formDelete = document.getElementById('formDelete');
                formDelete.action = `/master-ruangan/${id}`;
                formDelete.submit();
            }
        }

        // (fungsi lama, tidak diubah)
        function toggleStatus(id, nama) {
            if (!confirm('Ubah status ruangan "' + nama + '" ?')) {
                return;
            }

            const form = document.getElementById('toggleStatusForm');
            form.action = "{{ url('master-ruangan') }}/" + id + "/toggle-status";
            form.submit();
        }
    </script>
@endsection
