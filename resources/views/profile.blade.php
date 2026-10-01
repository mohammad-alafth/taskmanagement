@extends('layouts.app')

@section('title', 'Profile')

@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Profile</h1>
        <div class="subtitle">Your account information.</div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card" style="text-align:center">
        <div style="width:88px;height:88px;border-radius:50%;background:var(--color-brand);color:#fff;font-size:32px;font-weight:700;display:grid;place-items:center;margin:8px auto 16px">
            {{ $initials }}
        </div>
        <div style="font-size:20px;font-weight:650">{{ $profile->name }}</div>
        <div class="muted" style="margin-bottom:16px">{{ $profile->role->name ?? 'No role' }}</div>
        <span class="status-badge {{ $profile->is_active ? 'badge-done' : 'badge-waiting' }}">
            <i data-lucide="{{ $profile->is_active ? 'check-circle-2' : 'clock' }}" class="badge-icon"></i>
            {{ $profile->is_active ? 'ACTIVE' : 'INACTIVE' }}
        </span>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px">Personal Information</h2>
        <div class="info-row"><span class="k">Name</span><span class="v">{{ $profile->name }}</span></div>
        <div class="info-row"><span class="k">Email</span><span class="v">{{ $profile->email }}</span></div>
        <div class="info-row"><span class="k">Role</span><span class="v">{{ $profile->role->name ?? '—' }}</span></div>
        <div class="info-row"><span class="k">Status</span><span class="v">{{ $profile->is_active ? 'Active' : 'Inactive' }}</span></div>
        <div class="info-row"><span class="k">Member Since</span><span class="v">{{ $profile->created_at->format('d M Y') }}</span></div>

        <div style="display:flex;gap:10px;margin-top:20px">
            <a href="{{ route('logout') }}" class="btn btn-outline btn-sm"><i data-lucide="log-out"></i> Logout</a>
        </div>
    </div>
</div>
@endsection
