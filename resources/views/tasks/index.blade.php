@extends('layouts.app')

@section('title', 'Tasks')

@php
    $tabs = [
        'all' => ['label' => 'All', 'count' => $tabCounts['ALL'] ?? 0],
        'WAITING' => ['label' => 'Waiting', 'count' => $tabCounts['WAITING'] ?? 0],
        'ON_PROCESS' => ['label' => 'On Process', 'count' => $tabCounts['ON_PROCESS'] ?? 0],
        'ON_CHECK' => ['label' => 'On Check', 'count' => $tabCounts['ON_CHECK'] ?? 0],
        'DONE' => ['label' => 'Done', 'count' => $tabCounts['DONE'] ?? 0],
    ];
    $activeTab = request('status', 'all');
    $hasFilters = request('search') || request('priority') || request('assignee_id') || request('checker_id')
        || request('due_date') || request('created_date') || ($activeTab && $activeTab !== 'all');

    $columnMeta = [
        'WAITING' => ['label' => 'WAITING', 'icon' => 'clock', 'class' => 'badge-waiting'],
        'ON_PROCESS' => ['label' => 'ON PROCESS', 'icon' => 'loader-circle', 'class' => 'badge-on-process'],
        'ON_CHECK' => ['label' => 'ON CHECK', 'icon' => 'eye', 'class' => 'badge-on-check'],
        'DONE' => ['label' => 'DONE', 'icon' => 'check-circle-2', 'class' => 'badge-done'],
    ];
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tasks</h1>
        <div class="subtitle">Manage and monitor all tasks.</div>
    </div>
    @can('create', App\Models\Task::class)
        <a href="{{ route('tasks.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Create Task</a>
    @endcan
</div>

<div class="card">
    {{-- Status tabs are filter shortcuts, not workflow navigation (DragDrop UI §17.4) --}}
    <div class="tabs" role="tablist">
        @foreach($tabs as $key => $tab)
            <a class="tab {{ $activeTab === $key ? 'active' : '' }}"
               data-tab-status="{{ $key }}"
               href="{{ route('tasks.index', array_merge(request()->query(), ['status' => $key === 'all' ? null : $key])) }}"
               role="tab" aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}">
                {{ $tab['label'] }} <span class="count">({{ $tab['count'] }})</span>
            </a>
        @endforeach
    </div>

    {{-- Filter bar (DragDrop PRD §8.4) --}}
    <form class="filter-bar" method="GET" action="{{ route('tasks.index') }}">
        @if($activeTab && $activeTab !== 'all')
            <input type="hidden" name="status" value="{{ $activeTab }}">
        @endif
        <input type="hidden" name="view" value="{{ $view }}">
        <div class="search-field">
            <i data-lucide="search"></i>
            <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search title / Task ID" aria-label="Search tasks">
        </div>
        <select name="priority" class="form-control" style="width:auto" aria-label="Filter by priority">
            <option value="">Priority: All</option>
            @foreach(['LOW', 'MEDIUM', 'HIGH'] as $p)
                <option value="{{ $p }}" @selected(request('priority') === $p)>{{ $p }}</option>
            @endforeach
        </select>
        <select name="assignee_id" class="form-control" style="width:auto" aria-label="Filter by assignee">
            <option value="">Assignee: All</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected((string) request('assignee_id') === (string) $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        <select name="checker_id" class="form-control" style="width:auto" aria-label="Filter by checker">
            <option value="">Checker: All</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected((string) request('checker_id') === (string) $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        <input type="date" name="due_date" value="{{ request('due_date') }}" class="form-control" style="width:auto" aria-label="Filter by due date" title="Due date">
        <input type="date" name="created_date" value="{{ request('created_date') }}" class="form-control" style="width:auto" aria-label="Filter by created date" title="Created date">
        <button type="submit" class="btn btn-outline"><i data-lucide="sliders-horizontal"></i> Filter</button>
        @if($hasFilters)
            <a href="{{ route('tasks.index') }}" class="btn btn-outline">Clear Filters</a>
        @endif
    </form>

    {{-- Board / Table view toggle (DragDrop PRD §8.5) --}}
    <div class="view-toggle" role="group" aria-label="Task view">
        <a href="{{ route('tasks.index', array_merge(request()->query(), ['view' => 'board'])) }}"
           class="{{ $view === 'board' ? 'active' : '' }}" aria-pressed="{{ $view === 'board' ? 'true' : 'false' }}">
            <i data-lucide="columns-3"></i> Board
        </a>
        <a href="{{ route('tasks.index', array_merge(request()->query(), ['view' => 'table'])) }}"
           class="{{ $view === 'table' ? 'active' : '' }}" aria-pressed="{{ $view === 'table' ? 'true' : 'false' }}">
            <i data-lucide="table-2"></i> Table
        </a>
    </div>

    @if($view === 'board')
        {{-- Kanban board (DragDrop PRD §8.1) --}}
        @if(($boardTotal ?? 0) === 0)
            <div class="empty-state" data-board-empty>
                <i data-lucide="inbox" class="empty-icon"></i>
                <h4>No tasks found</h4>
                <p>There are no tasks matching your current filters.</p>
                <a href="{{ route('tasks.index') }}" class="btn btn-outline">Clear Filters</a>
            </div>
        @else
            <div class="kanban-board" data-kanban-board data-can-drop='@json($canDropInto)'>
                @foreach($board as $status => $col)
                    @php
                        $meta = $columnMeta[$status];
                    @endphp
                    <section class="kanban-col" data-kanban-column="{{ $status }}" aria-label="{{ $meta['label'] }} column">
                        <header class="kanban-col-head">
                            <i data-lucide="{{ $meta['icon'] }}" class="col-icon {{ $meta['class'] }}"></i>
                            <span>{{ $meta['label'] }}</span>
                            <span class="col-count" data-col-count="{{ $status }}">{{ $col['total'] }}</span>
                        </header>
                        <div class="kanban-col-body" data-col-body="{{ $status }}">
                            @forelse($col['tasks'] as $task)
                                @include('tasks._card', ['task' => $task, 'validTargets' => $validTargets[$task->id] ?? []])
                            @empty
                                <div class="kanban-empty" data-col-empty>
                                    <p>No tasks</p>
                                    @if(in_array($status, $canDropInto, true))
                                        <p class="hint">Drop a valid task here</p>
                                    @endif
                                </div>
                            @endforelse
                        </div>
                        @if($col['has_more'])
                            <button type="button" class="kanban-load-more" data-load-more data-page="{{ $col['next_page'] }}">
                                Load more
                            </button>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    @else
        {{-- Desktop table (secondary view, DragDrop PRD §17.14) --}}
        <div class="table-responsive desktop-only" style="margin-top:20px">
            <table class="data">
                <thead>
                    <tr>
                        <th>Task ID</th>
                        <th>Title</th>
                        <th>Assignee</th>
                        <th>Checker</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($tasks as $task)
                    @php
                        $isOverdue = $task->due_date && $task->due_date->isPast() && $task->status !== 'DONE';
                    @endphp
                    <tr>
                        <td class="small muted">#{{ $task->id }}</td>
                        <td>
                            <a href="{{ route('tasks.show', $task->id) }}" style="font-weight:600">{{ $task->title }}</a>
                        </td>
                        <td class="small">{{ $task->assignee->name ?? 'Unassigned' }}</td>
                        <td class="small">{{ $task->checker->name ?? '—' }}</td>
                        <td><x-priority-badge :priority="$task->priority" /></td>
                        <td>
                            <x-status-badge :status="$task->status" />
                            @if($isOverdue)
                                <span class="status-badge badge-overdue" title="Overdue"><i data-lucide="alert-triangle" class="badge-icon"></i> OVERDUE</span>
                            @endif
                        </td>
                        <td class="small {{ $isOverdue ? '' : 'muted' }}" @if($isOverdue) style="color:var(--color-overdue);font-weight:600" @endif>
                            {{ $task->due_date?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="small muted">{{ $task->updated_at->diffForHumans(short: true) }}</td>
                        <td>
                            <div style="display:flex;gap:6px">
                                <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-outline" aria-label="View task">View</a>
                                @can('edit', $task)
                                    <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-sm btn-outline" aria-label="Edit task">Edit</a>
                                @endcan
                                @can('delete', $task)
                                    <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" style="display:inline"
                                          onsubmit="return confirm('Delete Task?\n\nAre you sure you want to delete \"{{ $task->title }}\"?\n\nThis action cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" aria-label="Delete task">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i data-lucide="inbox" class="empty-icon"></i>
                                <h4>No tasks found</h4>
                                <p>There are no tasks matching your current filters.</p>
                                <a href="{{ route('tasks.index') }}" class="btn btn-outline">Clear Filters</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="mobile-cards mobile-only" style="margin-top:20px">
            @forelse($tasks as $task)
                <a href="{{ route('tasks.show', $task->id) }}" class="card" style="padding:16px;display:block;border-radius:var(--radius-md)">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                        <span class="caption">#{{ $task->id }}</span>
                        <x-priority-badge :priority="$task->priority" />
                    </div>
                    <div style="font-weight:600;font-size:15px;margin:8px 0">{{ $task->title }}</div>
                    <div class="small text-secondary" style="margin-bottom:8px">
                        {{ $task->assignee->name ?? 'Unassigned' }} · Checker: {{ $task->checker->name ?? '—' }}
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                        <x-status-badge :status="$task->status" />
                        <span class="caption">Due {{ $task->due_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                </a>
            @empty
                <div class="empty-state">
                    <i data-lucide="inbox" class="empty-icon"></i>
                    <h4>No tasks found</h4>
                    <p>There are no tasks matching your current filters.</p>
                    <a href="{{ route('tasks.index') }}" class="btn btn-outline">Clear Filters</a>
                </div>
            @endforelse
        </div>

        {{ $tasks->links() }}
    @endif
</div>

{{-- Transition confirmation (DragDrop UI §17.8) --}}
<div class="modal-overlay" id="transition-modal" role="dialog" aria-modal="true" aria-labelledby="transition-modal-title">
    <div class="modal">
        <h3 id="transition-modal-title" data-role="title">Confirm</h3>
        <p data-role="body" style="color:var(--color-text-secondary);white-space:pre-line;margin:8px 0 0"></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
            <button type="button" class="btn btn-primary" data-role="confirm">Confirm</button>
        </div>
    </div>
</div>

{{-- Revision reason (DragDrop UI §17.8 / PRD §5.3.6) --}}
<div class="modal-overlay" id="revision-modal" role="dialog" aria-modal="true" aria-labelledby="revision-modal-title">
    <div class="modal">
        <h3 id="revision-modal-title">Request Revision</h3>
        <div class="form-group" style="margin-top:12px">
            <label class="form-label" for="revision-reason">Reason <span class="req">*</span></label>
            <textarea id="revision-reason" class="form-control" rows="3" placeholder="Please revise page 3 and update totals."></textarea>
            <div class="form-error" id="revision-reason-error" hidden>The reason is required to request a revision.</div>
            <div class="form-helper">This task will return to ON PROCESS.</div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
            <button type="button" class="btn btn-warning" id="revision-submit">Request Revision</button>
        </div>
    </div>
</div>

{{-- Change Status fallback (DragDrop PRD §8.8 / UI §17.11) --}}
<div class="modal-overlay" id="change-status-modal" role="dialog" aria-modal="true" aria-labelledby="change-status-modal-title">
    <div class="modal">
        <h3 id="change-status-modal-title">Change Status</h3>
        <p class="small text-secondary" id="change-status-context" style="margin:8px 0 0"></p>
        <div class="form-group" style="margin-top:16px;display:flex;flex-direction:column;gap:8px" id="change-status-targets"></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* ---------- View toggle ---------- */
    .view-toggle { display: inline-flex; border: 1px solid var(--color-border); border-radius: var(--radius-sm); overflow: hidden; margin-top: var(--space-4); }
    .view-toggle a {
        display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
        font-size: 14px; font-weight: 600; color: var(--color-text-secondary);
        border-right: 1px solid var(--color-border);
    }
    .view-toggle a:last-child { border-right: 0; }
    .view-toggle a svg { width: 16px; height: 16px; }
    .view-toggle a:hover { background: var(--color-bg); color: var(--color-text); }
    .view-toggle a.active { background: var(--color-onprocess-bg); color: var(--color-brand); }

    /* ---------- Kanban board ---------- */
    .kanban-board {
        display: grid; grid-auto-flow: column; grid-auto-columns: minmax(255px, 1fr);
        gap: var(--space-4); margin-top: var(--space-5); overflow-x: auto;
        padding-bottom: var(--space-2); align-items: start;
    }
    .kanban-col {
        background: var(--color-bg); border: 1px solid var(--color-border);
        border-radius: var(--radius-md); display: flex; flex-direction: column;
        max-height: 72vh; min-height: 180px; transition: box-shadow .15s ease, border-color .15s ease, background .15s ease;
    }
    .kanban-col-head {
        position: sticky; top: 0; z-index: 1;
        display: flex; align-items: center; gap: var(--space-2);
        padding: var(--space-3); border-bottom: 1px solid var(--color-border);
        background: var(--color-surface); border-radius: var(--radius-md) var(--radius-md) 0 0;
        font-size: 12px; font-weight: 700; letter-spacing: .05em; color: var(--color-text);
    }
    .kanban-col-head .col-icon { width: 14px; height: 14px; }
    .kanban-col-head .col-icon.badge-waiting { color: var(--color-waiting); }
    .kanban-col-head .col-icon.badge-on-process { color: var(--color-onprocess); }
    .kanban-col-head .col-icon.badge-on-check { color: var(--color-oncheck); }
    .kanban-col-head .col-icon.badge-done { color: var(--color-done); }
    .kanban-col-head .col-count {
        margin-left: auto; min-width: 22px; height: 20px; padding: 0 6px; border-radius: var(--radius-pill);
        background: var(--color-bg); border: 1px solid var(--color-border);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 12px; color: var(--color-text-secondary);
    }
    .kanban-col-body {
        padding: var(--space-3); display: flex; flex-direction: column; gap: var(--space-3);
        overflow-y: auto; flex: 1; min-height: 96px;
    }
    .kanban-empty { text-align: center; padding: var(--space-5) var(--space-3); color: var(--color-text-muted); font-size: 13px; }
    .kanban-empty p { margin: 0; }
    .kanban-empty .hint { margin-top: 4px; font-size: 12px; color: var(--color-text-secondary); }
    .kanban-load-more {
        margin: 0 var(--space-3) var(--space-3); height: 34px; border-radius: var(--radius-sm);
        border: 1px dashed var(--color-border); background: var(--color-surface);
        font-size: 13px; font-weight: 600; color: var(--color-text-secondary); cursor: pointer;
    }
    .kanban-load-more:hover { background: var(--color-bg); color: var(--color-text); }
    .kanban-load-more[disabled] { opacity: .6; cursor: wait; }

    /* Drop affordances (DragDrop UI §17.6/§17.7) */
    .kanban-col.drop-valid { border-color: var(--color-focus); box-shadow: 0 0 0 3px rgba(132,169,232,.18); }
    .kanban-col.drop-valid .kanban-col-body { background: rgba(132,169,232,.08); }
    .kanban-col.drop-over { border-color: var(--color-brand); box-shadow: 0 0 0 3px rgba(31,75,153,.18); background: var(--color-onprocess-bg); }
    .kanban-col.drop-invalid { opacity: .55; }

    /* ---------- Task card ---------- */
    .task-card {
        background: var(--color-surface); border: 1px solid var(--color-border);
        border-radius: var(--radius-md); padding: var(--space-3);
        box-shadow: var(--shadow-card); display: flex; flex-direction: column; gap: var(--space-2);
        position: relative; transition: box-shadow .15s ease, transform .15s ease, opacity .15s ease;
    }
    .task-card[draggable="true"] { cursor: grab; }
    .task-card:hover { border-color: #D4D8DE; }
    .task-card .task-card-head { display: flex; align-items: center; gap: var(--space-2); }
    .task-card .task-card-head .dropdown { margin-left: auto; }
    .card-menu-btn {
        width: 26px; height: 26px; border: 0; background: none; border-radius: var(--radius-sm);
        display: grid; place-items: center; cursor: pointer; color: var(--color-text-muted);
    }
    .card-menu-btn:hover { background: var(--color-bg); color: var(--color-text); }
    .card-menu-btn svg { width: 16px; height: 16px; }
    .card-menu { min-width: 170px; }
    .task-card-title { font-size: 14px; font-weight: 600; color: var(--color-text); line-height: 1.35; }
    .task-card-title:hover { color: var(--color-brand); }
    .task-card-desc { margin: 0; font-size: 13px; color: var(--color-text-secondary); }
    .task-card-people { display: flex; flex-wrap: wrap; gap: var(--space-3); color: var(--color-text-secondary); }
    .task-card-people .person { display: inline-flex; align-items: center; gap: 4px; min-width: 0; }
    .task-card-people .person svg { width: 13px; height: 13px; flex-shrink: 0; }
    .task-card-foot { display: flex; align-items: center; justify-content: space-between; gap: var(--space-2); flex-wrap: wrap; }
    .task-card-foot .due { display: inline-flex; align-items: center; gap: 4px; }
    .task-card-foot .due svg { width: 12px; height: 12px; }
    .task-card-foot .due.is-overdue { color: var(--color-overdue); font-weight: 600; }
    .task-card-foot .due.is-due-soon { color: var(--color-waiting); font-weight: 600; }

    .task-card.dragging {
        opacity: .4; transform: rotate(1deg) scale(.98);
        box-shadow: 0 8px 24px rgba(23,26,31,.16); border-style: dashed;
    }
    .task-card.is-static { cursor: default; }

    .task-card.transitioning { pointer-events: none; }
    .task-card.transitioning::after {
        content: "Updating status...";
        position: absolute; inset: 0; border-radius: var(--radius-md);
        background: rgba(255,255,255,.86); display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 600; color: var(--color-brand);
        animation: transition-pulse 1s ease-in-out infinite;
    }
    @keyframes transition-pulse { 0%,100% { opacity: .85; } 50% { opacity: 1; } }

    .kanban-skeleton {
        border-radius: var(--radius-md); background: linear-gradient(90deg, #EDEFF3 25%, #F6F7F9 37%, #EDEFF3 63%);
        background-size: 400% 100%; animation: skeleton-shimmer 1.2s ease-in-out infinite;
        height: 118px; flex-shrink: 0;
    }
    @keyframes skeleton-shimmer { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }

    #change-status-targets .btn { justify-content: flex-start; }
</style>
@endpush

@if($view === 'board')
@push('scripts')
<script>
(function () {
    'use strict';

    var STATUS_LABEL = { WAITING: 'WAITING', ON_PROCESS: 'ON PROCESS', ON_CHECK: 'ON CHECK', DONE: 'DONE' };
    var STATUS_CLASS = { WAITING: 'badge-waiting', ON_PROCESS: 'badge-on-process', ON_CHECK: 'badge-on-check', DONE: 'badge-done' };
    var STATUS_ICON = { WAITING: 'clock', ON_PROCESS: 'loader-circle', ON_CHECK: 'eye', DONE: 'check-circle-2' };

    var board = document.querySelector('[data-kanban-board]');
    if (!board) return;

    var canDropInto = JSON.parse(board.getAttribute('data-can-drop') || '[]');
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content;
    var dragState = null;   // { id, from, targets }
    var pending = null;     // { id, from, to }
    var loadedPages = {};   // status -> jumlah halaman yang sedang dirender di kolom tsb
    var refreshToken = {};  // status -> token untuk men-drop refresh yang sudah usang

    board.querySelectorAll('[data-kanban-column]').forEach(function (col) {
        loadedPages[col.dataset.kanbanColumn] = 1;
    });

    /* ---------- Toast ---------- */
    function tmToast(message, type) {
        var stack = document.querySelector('.toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            document.body.appendChild(stack);
        }
        var el = document.createElement('div');
        el.className = 'toast ' + (type || 'success');
        var span = document.createElement('span');
        span.textContent = message;
        var close = document.createElement('button');
        close.className = 'toast-close';
        close.setAttribute('aria-label', 'Close');
        close.innerHTML = '&times;';
        close.addEventListener('click', function () { el.remove(); });
        el.appendChild(span);
        el.appendChild(close);
        stack.appendChild(el);
        setTimeout(function () { el.remove(); }, type === 'error' ? 8000 : 4000);
    }

    function cardOf(id) { return document.getElementById('task-card-' + id); }

    /* ---------- Column counters & tabs ---------- */
    function bumpCount(status, delta) {
        var el = document.querySelector('[data-col-count="' + status + '"]');
        if (el) el.textContent = Math.max(0, parseInt(el.textContent, 10) + delta);

        var tab = document.querySelector('[data-tab-status="' + status + '"] .count');
        if (tab) tab.textContent = '(' + Math.max(0, parseInt(tab.textContent, 10) + delta) + ')';
    }

    function emptyColumnHtml(status) {
        var hint = canDropInto.indexOf(status) !== -1 ? '<p class="hint">Drop a valid task here</p>' : '';
        return '<div class="kanban-empty" data-col-empty><p>No tasks</p>' + hint + '</div>';
    }

    /* ---------- Card movement ---------- */
    function moveCard(card, from, to) {
        var targetBody = document.querySelector('[data-col-body="' + to + '"]');
        var sourceBody = document.querySelector('[data-col-body="' + from + '"]');

        if (targetBody) {
            var placeholder = targetBody.querySelector('[data-col-empty]');
            if (placeholder) placeholder.remove();
            targetBody.appendChild(card);
        }

        if (sourceBody && !sourceBody.querySelector('.task-card')) {
            sourceBody.insertAdjacentHTML('beforeend', emptyColumnHtml(from));
        }

        card.dataset.status = to;
        bumpCount(from, -1);
        bumpCount(to, 1);

        var badge = card.querySelector('.task-card-status .status-badge');
        if (badge) {
            badge.className = 'status-badge ' + STATUS_CLASS[to];
            badge.innerHTML = '<i data-lucide="' + STATUS_ICON[to] + '" class="badge-icon"></i> ' + STATUS_LABEL[to];
        }

        if (window.lucide) lucide.createIcons();
    }

    /* ---------- Auto refresh: sinkronisasi kolom terdampak dengan server setelah drop ---------- */
    function columnUrl(status, page) {
        var params = new URLSearchParams(window.location.search);
        params.set('page', page);
        return '/tasks/board/column/' + status + '?' + params.toString();
    }

    function applyColumnRefresh(status, results) {
        var col = board.querySelector('[data-kanban-column="' + status + '"]');
        var body = col && col.querySelector('[data-col-body]');
        var last = results[results.length - 1];
        if (!body || !last) return;

        body.innerHTML = results.map(function (r) { return r.html; }).join('');

        var countEl = col.querySelector('[data-col-count]');
        if (countEl) countEl.textContent = String(last.total);

        var btn = col.querySelector('[data-load-more]');
        if (last.has_more) {
            if (!btn) {
                btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'kanban-load-more';
                btn.setAttribute('data-load-more', '');
                col.appendChild(btn);
            }
            btn.setAttribute('data-page', String(results.length + 1));
            btn.disabled = false;
            btn.textContent = 'Load more';
        } else if (btn) {
            btn.remove();
        }
    }

    function recomputeCanDrop() {
        var union = [];
        board.querySelectorAll('.task-card').forEach(function (card) {
            JSON.parse(card.dataset.validTargets || '[]').forEach(function (t) {
                if (union.indexOf(t) === -1) union.push(t);
            });
        });
        canDropInto = union;
        board.setAttribute('data-can-drop', JSON.stringify(union));
    }

    function refreshColumns(statuses) {
        var seen = [];
        var jobs = [];

        statuses.forEach(function (status) {
            if (seen.indexOf(status) !== -1) return;
            seen.push(status);
            if (!board.querySelector('[data-kanban-column="' + status + '"]')) return;

            var token = (refreshToken[status] || 0) + 1;
            refreshToken[status] = token;

            var pages = loadedPages[status] || 1;
            var pageJobs = [];
            for (var p = 1; p <= pages; p++) {
                pageJobs.push(fetch(columnUrl(status, p), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (res) { return res.json(); }));
            }

            jobs.push(Promise.all(pageJobs).then(function (results) {
                if (refreshToken[status] !== token) return; // refresh lebih baru sudah berjalan
                applyColumnRefresh(status, results);
            }));
        });

        Promise.all(jobs).then(function () {
            recomputeCanDrop();
            if (window.lucide) lucide.createIcons();
        }).catch(function () {
            tmToast("Couldn't refresh the board. Reload the page to see the latest data.", 'error');
        });
    }

    /* ---------- Transition request (shared by drag, menu, fallback) ---------- */
    function runTransition(id, from, to, comment) {
        var card = cardOf(id);
        if (!card || card.classList.contains('transitioning')) return;

        card.classList.add('transitioning');
        card.setAttribute('draggable', 'false');
        card.setAttribute('aria-busy', 'true');

        fetch('/tasks/' + id + '/status', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ status: to, comment: comment || null, source: 'kanban_drag' })
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                return { ok: res.ok, data: data };
            });
        }).then(function (result) {
            card.classList.remove('transitioning');
            card.removeAttribute('aria-busy');
            card.setAttribute('draggable', 'true');

            if (!result.ok) {
                // Rollback: the card never left its column (DragDrop PRD §8.2 rule 10)
                tmToast(
                    (result.data.message || "Couldn't update task status.") +
                    ' The task was returned to ' + STATUS_LABEL[from] + '.',
                    'error'
                );
                return;
            }

            if (!result.data.changed) {
                tmToast(result.data.message || 'Task is already in this status.', 'info');
                return;
            }

            moveCard(card, from, to);
            tmToast(result.data.message || 'Task status updated.', 'success');

            // Auto refresh: tarik ulang data kolom asal & tujuan agar valid-target,
            // badge, urutan kartu, hitungan, dan Load more selalu sinkron dengan server
            refreshColumns([from, to]);
        }).catch(function () {
            card.classList.remove('transitioning');
            card.removeAttribute('aria-busy');
            card.setAttribute('draggable', 'true');
            tmToast("Couldn't update task status. Check your connection — the task was returned to " + STATUS_LABEL[from] + '.', 'error');
        });
    }

    /* ---------- Confirmation dialogs (DragDrop UI §17.8) ---------- */
    function openConfirm(opts) {
        pending = opts;
        var modal = document.getElementById('transition-modal');
        modal.querySelector('[data-role="title"]').textContent = opts.title;
        modal.querySelector('[data-role="body"]').textContent = opts.body;
        var confirmBtn = modal.querySelector('[data-role="confirm"]');
        confirmBtn.textContent = opts.confirmLabel;
        confirmBtn.className = 'btn ' + (opts.tone || 'btn-primary');
        modal.classList.add('show');
        confirmBtn.focus();
    }

    function openRevision(id, from, to) {
        pending = { id: id, from: from, to: to };
        var modal = document.getElementById('revision-modal');
        document.getElementById('revision-reason').value = '';
        document.getElementById('revision-reason-error').hidden = true;
        modal.classList.add('show');
        document.getElementById('revision-reason').focus();
    }

    function requestWithConfirmation(id, from, to) {
        var card = cardOf(id);
        var title = card ? (card.dataset.title || '') : '';

        if (from === 'ON_CHECK' && to === 'DONE') {
            openConfirm({
                id: id, from: from, to: to,
                title: 'Approve Task',
                body: 'Task:\n' + title + '\n\nThis will mark the task as DONE.',
                confirmLabel: 'Approve & Complete',
                tone: 'btn-success'
            });
        } else if (from === 'ON_CHECK' && to === 'ON_PROCESS') {
            openRevision(id, from, to);
        } else if (from === 'DONE' && to === 'ON_PROCESS') {
            openConfirm({
                id: id, from: from, to: to,
                title: 'Reopen Task',
                body: 'Are you sure you want to reopen this task?\n\nReopening will move it back to ON PROCESS.',
                confirmLabel: 'Reopen Task',
                tone: 'btn-primary'
            });
        } else {
            runTransition(id, from, to, null);
        }
    }

    document.querySelector('#transition-modal [data-role="confirm"]').addEventListener('click', function () {
        var opts = pending;
        pending = null;
        document.getElementById('transition-modal').classList.remove('show');
        if (opts) runTransition(opts.id, opts.from, opts.to, opts.comment || null);
    });

    document.getElementById('revision-submit').addEventListener('click', function () {
        var reason = document.getElementById('revision-reason').value.trim();
        if (!reason) {
            document.getElementById('revision-reason-error').hidden = false;
            return;
        }
        var opts = pending;
        pending = null;
        document.getElementById('revision-modal').classList.remove('show');
        if (opts) runTransition(opts.id, opts.from, opts.to, reason);
    });

    /* ---------- Drag & drop (DragDrop PRD §8.2) ---------- */
    function clearDragState() {
        if (dragState) {
            var card = cardOf(dragState.id);
            if (card) card.classList.remove('dragging');
        }
        board.querySelectorAll('.kanban-col').forEach(function (col) {
            col.classList.remove('drop-valid', 'drop-invalid', 'drop-over');
        });
        dragState = null;
    }

    board.addEventListener('dragstart', function (e) {
        var card = e.target.closest('.task-card');
        if (!card || card.getAttribute('draggable') !== 'true') return;
        if (card.classList.contains('transitioning')) { e.preventDefault(); return; }

        dragState = {
            id: card.dataset.taskId,
            from: card.dataset.status,
            targets: JSON.parse(card.dataset.validTargets || '[]')
        };

        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragState.id);

        board.querySelectorAll('[data-kanban-column]').forEach(function (col) {
            if (dragState.targets.indexOf(col.dataset.kanbanColumn) !== -1) {
                col.classList.add('drop-valid');
            } else {
                col.classList.add('drop-invalid');
            }
        });
    });

    board.addEventListener('dragend', clearDragState);

    board.addEventListener('dragover', function (e) {
        if (!dragState) return;
        var col = e.target.closest('[data-kanban-column]');
        if (!col || dragState.targets.indexOf(col.dataset.kanbanColumn) === -1) return; // no affordance
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        col.classList.add('drop-over');
    });

    board.addEventListener('dragleave', function (e) {
        var col = e.target.closest('[data-kanban-column]');
        if (col && !col.contains(e.relatedTarget)) col.classList.remove('drop-over');
    });

    board.addEventListener('drop', function (e) {
        if (!dragState) return;
        var col = e.target.closest('[data-kanban-column]');
        if (!col) return;
        e.preventDefault();

        var state = dragState;
        var to = col.dataset.kanbanColumn;
        clearDragState();

        if (state.targets.indexOf(to) === -1 || to === state.from) {
            // Invalid drop → rejected, card stays where it is (DragDrop PRD §8.2 rule 3)
            tmToast('Invalid move — the task was returned to ' + STATUS_LABEL[state.from] + '.', 'error');
            return;
        }

        requestWithConfirmation(state.id, state.from, to);
    });

    /* ---------- Card menu + Change Status fallback ---------- */
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-menu-toggle]');
        document.querySelectorAll('.card-menu.show').forEach(function (menu) {
            if (!toggle || menu.id !== toggle.dataset.menuToggle) menu.classList.remove('show');
        });
        if (toggle) {
            var menu = document.getElementById(toggle.dataset.menuToggle);
            if (menu) {
                menu.classList.toggle('show');
                toggle.setAttribute('aria-expanded', menu.classList.contains('show') ? 'true' : 'false');
            }
            return;
        }

        var changeBtn = e.target.closest('[data-change-status]');
        if (changeBtn) {
            var card = changeBtn.closest('.task-card');
            if (!card) return;
            document.querySelectorAll('.card-menu.show').forEach(function (m) { m.classList.remove('show'); });

            var from = card.dataset.status;
            var targets = JSON.parse(card.dataset.validTargets || '[]');
            var context = document.getElementById('change-status-context');
            var list = document.getElementById('change-status-targets');
            context.textContent = '#' + card.dataset.taskId + ' ' + card.dataset.title + ' — Current status: ' + STATUS_LABEL[from];
            list.innerHTML = '';

            targets.forEach(function (target) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline';
                btn.innerHTML = '<i data-lucide="arrow-right"></i> Move to ' + STATUS_LABEL[target];
                btn.addEventListener('click', function () {
                    document.getElementById('change-status-modal').classList.remove('show');
                    requestWithConfirmation(card.dataset.taskId, from, target);
                });
                list.appendChild(btn);
            });

            document.getElementById('change-status-modal').classList.add('show');
            if (window.lucide) lucide.createIcons();
        }
    });

    /* ---------- Load more (DragDrop PRD §8.6) ---------- */
    board.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-load-more]');
        if (!btn) return;

        var col = btn.closest('[data-kanban-column]');
        var body = col.querySelector('.kanban-col-body');
        var status = col.dataset.kanbanColumn;
        var page = btn.dataset.page;

        btn.disabled = true;
        btn.textContent = 'Loading…';

        var skeleton = document.createElement('div');
        skeleton.className = 'kanban-skeleton';
        body.appendChild(skeleton);

        fetch(columnUrl(status, page), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); }).then(function (data) {
            skeleton.remove();
            body.insertAdjacentHTML('beforeend', data.html);
            loadedPages[status] = parseInt(page, 10);

            if (!data.has_more) {
                btn.remove();
            } else {
                btn.dataset.page = String(parseInt(page, 10) + 1);
                btn.disabled = false;
                btn.textContent = 'Load more';
            }

            if (window.lucide) lucide.createIcons();
        }).catch(function () {
            skeleton.remove();
            btn.disabled = false;
            btn.textContent = 'Load more';
            tmToast("Couldn't load more tasks. Please try again.", 'error');
        });
    });
})();
</script>
@endpush
@endif
