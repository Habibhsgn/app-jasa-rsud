@extends('layouts.app')
@section('title', 'Kelola Menu')
@section('content')
    <h1 class="h3 mb-3">
        <strong>Kelola</strong> Menu Sidebar
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
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMenu">+ Tambah Menu</button>
        </div>

        @foreach ($headers as $header)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ $header->name }}</strong> <span class="text-muted small">(header, urutan {{ $header->order }})</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editMenu{{ $header->id }}">Edit</button>
                        <form action="{{ route('rbac.menus.destroy', $header) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus header {{ $header->name }}? Pastikan tidak ada item di bawahnya.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Route</th>
                                <th>Permission (OR)</th>
                                <th>Status</th>
                                <th style="width:150px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($header->children as $menu)
                                <tr>
                                    <td><i data-feather="{{ $menu->icon }}"></i> {{ $menu->name }}</td>
                                    <td><code>{{ $menu->route_name }}</code></td>
                                    <td>
                                        @forelse ($menu->permissions as $p)
                                            <span class="badge bg-light text-dark border">{{ $p->code }}</span>
                                        @empty
                                            <span class="text-muted">terbuka untuk semua user login</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if ($menu->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editMenu{{ $menu->id }}">Edit</button>
                                        <form action="{{ route('rbac.menus.destroy', $menu) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus menu {{ $menu->name }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Belum ada item di header ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        {{-- Modal: create --}}
        <div class="modal fade" id="createMenu" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('rbac.menus.store') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Menu</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @include('admin.rbac.menus._form', ['menu' => null])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal: edit per header & item --}}
        @foreach ($allHeaders as $header)
            <div class="modal fade" id="editMenu{{ $header->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('rbac.menus.update', $header) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Edit: {{ $header->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                @include('admin.rbac.menus._form', ['menu' => $header])
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                <button class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @foreach ($header->children as $menu)
                <div class="modal fade" id="editMenu{{ $menu->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('rbac.menus.update', $menu) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit: {{ $menu->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    @include('admin.rbac.menus._form', ['menu' => $menu])
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button class="btn btn-primary">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
@endsection
