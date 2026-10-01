@props(['priority'])

@php
    $map = [
        'LOW' => 'priority-low',
        'MEDIUM' => 'priority-medium',
        'HIGH' => 'priority-high',
    ];
@endphp

<span class="priority-badge {{ $map[$priority] ?? 'priority-medium' }}">
    <i data-lucide="flag" class="badge-icon"></i>
    {{ $priority }}
</span>
