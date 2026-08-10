@extends('layouts.app')
@section('title', 'Manajemen Top Leader')
@section('content')
    <div class="mb-3">
        <h1 class="h3 mb-0"><strong>Manajemen</strong> Top Leader</h1>
        <p class="text-muted mb-0 small">Kelola data jajaran top leader beserta posisinya</p>
    </div>

    <div class="container-fluid p-0">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="bi bi-person-plus me-1"></i> Tambah Top Leader
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('top-leader.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Nama</label>
                                <input type="text" name="nama" class="form-control" placeholder="Nama lengkap"
                                    value="{{ old('nama') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Posisi</label>
                                <select name="posisi" class="form-select" required>
                                    <option value="">Pilih posisi</option>
                                    @foreach ($posisiOptions as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('posisi') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gaji Pokok</label>
                                <input type="number" name="gaji_pokok" class="form-control" placeholder="Gaji pokok (rupiah)"
                                    value="{{ old('gaji_pokok', 0) }}" min="0" step="100000">
                            </div>
                            <div class="mb-3 form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    id="is_active" checked>
                                <label class="form-check-label" for="is_active">Aktif</label>
                            </div>
                            <button class="btn btn-primary w-100">
                                <i class="bi bi-save me-1"></i> Simpan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Daftar Top Leader</h6>
                        <span class="badge text-bg-secondary">{{ $topLeaders->total() }} Data</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama</th>
                                        <th>Posisi</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end" style="width:160px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($topLeaders as $topLeader)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-semibold"
                                                        style="width:32px;height:32px;font-size:.8rem;flex-shrink:0;">
                                                        {{ strtoupper(substr($topLeader->nama, 0, 1)) }}
                                                    </div>
                                                    <span>{{ $topLeader->nama }}</span>
                                                </div>
                                            </td>
                                            <td><span
                                                    class="badge text-bg-light text-dark border">{{ $topLeader->posisi }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if ($topLeader->is_active)
                                                    <span class="badge rounded-pill text-bg-success">Aktif</span>
                                                @else
                                                    <span class="badge rounded-pill text-bg-secondary">Nonaktif</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editTopLeader{{ $topLeader->id }}">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <form action="{{ route('top-leader.destroy', $topLeader) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Hapus Top Leader {{ $topLeader->nama }}?')">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-outline-danger">
                                                            <i class="bi bi-trash"></i> Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="editTopLeader{{ $topLeader->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('top-leader.update', $topLeader) }}"
                                                        method="POST">
                                                        @csrf @method('PUT')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Top Leader: {{ $topLeader->nama }}
                                                            </h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Nama</label>
                                                                <input type="text" name="nama" class="form-control"
                                                                    value="{{ $topLeader->nama }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Posisi</label>
                                                                <select name="posisi" class="form-select" required>
                                                                    @foreach ($posisiOptions as $value => $label)
                                                                        <option value="{{ $value }}"
                                                                            {{ $topLeader->posisi === $value ? 'selected' : '' }}>
                                                                            {{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Gaji Pokok</label>
                                                                <input type="number" name="gaji_pokok" class="form-control" placeholder="Gaji pokok (rupiah)"
                                                                    value="{{ $topLeader->gaji_pokok ?? 0 }}" min="0" step="100000">
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="is_active" value="0">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="is_active" value="1"
                                                                    id="active{{ $topLeader->id }}"
                                                                    {{ $topLeader->is_active ? 'checked' : '' }}>
                                                                <label class="form-check-label"
                                                                    for="active{{ $topLeader->id }}">Aktif</label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-secondary"
                                                                data-bs-dismiss="modal">Batal</button>
                                                            <button class="btn btn-primary">Simpan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-5">
                                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                Belum ada data Top Leader.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($topLeaders->hasPages())
                            <div class="card-footer bg-white">
                                {{ $topLeaders->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
