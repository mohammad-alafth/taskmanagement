@extends('layouts.app')

@section('title', 'Notifications')

@php
    $today = $notifications->getCollection()->filter(fn ($n) => $n->created_at->isToday());
    $earlier = $notifications->getCollection()->reject(fn ($n) => $n->created_at->isToday());
    $typeIcons = [
        'TASK_ASSIGNED' => 'user-plus',
        'TASK_SUBMITTED' => 'send',
        'TASK_REVISION' => 'rotate-ccw',
        'TASK_APPROVED' => 'check-circle',
        'TASK_REOPENED' => 'rotate-ccw',
        'COMMENT' => 'message-circle',
    ];
    $activeFilter = request('filter', 'all');
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Notifications</h1>
        <div class="subtitle">{{ $unreadCount }} unread notification(s).</div>
    </div>
    @if($unreadCount > 0)
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-outline"><i data-lucide="check-check"></i> Mark all as read</button>
        </form>
    @endif
</div>

<div class="card">
    <div class="tabs" role="tablist">
        @foreach(['all' => 'All', 'unread' => 'Unread', 'task' => 'Task', 'review' => 'Review'] as $key => $label)
            <a class="tab {{ $activeFilter === $key ? 'active' : '' }}"
               href="{{ route('notifications.index', ['filter' => $key]) }}"
               role="tab" aria-selected="{{ $activeFilter === $key ? 'true' : 'false' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if($notifications->isEmpty())
        <div class="empty-state">
            <i data-lucide="bell-off" class="empty-icon"></i>
            <h4>You're all caught up</h4>
            <p>No new notifications.</p>
        </div>
    @else
        @if($today->isNotEmpty())
            <div class="nav-section-label" style="margin-top:20px">TODAY</div>
            @foreach($today as $notification)
                @include('notifications._item', ['notification' => $notification, 'typeIcons' => $typeIcons])
            @endforeach
        @endif

        @if($earlier->isNotEmpty())
            <div class="nav-section-label" style="margin-top:20px">EARLIER</div>
            @foreach($earlier as $notification)
                @include('notifications._item', ['notification' => $notification, 'typeIcons' => $typeIcons])
            @endforeach
        @endif
    @endif

    {{ $notifications->links() }}
</div>
@endsection
