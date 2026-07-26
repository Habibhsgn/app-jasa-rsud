@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
    <h1 class="h3 mb-3">
        <strong>Edit Role</strong>
        <small class="text-muted">/ {{ $role->name }}</small>
    </h1>

    <div class="container-fluid p-0">

        @include('admin.rbac.partials.nav')

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('rbac.roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Informasi Role --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <strong>Informasi Role</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $role->code) }}"
                                {{ $role->isSuper() ? 'readonly' : '' }} required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nama Role</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $role->name) }}"
                                required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description', $role->description) }}</textarea>
                        </div>

                    </div>
                </div>
            </div>

            @if ($role->isSuper())
                <div class="alert alert-info">
                    <i class="align-middle me-1" data-feather="shield"></i>

                    <strong>Super Role</strong>

                    <div class="small mt-1">
                        Role ini otomatis memiliki seluruh permission.
                        Checklist di bawah hanya sebagai dokumentasi.
                    </div>
                </div>
            @endif


            {{-- Permission --}}
            <div class="card shadow-sm">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <strong>Permission</strong>

                    <div class="d-flex gap-2">

                        <button type="button" class="btn btn-sm btn-outline-primary"
                            onclick="document.querySelectorAll('.perm-checkbox').forEach(c=>c.checked=true)">
                            Pilih Semua
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="document.querySelectorAll('.perm-checkbox').forEach(c=>c.checked=false)">
                            Kosongkan
                        </button>

                    </div>

                </div>

                <div class="card-body">

                    {{-- Permission tanpa module --}}
                    @if ($unassigned->isNotEmpty())

                        <div class="border rounded p-3 mb-4">

                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <h5 class="mb-0">
                                    Umum
                                    <span class="badge bg-secondary">
                                        {{ $unassigned->count() }}
                                    </span>
                                </h5>

                            </div>

                            <div class="row g-2">

                                @foreach ($unassigned as $permission)
                                    <div class="col-lg-4 col-md-6">

                                        <div class="form-check">

                                            <input class="form-check-input perm-checkbox" type="checkbox"
                                                name="permissions[]" value="{{ $permission->id }}"
                                                id="perm-{{ $permission->id }}"
                                                {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}>

                                            <label class="form-check-label" for="perm-{{ $permission->id }}">

                                                <strong>{{ $permission->name }}</strong>

                                                <br>

                                                <small class="text-muted">
                                                    {{ $permission->code }}
                                                </small>

                                            </label>

                                        </div>

                                    </div>
                                @endforeach

                            </div>

                        </div>

                    @endif


                    {{-- Permission per Module --}}
                    @foreach ($modules as $module)
                        @continue($module->permissions->isEmpty())

                        <div class="border rounded p-3 mb-4 module-group">

                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <h5 class="mb-0">

                                    {{ $module->name }}

                                    <span class="badge bg-primary">
                                        {{ $module->permissions->count() }}
                                    </span>

                                </h5>

                                <button type="button" class="btn btn-sm btn-outline-success"
                                    onclick="this.closest('.module-group').querySelectorAll('.perm-checkbox').forEach(c=>c.checked=true)">
                                    Pilih Semua
                                </button>

                            </div>

                            <div class="row g-2">

                                @foreach ($module->permissions as $permission)
                                    <div class="col-lg-4 col-md-6">

                                        <div class="form-check">

                                            <input class="form-check-input perm-checkbox" type="checkbox"
                                                name="permissions[]" value="{{ $permission->id }}"
                                                id="perm-{{ $permission->id }}"
                                                {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}>

                                            <label class="form-check-label" for="perm-{{ $permission->id }}">

                                                <strong>{{ $permission->name }}</strong>

                                                <br>

                                                <small class="text-muted">
                                                    {{ $permission->code }}
                                                </small>

                                            </label>

                                        </div>

                                    </div>
                                @endforeach

                            </div>

                        </div>
                    @endforeach

                </div>

                <div class="card-footer text-end">

                    <a href="{{ route('rbac.roles.index') }}" class="btn btn-outline-secondary">
                        Batal
                    </a>

                    <button class="btn btn-primary">
                        Simpan Perubahan
                    </button>

                </div>

            </div>

        </form>

    </div>
@endsection
