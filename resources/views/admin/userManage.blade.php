@extends('layouts.app')

@section('title', 'Manajemen User & Aktivasi')

@section('content')
    <div class="container-fluid p-0">

        <h1 class="h3 mb-3">Manajemen <strong>User</strong></h1>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daftar Pengguna Sistem</h5>
                        <p class="text-muted small">Aktifkan user yang baru mendaftar agar mereka bisa login ke dalam sistem.
                        </p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover my-0">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th class="d-none d-xl-table-cell">Email</th>
                                        <th>Role</th>
                                        <th class="d-none d-md-table-cell">Ruangan</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                        <th>Ubah Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="{{ asset('assets/img/avatars/avatar.png') }}"
                                                        class="rounded-circle me-2" width="32" height="32"
                                                        alt="Avatar">
                                                    <div class="flex-grow-1">{{ $user->name }}</div>
                                                </div>
                                            </td>
                                            <td class="d-none d-xl-table-cell">{{ $user->email }}</td>
                                            <td>
                                                <span class="badge {{ $user->role == 'admin' ? 'bg-primary' : 'bg-info' }}">
                                                    {{ strtoupper($user->role) }}
                                                </span>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                {{ $user->ruangan->nama_ruangan ?? '-' }}
                                            </td>
                                            <td>
                                                @if ($user->is_active)
                                                    <span class="badge bg-success">Aktif</span>
                                                @else
                                                    <span class="badge bg-danger">Belum Aktif</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if ($user->role !== 'admin')
                                                    <form action="{{ route('users.toggle', $user->id) }}" method="POST"
                                                        class="d-inline">
                                                        @csrf
                                                        @method('PATCH')

                                                        @if ($user->is_active)
                                                            <button class="btn btn-sm btn-outline-danger">
                                                                Nonaktifkan
                                                            </button>
                                                        @else
                                                            <button class="btn btn-sm btn-success">
                                                                Aktivasi
                                                            </button>
                                                        @endif
                                                    </form>
                                                @else
                                                    <span class="text-muted">Protected</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($user->role !== 'admin')
                                                    <form action="{{ route('users.updateRole', $user->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PATCH')

                                                        <select name="role" class="form-select form-select-sm"
                                                            onchange="this.form.submit()">
                                                            <option value="admin"
                                                                {{ $user->role == 'admin' ? 'selected' : '' }}>ADMIN
                                                            </option>
                                                            <option value="karu"
                                                                {{ $user->role == 'karu' ? 'selected' : '' }}>KARU</option>
                                                            <option value="koordinator_karu"
                                                                {{ $user->role == 'koordinator_karu' ? 'selected' : '' }}>
                                                                KOORDINATOR KARU</option>
                                                        </select>
                                                    </form>
                                                @else
                                                    <span class="badge bg-dark">LOCKED</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Tidak ada data user lain
                                                ditemukan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
