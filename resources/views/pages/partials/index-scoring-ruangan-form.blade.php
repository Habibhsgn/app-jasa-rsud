<div class="card mb-4">
    <div
        class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
        <div>
            <span class="text-muted small">Ruangan:</span>
            <strong>{{ $item->ruangan->nama_ruangan ?? '-' }}</strong>
        </div>

        {{-- Badge status sekarang per-ruangan --}}
        <div>
            @if ($item->status_pengajuan == 'draft')
                <span class="badge bg-warning">Draft</span>
            @elseif($item->status_pengajuan == 'submit')
                <span class="badge bg-success">Sudah Submit</span>
            @elseif($item->status_pengajuan == 'verifikasi')
                <span class="badge bg-info text-dark">Menunggu Verifikasi</span>
            @elseif($item->status_pengajuan == 'revisi')
                <span class="badge bg-danger">Perlu Revisi — Silakan lengkapi & submit ulang</span>
            @elseif($item->status_pengajuan == 'selesai')
                <span class="badge bg-primary">Selesai</span>
            @endif
        </div>
    </div>

    <div class="card-body">
        <form action="{{ route('index.scoring.store') }}" method="POST">
            @csrf
            <input type="hidden" name="jasa_ruangan_id" value="{{ $item->id }}">
            <input type="hidden" name="periode" value="{{ $periodeItem->periode }}">

            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="align-middle text-center" style="min-width:70px;">
                                NO</th>
                            <th rowspan="2" class="align-middle text-center"
                                style="min-width:250px;">NAMA</th>

                            <th colspan="7" class="align-middle text-center">INDEKS SKOR</th>
                            <th colspan="4" class="align-middle text-center">BOBOT PENGURANG</th>

                            <th rowspan="2" class="align-middle text-center"
                                style="min-width:220px;">
                                SIKAP<br>
                                <small class="fw-normal">
                                    (Kriminal, Narkoba,<br>Asusila, Merokok, dll)
                                </small>
                            </th>

                            <th rowspan="2" class="align-middle text-center"
                                style="min-width:170px;">
                                JUMLAH<br>
                                <small class="fw-normal">
                                    (Total Skor Pengurangan)
                                </small>
                            </th>

                            <th rowspan="2" class="align-middle text-center"
                                style="min-width:180px;">
                                Keterangan
                            </th>

                            <th rowspan="2" class="align-middle text-center"
                                style="min-width:170px;">
                                JUMLAH AKHIR
                            </th>
                        </tr>

                        <tr>
                            <th class="align-middle text-center" style="min-width:120px;">NIP/NIP3K</th>
                            <th class="align-middle text-center" style="min-width:160px;">Jabatan</th>
                            <th class="align-middle text-center" style="min-width:120px;">
                                Pend.<br>Formal</th>
                            <th class="align-middle text-center" style="min-width:140px;">Pend.
                                Non<br>Formal</th>
                            <th class="align-middle text-center" style="min-width:120px;">Gaji<br>Pokok
                            </th>
                            <th class="align-middle text-center" style="min-width:110px;">Risk</th>
                            <th class="align-middle text-center" style="min-width:120px;">Emergency</th>

                            <th class="align-middle text-center" style="min-width:100px;">Cuti</th>
                            <th class="align-middle text-center" style="min-width:100px;">Izin</th>
                            <th class="align-middle text-center" style="min-width:120px;">Tanpa<br>Izin
                            </th>
                            <th class="align-middle text-center" style="min-width:100px;">Telat</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($item->pegawai as $i => $p)
                            @php
                                $rowDisabled = $item->disable_input || empty($p->id_petugas);
                            @endphp

                            <tr>
                                <td>{{ $i + 1 }}</td>

                                <input type="hidden" name="pegawai[{{ $p->source_id }}][ruangan_id]"
                                    value="{{ $item->id }}">
                                <input type="hidden" name="pegawai[{{ $p->source_id }}][pegawai_id]"
                                    value="{{ $p->source_id }}">
                                <input type="hidden" name="pegawai[{{ $p->source_id }}][source_type]"
                                    value="{{ $p->source_type }}">
                                <input type="hidden" name="pegawai[{{ $p->source_id }}][source_id]"
                                    value="{{ $p->source_id }}">

                                <td class="text-start">
                                    <strong>{{ $p->nama }}</strong>
                                </td>

                                <td>
                                    @if ($p->id_petugas)
                                        <span class="badge bg-success">{{ $p->id_petugas }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Lengkapi ID</span>
                                    @endif
                                </td>

                                <td>
                                    <select name="pegawai[{{ $p->source_id }}][jabatan]"
                                        class="form-select form-select-sm jabatan"
                                        {{ $rowDisabled ? 'disabled' : '' }}>

                                        <option value="0"
                                            {{ $p->jabatan == 0 ? 'selected' : '' }}>-- Pilih Jabatan
                                            --</option>
                                        <option value="1"
                                            {{ $p->jabatan == 1 ? 'selected' : '' }}>(1) Tidak memiliki
                                            jabatan</option>
                                        <option value="2"
                                            {{ $p->jabatan == 2 ? 'selected' : '' }}>(2) Koordinator,
                                            Admin, Penanggung Jawab, Bendahara</option>
                                        <option value="3"
                                            {{ $p->jabatan == 3 ? 'selected' : '' }}>(3) Kepala
                                            Seksi/Sub Bidang, Kepala Ruangan</option>
                                        <option value="4"
                                            {{ $p->jabatan == 4 ? 'selected' : '' }}>(4) Kepala Bidang,
                                            Kepala Bagian, Kepala Instalasi, IPCLN</option>
                                        <option value="6"
                                            {{ $p->jabatan == 6 ? 'selected' : '' }}>(6) Ketua Komite
                                            Medik, Ketua Komite Keperawatan, Ketua SPI, Wadir</option>
                                        <option value="8"
                                            {{ $p->jabatan == 8 ? 'selected' : '' }}>(8) Direktur
                                        </option>
                                    </select>
                                </td>

                                <td>
                                    <select name="pegawai[{{ $p->source_id }}][pendidikan_formal]"
                                        class="form-select form-select-sm text-center pendidikan-formal"
                                        {{ $rowDisabled ? 'disabled' : '' }}>
                                        <option value="0"
                                            {{ $p->pendidikan_formal == 0 ? 'selected' : '' }}>-- Pilih
                                            Pendidikan --</option>
                                        <option value="1"
                                            {{ $p->pendidikan_formal == 1 ? 'selected' : '' }}>SD
                                        </option>
                                        <option value="2"
                                            {{ $p->pendidikan_formal == 2 ? 'selected' : '' }}>SMP
                                        </option>
                                        <option value="3"
                                            {{ $p->pendidikan_formal == 3 ? 'selected' : '' }}>SMA/SMU
                                        </option>
                                        <option value="4"
                                            {{ $p->pendidikan_formal == 4 ? 'selected' : '' }}>D1
                                        </option>
                                        <option value="5"
                                            {{ $p->pendidikan_formal == 5 ? 'selected' : '' }}>D3
                                        </option>
                                        <option value="6"
                                            {{ $p->pendidikan_formal == 6 ? 'selected' : '' }}>S1/D4
                                        </option>
                                        <option value="7"
                                            {{ $p->pendidikan_formal == 7 ? 'selected' : '' }}>Dokter
                                            Umum / Dokter Gigi / Apoteker / Ners</option>
                                        <option value="8"
                                            {{ $p->pendidikan_formal == 8 ? 'selected' : '' }}>S2
                                        </option>
                                        <option value="9"
                                            {{ $p->pendidikan_formal == 9 ? 'selected' : '' }}>Dokter
                                            Spesialis</option>
                                        <option value="10"
                                            {{ $p->pendidikan_formal == 10 ? 'selected' : '' }}>S3 /
                                            Subspesialis / Konsultan</option>
                                    </select>
                                </td>

                                <td>
                                    <input type="text"
                                        name="pegawai[{{ $p->source_id }}][pendidikan_non_formal]"
                                        class="form-control form-control-sm text-center pendidikan-non-formal"
                                        value="{{ $p->pendidikan_non_formal }}" readonly>
                                </td>

                                <td>
                                    <input type="text"
                                        name="pegawai[{{ $p->source_id }}][gaji_pokok]"
                                        class="form-control form-control-sm text-center gaji-pokok"
                                        value="{{ $p->gaji_pokok_display }}" readonly>
                                </td>

                                <td>
                                    <input type="text" name="pegawai[{{ $p->source_id }}][risk]"
                                        class="form-control form-control-sm text-center bg-light risk"
                                        value="{{ $p->risk }}" readonly>
                                </td>

                                <td>
                                    <input type="text"
                                        name="pegawai[{{ $p->source_id }}][emergency]"
                                        class="form-control form-control-sm text-center bg-light emergency"
                                        value="{{ $p->emergency }}" readonly>
                                </td>

                                <td>
                                    <input type="text" name="pegawai[{{ $p->source_id }}][cuti]"
                                        class="form-control form-control-sm text-center cuti"
                                        value="{{ $p->cuti }}"
                                        {{ $rowDisabled ? 'disabled' : '' }}>
                                </td>

                                <td>
                                    <input type="text" name="pegawai[{{ $p->source_id }}][izin]"
                                        class="form-control form-control-sm text-center izin"
                                        value="{{ $p->izin }}"
                                        {{ $rowDisabled ? 'disabled' : '' }}>
                                </td>

                                <td>
                                    <input type="text"
                                        name="pegawai[{{ $p->source_id }}][tanpa_izin]"
                                        class="form-control form-control-sm text-center tanpa-izin"
                                        value="{{ $p->tanpa_izin }}"
                                        {{ $rowDisabled ? 'disabled' : '' }}>
                                </td>

                                <td>
                                    <input type="text" name="pegawai[{{ $p->source_id }}][telat]"
                                        class="form-control form-control-sm text-center telat"
                                        value="{{ $p->telat }}"
                                        {{ $rowDisabled ? 'disabled' : '' }}>
                                </td>

                                <td>
                                    <select name="pegawai[{{ $p->source_id }}][sikap]"
                                        class="form-select form-select-sm text-center sikap"
                                        {{ $rowDisabled ? 'disabled' : '' }}>
                                        <option value="0" {{ $p->sikap == 0 ? 'selected' : '' }}>
                                            -</option>
                                        <option value="1" {{ $p->sikap == 1 ? 'selected' : '' }}>
                                            Terbukti mencuri di RS (-100%)</option>
                                        <option value="2" {{ $p->sikap == 2 ? 'selected' : '' }}>
                                            Terbukti narkoba/miras/judi/mesum di RS (-100%)</option>
                                        <option value="3" {{ $p->sikap == 3 ? 'selected' : '' }}>
                                            Merokok di lingkungan RS (-25%)</option>
                                        <option value="4" {{ $p->sikap == 4 ? 'selected' : '' }}>
                                            Berkelahi di lingkungan RS (-50%)</option>
                                        <option value="5" {{ $p->sikap == 5 ? 'selected' : '' }}>
                                            Mogok kerja/menghasut (-100%)</option>
                                    </select>
                                </td>

                                <td class="fw-bold">
                                    <input type="text" name="pegawai[{{ $p->source_id }}][jumlah]"
                                        class="form-control form-control-sm text-center jumlah-pengurang jumlah"
                                        value="{{ $p->jumlah }}" readonly>
                                </td>

                                <td>
                                    <input type="text"
                                        name="pegawai[{{ $p->source_id }}][keterangan]"
                                        class="form-control form-control-sm"
                                        value="{{ $p->keterangan }}"
                                        {{ $rowDisabled ? 'readonly' : '' }}>
                                </td>

                                <td class="fw-bold text-primary">
                                    <input type="text"
                                        name="pegawai[{{ $p->source_id }}][jumlah_akhir]"
                                        class="form-control form-control-sm text-center jumlah-akhir"
                                        value="{{ $p->jumlah_akhir }}" readonly>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Catatan Revisi: sekarang hanya tampil di ruangan yang statusnya 'revisi' --}}
            @if ($item->status_pengajuan == 'revisi' && $item->catatan_revisi)
                <div class="mt-3 p-3 bg-light border rounded shadow-sm">
                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-0">
                        <i data-feather="alert-triangle" class="flex-shrink-0 mt-1"></i>
                        <div>
                            <strong>Catatan Revisi dari Manajemen:</strong>
                            <p class="mb-0 text-danger">{{ $item->catatan_revisi }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary px-4 btn-draft"
                    {{ $item->disable_input ? 'disabled' : '' }}>
                    <i data-feather="save" class="me-1 small"></i>
                    Simpan Draft
                </button>

                <button type="submit" class="btn btn-success px-4 btn-submit"
                    formaction="{{ route('index.scoring.submit') }}"
                    {{ $item->disable_input ? 'disabled' : '' }}>
                    <i data-feather="check-circle" class="me-1"></i>
                    Kunci & Submit Pembagian
                </button>
            </div>

        </form>
    </div>
</div>