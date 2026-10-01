@props(['status'])

@php
    $map = [
        'WAITING' => ['class' => 'badge-waiting', 'icon' => 'clock'],
        'ON_PROCESS' => ['class' => 'badge-on-process', 'icon' => 'loader-circle'],
        'ON_CHECK' => ['class' => 'badge-on-check', 'icon' => 'eye'],
        'DONE' => ['class' => 'badge-done', 'icon' => 'check-circle-2'],
    ];
    $cfg = $map[$status] ?? ['class' => 'badge-waiting', 'icon' => 'circle'];
@endphp

<span class="status-badge {{ $cfg['class'] }}">
    <i data-lucide="{{ $cfg['icon'] }}" class="badge-icon"></i>
    {{ $status === 'ON_PROCESS' ? 'ON PROCESS' : ($status === 'ON_CHECK' ? 'ON CHECK' : $status) }}
</span>
