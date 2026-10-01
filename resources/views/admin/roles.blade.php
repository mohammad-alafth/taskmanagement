@extends('layouts.app')

@section('title', 'Roles & Permissions')

@php
    $roleKeys = ['admin' => 'ADMIN', 'manager' => 'MANAGER', 'staff' => 'STAFF', 'checker' => 'CHECKER'];
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Roles &amp; Permissions</h1>
        <div class="subtitle">Permission matrix (PRD §4.2). Backend policies remain the source of truth.</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data">
            <thead>
                <tr>
                    <th>Permission</th>
                    @foreach($roleKeys as $label)
                        <th style="text-align:center">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @foreach($permissions as $group => $items)
                <tr>
                    <td colspan="5" style="background:var(--color-bg);font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted)">
                        {{ $group }}
                    </td>
                </tr>
                @foreach($items as $name => $matrix)
                    <tr>
                        <td>{{ $name }}</td>
                        @foreach($roleKeys as $key => $label)
                            <td style="text-align:center">
                                @php($value = $matrix[$key] ?? false)
                                @if($value === true)
                                    <span style="color:var(--color-done);font-weight:700" title="Allowed">✓</span>
                                @elseif($value === 'own')
                                    <span style="color:var(--color-waiting);font-weight:600;font-size:12px" title="Own/assigned only">own</span>
                                @else
                                    <span style="color:var(--color-text-muted)" title="Not allowed">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2 class="card-title" style="margin-bottom:12px">Roles</h2>
    <div class="grid grid-4">
        @foreach($roles as $role)
            <div class="stat-card" style="box-shadow:none">
                <div class="stat-label">{{ strtoupper($role->name) }}</div>
                <div class="caption" style="margin-top:6px">slug: {{ $role->slug }}</div>
                <div class="caption">{{ $role->users_count ?? $role->users()->count() }} user(s)</div>
            </div>
        @endforeach
    </div>
</div>
@endsection
