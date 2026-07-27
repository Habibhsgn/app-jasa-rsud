@extends('layouts.app')
@section('title', 'Kelola Permission')
@section('content')
    <h1 class="h3 mb-3">
        <strong>Kelola</strong> Permission
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
                    <div class="card-header">Tambah Permission</div>
                    <div class="card-body">
                        <form action="{{ route('rbac.permissions.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Code</label>
                                <input type="text" name="code" class="form-control" placeholder="mis. jasa.storeTotal">
                                <small class="text-muted">Idealnya sama dengan nama route yang mau diproteksi.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama</label>
                                <input type="text" name="name" class="form-control" placeholder="mis. Simpan Total Jasa" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Module</label>
                                <select name="module_id" class="form-select">
                                    <option value="">— Umum (tanpa module) —</option>
                                    @foreach ($modules as $module)
                                        <option value="{{ $module->id }}">{{ $module->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Atau buat module baru</label>
                                <input type="text" name="new_module_name" class="form-control" placeholder="kosongkan kalau pilih module di atas">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deskripsi (opsional)</label>
                                <input type="text" name="description" class="form-control">
                            </div>
                            <button class="btn btn-primary">Simpan</button>
                        </form>
                    </div>
                </div>
                <div class="alert alert-warning mt-3">
                    Membuat permission di sini <strong>belum otomatis memproteksi route apa pun</strong>.
                    Pasang <code>middleware(['permission:kode-nya'])</code> di <code>routes/web.php</code>
                    supaya permission ini benar-benar berlaku.
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
                                    <th>Module</th>
                                    <th>Dipakai di Role</th>
                                    <th>Dipakai di Menu</th>
                                    <th style="width:150px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($permissions as $permission)
                                    <tr>
                                        <td><code>{{ $permission->code }}</code></td>
                                        <td>{{ $permission->name }}</td>
                                        <td>{{ $permission->module?->name ?? '-' }}</td>
                                        <td>{{ $permission->roles_count }}</td>
                                        <td>{{ $permission->menus_count }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPerm{{ $permission->id }}">Edit</button>
                                            <form action="{{ route('rbac.permissions.destroy', $permission) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus permission {{ $permission->code }}?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editPerm{{ $permission->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('rbac.permissions.update', $permission) }}" method="POST">
                                                    @csrf @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Permission</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Code</label>
                                                            <input type="text" name="code" class="form-control" value="{{ $permission->code }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Nama</label>
                                                            <input type="text" name="name" class="form-control" value="{{ $permission->name }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Module</label>
                                                            <select name="module_id" class="form-select">
                                                                <option value="">— Umum —</option>
                                                                @foreach ($modules as $module)
                                                                    <option value="{{ $module->id }}" {{ $permission->module_id == $module->id ? 'selected' : '' }}>{{ $module->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Deskripsi</label>
                                                            <input type="text" name="description" class="form-control" value="{{ $permission->description }}">
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
                                    <tr><td colspan="6" class="text-center text-muted">Belum ada permission.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
