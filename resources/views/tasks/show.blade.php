@extends('layouts.app')

@section('title', 'Task #'.$task->id)

@php
    $user = auth()->user();
    $steps = ['WAITING' => 'WAITING', 'ON_PROCESS' => 'ON PROCESS', 'ON_CHECK' => 'ON CHECK', 'DONE' => 'DONE'];
    $order = ['WAITING', 'ON_PROCESS', 'ON_CHECK', 'DONE'];
    $currentIndex = array_search($task->status, $order, true);
    $isOverdue = $task->due_date && $task->due_date->isPast() && $task->status !== 'DONE';
    $dueInDays = $task->due_date ? now()->startOfDay()->diffInDays($task->due_date->startOfDay(), false) : null;
    $dueSoon = $dueInDays !== null && $dueInDays >= 0 && $dueInDays <= 1 && $task->status !== 'DONE';
    $initials = fn ($name) => strtoupper(substr(str_replace(['.', '-'], ' ', $name ?? ''), 0, 1));
    $recentRevision = $task->activities->firstWhere('action', 'request-revision');
@endphp

@section('content')
<a href="{{ route('tasks.index') }}" class="btn btn-outline btn-sm" style="margin-bottom:16px">
    <i data-lucide="arrow-left"></i> Back to Tasks
</a>

{{-- Hero (UI §23) --}}
<div class="card" style="margin-bottom:24px">
    <div class="page-header" style="margin-bottom:0">
        <div>
            <div class="caption" style="letter-spacing:.06em;font-weight:600">TASK #{{ $task->id }}</div>
            <h1 class="page-title" style="font-size:24px;margin-top:4px">{{ $task->title }}</h1>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;align-items:center">
                <x-priority-badge :priority="$task->priority" />
                <x-status-badge :status="$task->status" />
                @if($isOverdue)
                    <span class="status-badge badge-overdue"><i data-lucide="alert-triangle" class="badge-icon"></i> OVERDUE</span>
                @elseif($dueSoon)
                    <span class="status-badge badge-waiting"><i data-lucide="clock" class="badge-icon"></i> DUE SOON</span>
                @endif
            </div>
            <div class="small text-secondary" style="margin-top:12px;display:flex;gap:20px;flex-wrap:wrap">
                <span><i data-lucide="user" style="width:14px;height:14px;vertical-align:-2px"></i> Assignee: <strong>{{ $task->assignee->name ?? 'Unassigned' }}</strong></span>
                <span><i data-lucide="search-check" style="width:14px;height:14px;vertical-align:-2px"></i> Checker: <strong>{{ $task->checker->name ?? '—' }}</strong></span>
                <span><i data-lucide="calendar" style="width:14px;height:14px;vertical-align:-2px"></i> Due: <strong @if($isOverdue) style="color:var(--color-overdue)" @endif>{{ $task->due_date?->format('d M Y') ?? 'No due date' }}</strong>
                    @if($isOverdue)
                        <span class="caption" style="color:var(--color-overdue)">({{ now()->startOfDay()->diffInDays($task->due_date->startOfDay(), false) }} days overdue)</span>
                    @endif
                </span>
            </div>
        </div>

        {{-- Contextual actions (UI §26) --}}
        <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end">
            @if($task->status === 'WAITING')
                @can('edit', $task)
                    <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-outline"><i data-lucide="pencil"></i> Edit</a>
                @endcan
                @can('start', $task)
                    <form action="{{ route('tasks.start', $task->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary"><i data-lucide="play"></i> Start Task</button>
                    </form>
                @endcan
            @elseif($task->status === 'ON_PROCESS')
                @can('attach', $task)
                    <button class="btn btn-outline" data-modal-open="#upload-modal"><i data-lucide="upload"></i> Upload</button>
                @endcan
                @can('submit', $task)
                    <form action="{{ route('tasks.submit-for-check', $task->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary"><i data-lucide="send"></i> Submit for Check</button>
                    </form>
                @endcan
                @unless($user->isAdmin() || $user->isManager() || $task->assignee_id === $user->id)
                    <span class="btn is-disabled" title="Only the assignee can submit this task.">Submit for Check</span>
                @endunless
            @elseif($task->status === 'ON_CHECK')
                @can('approve', $task)
                    <button class="btn btn-outline btn-warning" data-modal-open="#revision-modal"><i data-lucide="rotate-ccw"></i> Request Revision</button>
                    <button class="btn btn-success" data-modal-open="#approve-modal"><i data-lucide="check-circle"></i> Approve</button>
                @else
                    <span class="btn is-disabled" title="Waiting for the checker to review this task.">Waiting for review</span>
                @endcan
            @elseif($task->status === 'DONE')
                @can('reopen', $task)
                    <button class="btn btn-outline" data-modal-open="#reopen-modal"><i data-lucide="rotate-ccw"></i> Reopen</button>
                @else
                    <span class="btn is-disabled"><i data-lucide="check-circle"></i> Task completed</span>
                @endcan
            @endif
        </div>
    </div>

    {{-- Workflow stepper (UI §24) --}}
    <div class="workflow" role="list" aria-label="Task workflow">
        @foreach($steps as $key => $label)
            @php
                $idx = array_search($key, $order, true);
                $state = $idx < $currentIndex ? 'done' : ($idx === $currentIndex ? 'current' : '');
            @endphp
            <div class="step {{ $state }}" role="listitem">
                <span class="step-dot">
                    @if($state === 'done') <i data-lucide="check" style="width:13px;height:13px"></i>
                    @elseif($state === 'current') <i data-lucide="circle" style="width:8px;height:8px;fill:currentColor"></i>
                    @endif
                </span>
                <span class="step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>
    @if($recentRevision && $recentRevision->created_at->gt(now()->subDay()) && $task->status === 'ON_PROCESS')
        <div class="revision-note"><i data-lucide="rotate-ccw" style="width:14px;height:14px"></i>
            Revision requested by {{ $recentRevision->user->name ?? 'a reviewer' }} — task returned to ON PROCESS.
        </div>
    @endif
</div>

<div class="detail-grid">
    <div>
        {{-- Description --}}
        <div class="card">
            <h2 class="card-title" style="margin-bottom:12px">Description</h2>
            <div class="text-secondary" style="white-space:pre-wrap">{{ $task->description }}</div>
        </div>

        {{-- Attachments (UI §28) --}}
        <div class="card">
            <div class="card-header-row">
                <h2 class="card-title"><i data-lucide="paperclip" style="width:16px;height:16px;vertical-align:-2px"></i> Attachments</h2>
                @can('attach', $task)
                    <button class="btn btn-sm btn-outline" data-modal-open="#upload-modal"><i data-lucide="upload"></i> Upload</button>
                @endcan
            </div>

            @if($task->attachments->isEmpty())
                <div class="empty-state" style="padding:24px">
                    <i data-lucide="file-x" class="empty-icon"></i>
                    <h4>No attachments</h4>
                    <p>Upload supporting documents for this task.</p>
                </div>
            @else
                @foreach($task->attachments as $attachment)
                    <div class="attachment-item">
                        <span class="file-icon"><i data-lucide="file-text" style="width:18px;height:18px"></i></span>
                        <div class="file-meta">
                            <div class="file-name">{{ $attachment->file_name }}</div>
                            <div class="caption">
                                {{ strtoupper(pathinfo($attachment->file_name, PATHINFO_EXTENSION)) }}
                                · {{ number_format($attachment->file_size / 1024, 1) }} KB
                                · uploaded by {{ $attachment->uploader->name ?? 'Unknown' }}
                                · {{ $attachment->created_at->format('d M Y') }}
                            </div>
                        </div>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('attachments.download', $attachment->id) }}" class="btn btn-sm btn-outline"><i data-lucide="download"></i> Download</a>
                            @can('delete', $task)
                                <form action="{{ route('attachments.destroy', $attachment->id) }}" method="POST"
                                      onsubmit="return confirm('Delete this attachment?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" aria-label="Delete attachment"><i data-lucide="trash-2"></i></button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Comments (UI §30) --}}
        <div class="card">
            <h2 class="card-title" style="margin-bottom:8px"><i data-lucide="message-circle" style="width:16px;height:16px;vertical-align:-2px"></i> Comments</h2>

            @if($task->comments->isEmpty())
                <div class="empty-state" style="padding:24px">
                    <h4>No comments yet</h4>
                    <p>Start the discussion about this task.</p>
                </div>
            @else
                @foreach($task->comments as $comment)
                    <div class="comment-item">
                        <span class="comment-avatar">{{ $initials($comment->user->name) }}</span>
                        <div style="flex:1">
                            <div style="display:flex;justify-content:space-between;gap:8px">
                                <strong>{{ $comment->user->name }}</strong>
                                <span class="caption">{{ $comment->created_at->format('d M Y · H:i') }}</span>
                            </div>
                            <div class="text-secondary" style="margin-top:4px">{{ $comment->comment }}</div>
                        </div>
                        @can('delete', $comment->task)
                            <form action="{{ route('comments.destroy', $comment->id) }}" method="POST" onsubmit="return confirm('Delete this comment?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="icon-btn" aria-label="Delete comment"><i data-lucide="trash-2"></i></button>
                            </form>
                        @endcan
                    </div>
                @endforeach
            @endif

            <form action="{{ route('comments.store', $task->id) }}" method="POST" style="margin-top:16px">
                @csrf
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label" for="comment">Write a comment</label>
                    <textarea id="comment" name="comment" class="form-control {{ $errors->has('comment') ? 'is-invalid' : '' }}" rows="2"
                              placeholder="Write a comment..." required>{{ old('comment') }}</textarea>
                    @error('comment') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div style="display:flex;justify-content:flex-end">
                    <button type="submit" class="btn btn-primary btn-sm">Comment</button>
                </div>
            </form>
        </div>

        {{-- Activity timeline (UI §31) --}}
        <div class="card">
            <h2 class="card-title" style="margin-bottom:16px"><i data-lucide="history" style="width:16px;height:16px;vertical-align:-2px"></i> Activity</h2>
            @if($task->activities->isEmpty())
                <div class="empty-state" style="padding:24px">
                    <h4>No activity yet</h4>
                    <p>Status changes, uploads and comments will appear here.</p>
                </div>
            @else
                <ul class="timeline">
                    @foreach($task->activities->sortByDesc('id') as $activity)
                        <li>
                            <div class="t-time">{{ $activity->created_at->format('d M Y · H:i') }}</div>
                            <div class="t-body">
                                <strong>{{ $activity->user->name ?? 'System' }}</strong> — {{ $activity->description }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Sidebar: task information (UI §25) --}}
    <div class="card">
        <h2 class="card-title" style="margin-bottom:12px">Task Information</h2>
        <div class="info-row"><span class="k">Creator</span><span class="v">{{ $task->creator->name ?? '—' }}</span></div>
        <div class="info-row"><span class="k">Assignee</span><span class="v">{{ $task->assignee->name ?? 'Unassigned' }}</span></div>
        <div class="info-row"><span class="k">Checker</span><span class="v">{{ $task->checker->name ?? '—' }}</span></div>
        <div class="info-row"><span class="k">Priority</span><span class="v"><x-priority-badge :priority="$task->priority" /></span></div>
        <div class="info-row"><span class="k">Status</span><span class="v"><x-status-badge :status="$task->status" /></span></div>
        <div class="info-row"><span class="k">Due Date</span><span class="v" @if($isOverdue) style="color:var(--color-overdue)" @endif>{{ $task->due_date?->format('d M Y') ?? '—' }}</span></div>
        <div class="info-row"><span class="k">Created</span><span class="v">{{ $task->created_at->format('d M Y') }}</span></div>
        <div class="info-row"><span class="k">Updated</span><span class="v">{{ $task->updated_at->format('d M Y H:i') }}</span></div>
        @if($task->completed_at)
            <div class="info-row"><span class="k">Completed</span><span class="v" style="color:var(--color-done)">{{ $task->completed_at->format('d M Y H:i') }}</span></div>
        @endif

        @can('delete', $task)
            <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" style="margin-top:16px"
                  onsubmit="return confirm('Delete Task?\n\nAre you sure you want to delete \"{{ $task->title }}\"?\n\nThis action cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-block btn-sm"><i data-lucide="trash-2"></i> Delete Task</button>
            </form>
        @endcan
    </div>
</div>

{{-- Upload modal --}}
<div class="modal-overlay" id="upload-modal" role="dialog" aria-modal="true" aria-labelledby="upload-modal-title">
    <div class="modal">
        <h3 id="upload-modal-title">Upload Attachment</h3>
        <p class="text-secondary small">Add a supporting document to this task.</p>
        <form action="{{ route('attachments.store', $task->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label" for="file">File <span class="req">*</span></label>
                <input type="file" id="file" name="file" class="form-control" required
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip">
                <div class="form-helper">PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, ZIP · max 10 MB</div>
                @error('file') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="upload"></i> Upload</button>
            </div>
        </form>
    </div>
</div>

{{-- Approve modal (UI §53) --}}
<div class="modal-overlay" id="approve-modal" role="dialog" aria-modal="true" aria-labelledby="approve-modal-title">
    <div class="modal">
        <h3 id="approve-modal-title">Approve Task?</h3>
        <p class="text-secondary">You're about to mark this task as <strong>DONE</strong>.</p>
        <div class="alert alert-info">Task: {{ $task->title }}</div>
        <form action="{{ route('tasks.approve', $task->id) }}" method="POST">
            @csrf
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-success"><i data-lucide="check-circle"></i> Approve Task</button>
            </div>
        </form>
    </div>
</div>

{{-- Request revision modal (UI §37 / §52) --}}
<div class="modal-overlay" id="revision-modal" role="dialog" aria-modal="true" aria-labelledby="revision-modal-title">
    <div class="modal">
        <h3 id="revision-modal-title">Request Revision</h3>
        <p class="text-secondary small">Task: <strong>{{ $task->title }}</strong></p>
        <form action="{{ route('tasks.request-revision', $task->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="revision-reason">Reason <span class="req">*</span></label>
                <textarea id="revision-reason" name="comment" class="form-control {{ $errors->has('comment') ? 'is-invalid' : '' }}" rows="3"
                          placeholder="Please revise page 3 and update totals." required>{{ old('comment') }}</textarea>
                <div class="form-helper">This task will return to ON PROCESS.</div>
                @error('comment') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-warning">Request Revision</button>
            </div>
        </form>
    </div>
</div>

{{-- Reopen modal (UI §54) --}}
<div class="modal-overlay" id="reopen-modal" role="dialog" aria-modal="true" aria-labelledby="reopen-modal-title">
    <div class="modal">
        <h3 id="reopen-modal-title">Reopen Task?</h3>
        <p class="text-secondary">This task is currently <strong>DONE</strong>. Reopening will move it back to <strong>ON PROCESS</strong>.</p>
        <form action="{{ route('tasks.reopen', $task->id) }}" method="POST">
            @csrf
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Reopen Task</button>
            </div>
        </form>
    </div>
</div>
@endsection
