@extends('layouts.app')
@section('title', 'Kelola Module')
@section('content')
    <h1 class="h3 mb-3">
        <strong>Kelola</strong> Module
    </h1>
    <div class="container-fluid p-0">

        @include('admin.rbac.partials.nav')

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Tambah Module</div>
                    <div class="card-body">
                        <form action="{{ route('rbac.modules.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Code</label>
                                <input type="text" name="code" class="form-control" placeholder="mis. jasa" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama</label>
                                <input type="text" name="name" class="form-control" placeholder="mis. Jasa & Index Scoring" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Icon (feather icon, opsional)</label>
                                <input type="text" name="icon" class="form-control" placeholder="mis. dollar-sign">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Urutan</label>
                                <input type="number" name="order" class="form-control" value="0">
                            </div>
                            <button class="btn btn-primary">Simpan</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Nama</th>
                                    <th>Permission</th>
                                    <th>Menu</th>
                                    <th>Status</th>
                                    <th style="width:160px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($modules as $module)
                                    <tr>
                                        <td><code>{{ $module->code }}</code></td>
                                        <td>{{ $module->name }}</td>
                                        <td>{{ $module->permissions_count }}</td>
                                        <td>{{ $module->menus_count }}</td>
                                        <td>
                                            @if ($module->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModule{{ $module->id }}">Edit</button>
                                            <form action="{{ route('rbac.modules.destroy', $module) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus module {{ $module->name }}?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editModule{{ $module->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('rbac.modules.update', $module) }}" method="POST">
                                                    @csrf @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Module: {{ $module->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Code</label>
                                                            <input type="text" name="code" class="form-control" value="{{ $module->code }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Nama</label>
                                                            <input type="text" name="name" class="form-control" value="{{ $module->name }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Icon</label>
                                                            <input type="text" name="icon" class="form-control" value="{{ $module->icon }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Urutan</label>
                                                            <input type="number" name="order" class="form-control" value="{{ $module->order }}">
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $module->id }}" {{ $module->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="active{{ $module->id }}">Aktif</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button class="btn btn-primary">Simpan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Belum ada module.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
