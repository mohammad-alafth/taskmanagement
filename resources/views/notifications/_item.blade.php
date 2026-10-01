@php($icon = $typeIcons[$notification->type] ?? 'bell')
<div style="display:flex;gap:12px;align-items:flex-start;padding:14px 4px;border-bottom:1px solid var(--color-border)">
    <span style="width:34px;height:34px;border-radius:50%;background:{{ $notification->is_read ? 'var(--color-bg)' : 'var(--color-onprocess-bg)' }};color:var(--color-brand);display:grid;place-items:center;flex-shrink:0">
        <i data-lucide="{{ $icon }}" style="width:16px;height:16px"></i>
    </span>
    <div style="flex:1;min-width:0">
        <div style="display:flex;gap:8px;align-items:center">
            @unless($notification->is_read)
                <span style="width:8px;height:8px;border-radius:50%;background:var(--color-brand);display:inline-block" title="Unread"></span>
            @endunless
            <strong style="font-size:14px">{{ $notification->title }}</strong>
        </div>
        <div class="small text-secondary">{{ $notification->message }}</div>
        @if($notification->task)
            <div class="caption"><a href="{{ route('tasks.show', $notification->task_id) }}">#{{ $notification->task_id }} {{ $notification->task->title }}</a></div>
        @endif
        <div class="caption" style="margin-top:4px">{{ $notification->created_at->diffForHumans() }}</div>
    </div>
    @unless($notification->is_read)
        <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-sm btn-outline" aria-label="Mark as read">Mark read</button>
        </form>
    @endunless
</div>
