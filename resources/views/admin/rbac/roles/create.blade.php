@extends('layouts.app')
@section('title', 'Tambah Role')
@section('content')
    <h1 class="h3 mb-3">
        <strong>Tambah</strong> Role
    </h1>
    <div class="container-fluid p-0">

        @include('admin.rbac.partials.nav')

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('rbac.roles.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" placeholder="mis. supervisor" value="{{ old('code') }}" required>
                        <small class="text-muted">Huruf kecil, tanpa spasi, dipakai internal di kode (mis. <code>role:supervisor</code>).</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" placeholder="mis. Supervisor Ruangan" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi (opsional)</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                    </div>
                    <button class="btn btn-primary">Simpan & Atur Permission</button>
                    <a href="{{ route('rbac.roles.index') }}" class="btn btn-outline-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
@endsection
