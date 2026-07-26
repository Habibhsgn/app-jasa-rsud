@extends('layouts.app')
@section('title', 'Override Permission User')
@section('content')
    <h1 class="h3 mb-3">
        <strong>Override</strong> Permission User
    </h1>
    <div class="container-fluid p-0">

        @include('admin.rbac.partials.nav')

        <p class="text-muted">
            Dipakai untuk kasus khusus: kasih akses tambahan ke satu user tanpa ganti role-nya,
            atau cabut satu akses dari user tertentu walau role-nya harusnya boleh.
        </p>

        <form method="GET" class="mb-3">
            <div class="input-group" style="max-width:320px">
                <input type="text" name="search" class="form-control" placeholder="Cari nama user..." value="{{ request('search') }}">
                <button class="btn btn-outline-secondary">Cari</button>
            </div>
        </form>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th style="width:150px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role?->name ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('rbac.overrides.edit', $user) }}" class="btn btn-sm btn-outline-primary">Kelola Override</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">User tidak ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
