@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit User</h1>
        <div class="subtitle">{{ $user->name }} · {{ $user->email }}</div>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Back to Users</a>
</div>

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul style="margin:0;padding-left:18px">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="grid grid-2">
    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="form-section-title" style="margin-top:0">Account</div>

            <div class="form-group">
                <label class="form-label" for="name">Name <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="role_id">Role <span class="req">*</span></label>
                <select id="role_id" name="role_id" class="form-control" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id) === (string) $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:12px">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </form>

    <div>
        <form method="POST" action="{{ route('admin.users.password', $user->id) }}">
            @csrf @method('PATCH')
            <div class="card">
                <div class="form-section-title" style="margin-top:0">Reset Password</div>
                <div class="form-group">
                    <label class="form-label" for="password">New Password <span class="req">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Confirm Password <span class="req">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8">
                </div>
                <div style="display:flex;justify-content:flex-end">
                    <button type="submit" class="btn btn-outline" onclick="return confirm('Reset password for {{ $user->name }}?')">Reset Password</button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="form-section-title" style="margin-top:0">Status</div>
            <div class="info-row"><span class="k">Current status</span>
                <span class="v">
                    <span class="status-badge {{ $user->is_active ? 'badge-done' : 'badge-waiting' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </span>
            </div>
            <form method="POST" action="{{ route('admin.users.status', $user->id) }}" style="margin-top:12px">
                @csrf @method('PATCH')
                <button type="submit" class="btn {{ $user->is_active ? 'btn-danger' : 'btn-success' }} btn-block">
                    {{ $user->is_active ? 'Deactivate User' : 'Activate User' }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
