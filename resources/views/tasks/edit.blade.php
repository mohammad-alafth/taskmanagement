@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Task #{{ $task->id }}</h1>
        <div class="subtitle">{{ $task->title }}</div>
    </div>
    <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-outline">Cancel</a>
</div>

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Please fix the following:</strong>
        <ul style="margin:6px 0 0;padding-left:18px">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('tasks.update', $task->id) }}" id="task-form">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="form-section-title" style="margin-top:0">Task Information</div>

        <div class="form-group">
            <label class="form-label" for="title">Title <span class="req">*</span></label>
            <input type="text" id="title" name="title" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
                   value="{{ old('title', $task->title) }}" required minlength="3" maxlength="255">
            @error('title') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description <span class="req">*</span></label>
            <textarea id="description" name="description" class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
                      rows="4" required>{{ old('description', $task->description) }}</textarea>
            @error('description') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-section-title">Assignment</div>

        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label" for="assignee_id">Assignee</label>
                @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                    <select id="assignee_id" name="assignee_id" class="form-control {{ $errors->has('assignee_id') ? 'is-invalid' : '' }}">
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected((string) old('assignee_id', $task->assignee_id) === (string) $u->id)>
                                {{ $u->name }} ({{ $u->role->name ?? 'No role' }})
                            </option>
                        @endforeach
                    </select>
                    @error('assignee_id') <div class="form-error">{{ $message }}</div> @enderror
                @else
                    <select id="assignee_id" class="form-control" disabled aria-disabled="true">
                        <option>{{ $task->assignee->name ?? 'Unassigned' }}</option>
                    </select>
                    <div class="form-helper">Only Admin/Manager can reassign tasks.</div>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label" for="checker_id">Checker</label>
                @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                    <select id="checker_id" name="checker_id" class="form-control {{ $errors->has('checker_id') ? 'is-invalid' : '' }}">
                        <option value="">No checker</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected((string) old('checker_id', $task->checker_id) === (string) $u->id)>
                                {{ $u->name }} ({{ $u->role->name ?? 'No role' }})
                            </option>
                        @endforeach
                    </select>
                    @error('checker_id') <div class="form-error">{{ $message }}</div> @enderror
                @else
                    <select id="checker_id" class="form-control" disabled aria-disabled="true">
                        <option>{{ $task->checker->name ?? '—' }}</option>
                    </select>
                    <div class="form-helper">Only Admin/Manager can change the checker.</div>
                @endif
            </div>
        </div>

        <div class="form-section-title">Task Settings</div>

        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label" for="priority">Priority <span class="req">*</span></label>
                <select id="priority" name="priority" class="form-control" required>
                    @foreach(['LOW', 'MEDIUM', 'HIGH'] as $p)
                        <option value="{{ $p }}" @selected(old('priority', $task->priority) === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" class="form-control {{ $errors->has('due_date') ? 'is-invalid' : '' }}"
                       value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}">
                @error('due_date') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:8px">
            <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary" id="submit-btn">Save Changes</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('task-form');
        var btn = document.getElementById('submit-btn');
        form.addEventListener('submit', function () {
            if (form.checkValidity()) { btn.disabled = true; btn.textContent = '⟳ Saving...'; }
        });
    });
</script>
@endpush
