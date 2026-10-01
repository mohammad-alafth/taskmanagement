@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Create User</h1>
        <div class="subtitle">Add a new account to the system.</div>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Cancel</a>
</div>

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul style="margin:0;padding-left:18px">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.users.store') }}">
    @csrf
    <div class="card">
        <div class="form-section-title" style="margin-top:0">Account</div>

        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label" for="name">Name <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label" for="password">Password <span class="req">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required minlength="8">
                <div class="form-helper">Minimum 8 characters.</div>
            </div>
            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirm Password <span class="req">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8">
            </div>
        </div>

        <div class="form-section-title">Access</div>

        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label" for="role_id">Role <span class="req">*</span></label>
                <select id="role_id" name="role_id" class="form-control" required>
                    <option value="">Select role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="is_active">Status</label>
                <select id="is_active" name="is_active" class="form-control">
                    <option value="1" @selected(old('is_active', '1') == '1')>Active</option>
                    <option value="0" @selected(old('is_active') == '0')>Inactive</option>
                </select>
                <div class="form-helper">Inactive users cannot log in.</div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Create User</button>
        </div>
    </div>
</form>
@endsection
