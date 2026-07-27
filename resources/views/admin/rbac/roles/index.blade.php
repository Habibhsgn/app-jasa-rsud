@extends('layouts.app')
@section('title', 'Kelola Role')
@section('content')
    <h1 class="h3 mb-3">
        <strong>Kelola</strong> Role
    </h1>
    <div class="container-fluid p-0">

        @include('admin.rbac.partials.nav')

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="mb-3">
            <a href="{{ route('rbac.roles.create') }}" class="btn btn-primary">+ Tambah Role</a>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Nama</th>
                            <th>Jumlah Permission</th>
                            <th>Jumlah User</th>
                            <th style="width:220px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>
                                    <code>{{ $role->code }}</code>
                                    @if ($role->isSuper())
                                        <span class="badge bg-warning text-dark">super</span>
                                    @endif
                                </td>
                                <td>{{ $role->name }}</td>
                                <td>{{ $role->permissions_count }}</td>
                                <td>{{ $role->users_count }}</td>
                                <td>
                                    <a href="{{ route('rbac.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Kelola Permission</a>
                                    @unless ($role->isSuper())
                                        <form action="{{ route('rbac.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus role {{ $role->name }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum ada role.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
