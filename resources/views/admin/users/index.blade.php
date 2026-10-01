@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Users</h1>
        <div class="subtitle">Manage user accounts, roles and access.</div>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i data-lucide="user-plus"></i> Create User</a>
</div>

<div class="card">
    <form class="filter-bar" method="GET" action="{{ route('admin.users.index') }}" style="margin-top:0;margin-bottom:16px">
        <div class="search-field">
            <i data-lucide="search"></i>
            <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Search users..." aria-label="Search users">
        </div>
        <select name="role" class="form-control" style="width:auto" aria-label="Filter by role">
            <option value="">Role: All</option>
            @foreach($roles as $r)
                <option value="{{ $r->id }}" @selected((string) request('role') === (string) $r->id)>{{ $r->name }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control" style="width:auto" aria-label="Filter by status">
            <option value="">Status: All</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <button type="submit" class="btn btn-outline"><i data-lucide="sliders-horizontal"></i> Filter</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Clear</a>
    </form>

    <div class="table-responsive">
        <table class="data">
            <thead>
                <tr>
                    <th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $u)
                <tr>
                    <td style="font-weight:600">{{ $u->name }}</td>
                    <td class="small">{{ $u->email }}</td>
                    <td><span class="status-badge badge-on-process">{{ strtoupper($u->role->name ?? 'NO ROLE') }}</span></td>
                    <td>
                        <span class="status-badge {{ $u->is_active ? 'badge-done' : 'badge-waiting' }}">
                            <i data-lucide="{{ $u->is_active ? 'check-circle-2' : 'clock' }}" class="badge-icon"></i>
                            {{ $u->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <a href="{{ route('admin.users.edit', $u->id) }}" class="btn btn-sm btn-outline">Edit</a>
                            <form action="{{ route('admin.users.status', $u->id) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $u->is_active ? 'btn-danger' : 'btn-success' }}"
                                        onclick="return confirm('{{ $u->is_active ? 'Deactivate' : 'Activate' }} {{ $u->name }}?')">
                                    {{ $u->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i data-lucide="users" class="empty-icon"></i>
                            <h4>No users found</h4>
                            <p>There are no users matching your filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
@endsection
