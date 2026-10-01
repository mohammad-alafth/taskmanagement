@php
    $isOverdue = $task->due_date && $task->due_date->isPast() && $task->status !== 'DONE';
    $isDueSoon = ! $isOverdue && $task->due_date && $task->due_date->lte(now()->addDays(3)) && $task->status !== 'DONE';
@endphp
<article class="task-card{{ $validTargets ? '' : ' is-static' }}"
         id="task-card-{{ $task->id }}"
         data-task-id="{{ $task->id }}"
         data-status="{{ $task->status }}"
         data-title="{{ $task->title }}"
         data-valid-targets='@json($validTargets)'
         draggable="{{ $validTargets ? 'true' : 'false' }}"
         aria-label="Task #{{ $task->id }} {{ $task->title }}">
    <div class="task-card-head">
        <span class="caption">#{{ $task->id }}</span>
        <x-priority-badge :priority="$task->priority" />
        <div class="dropdown card-menu-wrap">
            <button type="button" class="card-menu-btn" data-menu-toggle="card-menu-{{ $task->id }}"
                    aria-haspopup="true" aria-expanded="false" aria-label="Task #{{ $task->id }} actions">
                <i data-lucide="more-vertical"></i>
            </button>
            <div class="dropdown-menu card-menu" id="card-menu-{{ $task->id }}">
                <a href="{{ route('tasks.show', $task->id) }}"><i data-lucide="eye" style="width:16px;height:16px"></i> View</a>
                @can('edit', $task)
                    <a href="{{ route('tasks.edit', $task->id) }}"><i data-lucide="pencil" style="width:16px;height:16px"></i> Edit</a>
                @endcan
                @if($validTargets)
                    <button type="button" data-change-status><i data-lucide="arrow-right-circle" style="width:16px;height:16px"></i> Change Status</button>
                @endif
                @can('delete', $task)
                    <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" style="margin:0">
                        @csrf @method('DELETE')
                        <button type="submit" style="color:var(--color-overdue)"
                                onclick="return confirm('Delete Task?\n\nAre you sure you want to delete &quot;{{ $task->title }}&quot;?\n\nThis action cannot be undone.')">
                            <i data-lucide="trash-2" style="width:16px;height:16px"></i> Delete
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </div>

    <a href="{{ route('tasks.show', $task->id) }}" class="task-card-title">{{ $task->title }}</a>
    @if($task->description)
        <p class="task-card-desc">{{ \Illuminate\Support\Str::limit($task->description, 90) }}</p>
    @endif

    <div class="task-card-people small">
        <span class="person" title="Assignee"><i data-lucide="user"></i> {{ $task->assignee->name ?? 'Unassigned' }}</span>
        @if($task->checker)
            <span class="person" title="Checker"><i data-lucide="eye"></i> {{ $task->checker->name }}</span>
        @endif
    </div>

    <div class="task-card-foot">
        <span class="caption due {{ $isOverdue ? 'is-overdue' : ($isDueSoon ? 'is-due-soon' : '') }}">
            <i data-lucide="calendar"></i>
            {{ $isOverdue ? 'Overdue' : ($isDueSoon ? 'Due soon' : 'Due') }} {{ $task->due_date?->format('d M Y') ?? '—' }}
        </span>
        <span class="task-card-status"><x-status-badge :status="$task->status" /></span>
    </div>
</article>
