@extends('layouts.app')

@section('title', 'Pengaturan Jasa')

@push('styles')
    <style>
        /* Setting Page Styles */

        .setting-value-panel1,
        .setting-value-panel2 {
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .setting-value-panel1.is-invalid,
        .setting-value-panel2.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
        }

        .table-borderless> :not(caption)>*>* {
            border-bottom-width: 1px;
            border-color: #e9ecef;
        }

        .input-group-sm .form-control {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            opacity: 1;
        }

        code.text-muted {
            font-size: 0.75rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
        }

        .bg-light-primary {
            background-color: rgba(67, 94, 199, 0.1);
        }

        .bg-light-warning {
            background-color: rgba(255, 181, 42, 0.1);
        }

        .bg-light-success {
            background-color: rgba(40, 199, 111, 0.1);
        }

        .text-primary {
            color: #435ec7 !important;
        }

        .text-warning {
            color: #ffb52a !important;
        }

        .text-success {
            color: #28c76f !important;
        }

        .badge-status {
            padding: 0.5em 0.75em;
            font-size: 0.75rem;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .page-header-title h5 {
            font-weight: 600;
            color: #212529;
        }

        .breadcrumb-item a {
            color: #6c757d;
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: #212529;
        }
    </style>
@endpush

@section('content')
    {{-- Flash messages --}}
    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $byKey = $settings->keyBy('key');
        $tlKeys = $settings->filter(fn($s) => str_starts_with($s->key, 'top_leader.'))->sortBy('id');
        $stKeys = $settings->filter(fn($s) => str_starts_with($s->key, 'staff.'))->sortBy('id');
    @endphp

    <form action="{{ route('setting.update') }}" method="POST">
        @csrf

        {{-- ========== CARD 1: PARAMETER PEMBAGIAN TARIF ========== --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0">Parameter Pembagian Tarif</h5>
                @if ($settings->isNotEmpty())
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="save" class="me-1"></i> Simpan Pengaturan
                    </button>
                @endif
            </div>
            <div class="card-body p-0">
                @if ($settings->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i data-feather="settings-off" class="d-block mb-2" style="width: 2rem; height: 2rem;"></i>
                        Belum ada pengaturan.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 160px">Key</th>
                                    <th style="min-width: 120px">Nilai</th>
                                    <th style="min-width: 180px">Label</th>
                                    <th style="min-width: 200px">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $keys_panel1 = ['persen_biaya_operasional', 'persen_jasa']; @endphp
                                @foreach ($keys_panel1 as $key)
                                    @php $s = $byKey[$key] ?? null; @endphp
                                    @if ($s)
                                        <tr>
                                            <td class="align-middle">
                                                <code class="text-muted small">{{ $s->key }}</code>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm" style="max-width: 150px">
                                                    <input type="number" step="0.01" min="0" max="100"
                                                        class="form-control text-end setting-value-panel1
                                                        @error('value.' . $s->id) is-invalid @enderror"
                                                        name="value[{{ $s->id }}]"
                                                        value="{{ old('value.' . $s->id, $s->value) }}"
                                                        oninput="updateTotalPanel1()">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text"
                                                    class="form-control form-control-sm
                                                    @error('label.' . $s->id) is-invalid @enderror"
                                                    name="label[{{ $s->id }}]"
                                                    value="{{ old('label.' . $s->id, $s->label) }}" placeholder="Label">
                                            </td>
                                            <td>
                                                <input type="text"
                                                    class="form-control form-control-sm
                                                    @error('description.' . $s->id) is-invalid @enderror"
                                                    name="description[{{ $s->id }}]"
                                                    value="{{ old('description.' . $s->id, $s->description) }}"
                                                    placeholder="Keterangan">
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr class="table-light">
                                    <td class="fw-bold">Total</td>
                                    <td>
                                        <span id="totalPanel1" class="fw-bold h5 mb-0"></span>
                                    </td>
                                    <td></td>
                                    <td>
                                        <span id="totalPanel1Warning" class="text-danger fw-bold"
                                            style="font-size: 0.875rem; display: none;">
                                            <i data-feather="alert-triangle" class="me-1"></i> Total harus 100%
                                        </span>
                                        <span id="totalPanel1Ok" class="text-success fw-bold"
                                            style="font-size: 0.875rem; display: none;">
                                            <i data-feather="circle-check" class="me-1"></i> Total = 100%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            @if ($settings->isNotEmpty())
                <div class="card-footer d-lg-none">
                    <button type="submit" class="btn btn-primary w-100">
                        <i data-feather="save" class="me-1"></i> Simpan Pengaturan
                    </button>
                </div>
            @endif
        </div>

        {{-- ========== CARD 2: PEMBAGIAN JASA ========== --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0">Pembagian Jasa</h5>
                @if ($settings->isNotEmpty())
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="save" class="me-1"></i> Simpan Pengaturan
                    </button>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 160px">Key</th>
                                <th style="min-width: 120px">Nilai</th>
                                <th style="min-width: 180px">Label</th>
                                <th style="min-width: 200px">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $keys_panel2 = ['persen_jasa_top_leader', 'persen_jasa_staff']; @endphp
                            @foreach ($keys_panel2 as $key)
                                @php $s = $byKey[$key] ?? null; @endphp
                                @if ($s)
                                    <tr>
                                        <td class="align-middle">
                                            <code class="text-muted small">{{ $s->key }}</code>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm" style="max-width: 150px">
                                                <input type="number" step="0.01" min="0" max="100"
                                                    class="form-control text-end setting-value-panel2
                                                    @error('value.' . $s->id) is-invalid @enderror"
                                                    name="value[{{ $s->id }}]"
                                                    value="{{ old('value.' . $s->id, $s->value) }}"
                                                    oninput="updateTotalPanel2()">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text"
                                                class="form-control form-control-sm
                                                @error('label.' . $s->id) is-invalid @enderror"
                                                name="label[{{ $s->id }}]"
                                                value="{{ old('label.' . $s->id, $s->label) }}" placeholder="Label">
                                        </td>
                                        <td>
                                            <input type="text"
                                                class="form-control form-control-sm
                                                @error('description.' . $s->id) is-invalid @enderror"
                                                name="description[{{ $s->id }}]"
                                                value="{{ old('description.' . $s->id, $s->description) }}"
                                                placeholder="Keterangan">
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                            <tr class="table-light">
                                <td class="fw-bold">Total</td>
                                <td>
                                    <span id="totalPanel2" class="fw-bold h5 mb-0"></span>
                                </td>
                                <td></td>
                                <td>
                                    <span id="totalPanel2Warning" class="text-danger" style="display:none;">
                                        <i data-feather="alert-triangle"></i> Total harus 100%!
                                    </span>
                                    <span id="totalPanel2Ok" class="text-success" style="display:none;">
                                        <i data-feather="circle-check"></i> Total = 100%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($settings->isNotEmpty())
                <div class="card-footer d-lg-none">
                    <button type="submit" class="btn btn-primary w-100">
                        <i data-feather="save" class="me-1"></i> Simpan Pengaturan
                    </button>
                </div>
            @endif
        </div>

        {{-- ========== CARD 3: DETAIL PEMBAGIAN TOP LEADER ========== --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0">Detail Pembagian Top Leader</h5>
                @if ($settings->isNotEmpty())
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="save" class="me-1"></i> Simpan Pengaturan
                    </button>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 160px">Key</th>
                                <th style="min-width: 120px">Nilai</th>
                                <th style="min-width: 180px">Label</th>
                                <th style="min-width: 200px">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tlKeys as $s)
                                <tr>
                                    <td class="align-middle">
                                        <code class="text-muted small">{{ $s->key }}</code>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm" style="max-width: 150px">
                                            <input type="number" step="0.01" min="0" max="100"
                                                class="form-control text-end tl-value
                                                @error('value.' . $s->id) is-invalid @enderror"
                                                name="value[{{ $s->id }}]"
                                                value="{{ old('value.' . $s->id, $s->value) }}"
                                                oninput="updateTLTotal()">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text"
                                            class="form-control form-control-sm
                                            @error('label.' . $s->id) is-invalid @enderror"
                                            name="label[{{ $s->id }}]"
                                            value="{{ old('label.' . $s->id, $s->label) }}" placeholder="Jabatan">
                                    </td>
                                    <td>
                                        <input type="text"
                                            class="form-control form-control-sm
                                            @error('description.' . $s->id) is-invalid @enderror"
                                            name="description[{{ $s->id }}]"
                                            value="{{ old('description.' . $s->id, $s->description) }}"
                                            placeholder="Keterangan">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        <i data-feather="info-circle" class="me-1"></i> Belum ada data Top Leader.
                                    </td>
                                </tr>
                            @endforelse
                            <tr class="table-light">
                                <td class="fw-bold">Total</td>
                                <td>
                                    <span id="tlTotal" class="fw-bold h5 mb-0"></span>
                                </td>
                                <td></td>
                                <td>
                                    <span id="tlTotalWarning" class="text-danger" style="display:none;">
                                        <i data-feather="alert-triangle"></i> Total harus 100%!
                                    </span>
                                    <span id="tlTotalOk" class="text-success" style="display:none;">
                                        <i data-feather="circle-check"></i> Total = 100%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($settings->isNotEmpty())
                <div class="card-footer d-lg-none">
                    <button type="submit" class="btn btn-primary w-100">
                        <i data-feather="save" class="me-1"></i> Simpan Pengaturan
                    </button>
                </div>
            @endif
        </div>

        {{-- ========== CARD 4: DETAIL PEMBAGIAN STAFF ========== --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0">Detail Pembagian Staff</h5>
                @if ($settings->isNotEmpty())
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="save" class="me-1"></i> Simpan Pengaturan
                    </button>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 160px">Key</th>
                                <th style="min-width: 120px">Nilai</th>
                                <th style="min-width: 180px">Label</th>
                                <th style="min-width: 200px">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stKeys as $s)
                                <tr>
                                    <td class="align-middle">
                                        <code class="text-muted small">{{ $s->key }}</code>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm" style="max-width: 150px">
                                            <input type="number" step="0.01" min="0" max="100"
                                                class="form-control text-end staff-value
                                                @error('value.' . $s->id) is-invalid @enderror"
                                                name="value[{{ $s->id }}]"
                                                value="{{ old('value.' . $s->id, $s->value) }}"
                                                oninput="updateStaffTotal()">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text"
                                            class="form-control form-control-sm
                                            @error('label.' . $s->id) is-invalid @enderror"
                                            name="label[{{ $s->id }}]"
                                            value="{{ old('label.' . $s->id, $s->label) }}" placeholder="Label">
                                    </td>
                                    <td>
                                        <input type="text"
                                            class="form-control form-control-sm
                                            @error('description.' . $s->id) is-invalid @enderror"
                                            name="description[{{ $s->id }}]"
                                            value="{{ old('description.' . $s->id, $s->description) }}"
                                            placeholder="Keterangan">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        <i data-feather="info-circle" class="me-1"></i> Belum ada data Staff.
                                    </td>
                                </tr>
                            @endforelse
                            <tr class="table-light">
                                <td class="fw-bold">Total</td>
                                <td>
                                    <span id="staffTotal" class="fw-bold h5 mb-0"></span>
                                </td>
                                <td></td>
                                <td>
                                    <span id="staffTotalWarning" class="text-danger" style="display:none;">
                                        <i data-feather="alert-triangle"></i> Total harus 100%!
                                    </span>
                                    <span id="staffTotalOk" class="text-success" style="display:none;">
                                        <i data-feather="circle-check"></i> Total = 100%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($settings->isNotEmpty())
                <div class="card-footer d-lg-none">
                    <button type="submit" class="btn btn-primary w-100">
                        <i data-feather="save" class="me-1"></i> Simpan Pengaturan
                    </button>
                </div>
            @endif
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        function renderTotal(totalId, warningId, okId, total, target) {
            const totalEl = document.getElementById(totalId);
            const warningEl = document.getElementById(warningId);
            const okEl = document.getElementById(okId);
            if (!totalEl) return;

            // Round to 2 decimal places to avoid floating point errors
            total = Math.round(total * 100) / 100;
            totalEl.textContent = total.toFixed(2) + '%';

            const isExact = Math.abs(total - target) < 0.01;

            if (isExact) {
                totalEl.className = 'fw-bold h5 mb-0 text-success';
            } else {
                totalEl.className = 'fw-bold h5 mb-0 text-danger';
            }

            if (warningEl) {
                warningEl.style.display = isExact ? 'none' : 'inline-block';
                warningEl.style.fontWeight = '600';
                warningEl.style.fontSize = '0.875rem';
            }
            if (okEl) {
                okEl.style.display = isExact ? 'inline-block' : 'none';
                okEl.style.fontWeight = '600';
                okEl.style.fontSize = '0.875rem';
            }
        }

        function updateTotalPanel1() {
            const inputs = document.querySelectorAll('.setting-value-panel1');
            let total = 0;
            inputs.forEach(inp => {
                total += parseFloat(inp.value) || 0;
            });
            renderTotal('totalPanel1', 'totalPanel1Warning', 'totalPanel1Ok', total, 100);
        }

        function updateTotalPanel2() {
            const inputs = document.querySelectorAll('.setting-value-panel2');
            let total = 0;
            inputs.forEach(inp => {
                total += parseFloat(inp.value) || 0;
            });
            renderTotal('totalPanel2', 'totalPanel2Warning', 'totalPanel2Ok', total, 100);
        }

        function updateTLTotal() {
            const inputs = document.querySelectorAll('.tl-value');
            let total = 0;
            inputs.forEach(inp => {
                total += parseFloat(inp.value) || 0;
            });
            renderTotal('tlTotal', 'tlTotalWarning', 'tlTotalOk', total, 100);
        }

        function updateStaffTotal() {
            const inputs = document.querySelectorAll('.staff-value');
            let total = 0;
            inputs.forEach(inp => {
                total += parseFloat(inp.value) || 0;
            });
            renderTotal('staffTotal', 'staffTotalWarning', 'staffTotalOk', total, 100);
        }

        // Auto-run on load
        document.addEventListener('DOMContentLoaded', function() {
            updateTotalPanel1();
            updateTotalPanel2();
            updateTLTotal();
            updateStaffTotal();
        });
    </script>
@endpush
