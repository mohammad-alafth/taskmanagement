@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $greeting = now()->hour < 12 ? 'Good morning' : (now()->hour < 18 ? 'Good afternoon' : 'Good evening');
    $isManagerLevel = $user->isAdmin() || $user->isManager();
    $initials = collect(explode(' ', $user->name))->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <div class="subtitle">{{ $greeting }}, {{ $user->name }} — here's what needs your attention.</div>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
        <form action="{{ route('tasks.index') }}" method="GET" role="search" style="display:flex;gap:8px">
            <input type="search" name="search" class="form-control" placeholder="Search tasks..." aria-label="Search tasks" style="width:220px">
        </form>
        @can('create', App\Models\Task::class)
            <a href="{{ route('tasks.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Create Task</a>
        @endcan
    </div>
</div>

{{-- KPI cards (PRD §7.1) --}}
<div class="grid grid-4" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-label"><span class="stat-accent" style="background:var(--color-waiting)"></span> WAITING</div>
        <div class="stat-value">{{ $waiting }}</div>
        <div class="stat-meta">Not started yet</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><span class="stat-accent" style="background:var(--color-onprocess)"></span> ON PROCESS</div>
        <div class="stat-value">{{ $onProcess }}</div>
        <div class="stat-meta">In progress</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><span class="stat-accent" style="background:var(--color-oncheck)"></span> ON CHECK</div>
        <div class="stat-value">{{ $onCheck }}</div>
        <div class="stat-meta">Waiting for review</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><span class="stat-accent" style="background:var(--color-done)"></span> DONE</div>
        <div class="stat-value">{{ $done }}</div>
        <div class="stat-meta">Completed</div>
    </div>
</div>

<div class="grid grid-2" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-label"><i data-lucide="alert-triangle" style="width:14px;height:14px;color:var(--color-overdue)"></i> OVERDUE</div>
        <div class="stat-value" style="color:var(--color-overdue)">{{ $overdue }}</div>
        <div class="stat-meta">Past due date, not done</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><i data-lucide="clock" style="width:14px;height:14px;color:var(--color-waiting)"></i> DUE SOON</div>
        <div class="stat-value" style="color:var(--color-waiting)">{{ $dueSoon }}</div>
        <div class="stat-meta">Due within 3 days</div>
    </div>
</div>

{{-- Checker focus (PRD §7.2 / UI §16) --}}
@if($user->isChecker() || $user->isAdmin() || $user->isManager())
    <div class="card">
        <div class="card-header-row">
            <h2 class="section-title"><i data-lucide="eye" style="width:18px;height:18px;vertical-align:-3px"></i> Tasks Waiting for Your Review</h2>
            <span class="caption">{{ $waitingForReview->count() }} task(s)</span>
        </div>
        @if($waitingForReview->isEmpty())
            <div class="empty-state" style="padding:24px">
                <h4>You're all caught up</h4>
                <p>No tasks are waiting for your review right now.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="data">
                    <thead><tr><th>Task</th><th>Assignee</th><th>Priority</th><th>Due Date</th><th></th></tr></thead>
                    <tbody>
                    @foreach($waitingForReview as $task)
                        <tr>
                            <td><a href="{{ route('tasks.show', $task->id) }}">#{{ $task->id }} {{ $task->title }}</a></td>
                            <td>{{ $task->assignee->name ?? '-' }}</td>
                            <td><x-priority-badge :priority="$task->priority" /></td>
                            <td class="small">{{ $task->due_date?->format('d M Y') ?? '—' }}</td>
                            <td><a class="btn btn-sm btn-primary" href="{{ route('tasks.show', $task->id) }}">Review</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif

{{-- Staff focus: my tasks first (UI §15) --}}
@if(! $isManagerLevel)
    <div class="card">
        <div class="card-header-row">
            <h2 class="section-title"><i data-lucide="clipboard-list" style="width:18px;height:18px;vertical-align:-3px"></i> Recent Assigned Tasks</h2>
            <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline">View all</a>
        </div>
        @if($myTasks->isEmpty())
            <div class="empty-state" style="padding:24px">
                <h4>You're all caught up</h4>
                <p>You currently have no assigned tasks.</p>
            </div>
        @else
            <div class="table-responsive desktop-only">
                <table class="data">
                    <thead><tr><th>Task</th><th>Status</th><th>Priority</th><th>Due Date</th></tr></thead>
                    <tbody>
                    @foreach($myTasks as $task)
                        <tr>
                            <td><a href="{{ route('tasks.show', $task->id) }}">#{{ $task->id }} {{ $task->title }}</a></td>
                            <td><x-status-badge :status="$task->status" /></td>
                            <td><x-priority-badge :priority="$task->priority" /></td>
                            <td class="small">{{ $task->due_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mobile-cards mobile-only">
                @foreach($myTasks as $task)
                    <a href="{{ route('tasks.show', $task->id) }}" class="card" style="padding:16px;display:block">
                        <div style="display:flex;justify-content:space-between;gap:8px">
                            <span class="caption">#{{ $task->id }}</span>
                            <x-priority-badge :priority="$task->priority" />
                        </div>
                        <div style="font-weight:600;margin:6px 0">{{ $task->title }}</div>
                        <x-status-badge :status="$task->status" />
                        <div class="caption" style="margin-top:8px">Due {{ $task->due_date?->format('d M Y') ?? 'no date' }}</div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endif

{{-- Admin / Manager blocks (UI §14) --}}
@if($isManagerLevel)
    <div class="grid grid-2" style="margin-top:24px">
        <div class="card">
            <div class="card-header-row">
                <h2 class="card-title">Recent Tasks</h2>
                <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline">View all</a>
            </div>
            @if($recentTasks->isEmpty())
                <div class="empty-state" style="padding:24px">
                    <h4>No tasks yet</h4>
                    <p>Create the first task to get started.</p>
                    @can('create', App\Models\Task::class)
                        <a href="{{ route('tasks.create') }}" class="btn btn-primary">Create Task</a>
                    @endcan
                </div>
            @else
                <div class="table-responsive">
                    <table class="data">
                        <thead><tr><th>Task</th><th>Assignee</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($recentTasks as $task)
                            <tr>
                                <td><a href="{{ route('tasks.show', $task->id) }}">#{{ $task->id }} {{ $task->title }}</a></td>
                                <td class="small">{{ $task->assignee->name ?? 'Unassigned' }}</td>
                                <td><x-status-badge :status="$task->status" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header-row">
                <h2 class="card-title">Due Soon</h2>
                <span class="caption">Next 3 days</span>
            </div>
            @if($dueSoonTasks->isEmpty())
                <div class="empty-state" style="padding:24px">
                    <h4>Nothing due soon</h4>
                    <p>No tasks are due in the next 3 days.</p>
                </div>
            @else
                <ul class="timeline">
                    @foreach($dueSoonTasks as $task)
                        <li>
                            <div class="t-time">Due {{ $task->due_date->format('d M Y') }}</div>
                            <div class="t-body">
                                <a href="{{ route('tasks.show', $task->id) }}">#{{ $task->id }} {{ $task->title }}</a>
                                <div class="caption">{{ $task->assignee->name ?? 'Unassigned' }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    @if($tasksByAssignee->isNotEmpty())
        <div class="card" style="margin-top:24px">
            <h2 class="card-title" style="margin-bottom:16px">Tasks by Assignee</h2>
            <div class="grid grid-3">
                @foreach($tasksByAssignee as $name => $count)
                    <div class="stat-card" style="box-shadow:none">
                        <div class="stat-label">{{ $name }}</div>
                        <div class="stat-value" style="font-size:24px">{{ $count }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif

{{-- Recently approved (checker) --}}
@if(($user->isChecker() || $user->isAdmin() || $user->isManager()) && $recentlyApproved->isNotEmpty())
    <div class="card">
        <h2 class="card-title" style="margin-bottom:16px">Recently Approved</h2>
        <div class="table-responsive">
            <table class="data">
                <thead><tr><th>Task</th><th>Assignee</th><th>Status</th><th>Completed</th></tr></thead>
                <tbody>
                @foreach($recentlyApproved as $task)
                    <tr>
                        <td><a href="{{ route('tasks.show', $task->id) }}">#{{ $task->id }} {{ $task->title }}</a></td>
                        <td class="small">{{ $task->assignee->name ?? '-' }}</td>
                        <td><x-status-badge :status="$task->status" /></td>
                        <td class="small">{{ $task->completed_at?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Recent activity (PRD §7.2) --}}
<div class="card" style="margin-top:24px">
    <h2 class="card-title" style="margin-bottom:16px"><i data-lucide="history" style="width:16px;height:16px;vertical-align:-2px"></i> Recent Activity</h2>
    @if($recentActivities->isEmpty())
        <div class="empty-state" style="padding:24px">
            <h4>No activity yet</h4>
            <p>Activity will appear here as tasks change.</p>
        </div>
    @else
        <ul class="timeline">
            @foreach($recentActivities as $activity)
                <li>
                    <div class="t-time">{{ $activity->created_at->format('d M Y · H:i') }}</div>
                    <div class="t-body">
                        <strong>{{ $activity->user->name ?? 'System' }}</strong>
                        {{ $activity->description }}
                        @if($activity->task)
                            <div class="caption"><a href="{{ route('tasks.show', $activity->task_id) }}">#{{ $activity->task_id }} {{ $activity->task->title }}</a></div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
