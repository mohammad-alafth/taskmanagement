@extends('layouts.app')

@section('title', 'Create Task')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Create Task</h1>
        <div class="subtitle">Define a new task and assign it to a team member.</div>
    </div>
    <a href="{{ route('tasks.index') }}" class="btn btn-outline">Cancel</a>
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

<form method="POST" action="{{ route('tasks.store') }}" enctype="multipart/form-data" id="task-form">
    @csrf

    <div class="card">
        <div class="form-section-title" style="margin-top:0">Task Information</div>

        <div class="form-group">
            <label class="form-label" for="title">Title <span class="req">*</span></label>
            <input type="text" id="title" name="title" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
                   value="{{ old('title') }}" required minlength="3" maxlength="255" placeholder="e.g. Create Monthly Financial Report">
            @error('title') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description <span class="req">*</span></label>
            <textarea id="description" name="description" class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
                      rows="4" required placeholder="Describe what needs to be done...">{{ old('description') }}</textarea>
            @error('description') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-section-title">Assignment</div>

        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label" for="assignee_id">Assignee <span class="req">*</span></label>
                <select id="assignee_id" name="assignee_id" class="form-control {{ $errors->has('assignee_id') ? 'is-invalid' : '' }}" required>
                    <option value="">Select assignee</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('assignee_id') === (string) $user->id)>
                            {{ $user->name }} ({{ $user->role->name ?? 'No role' }})
                        </option>
                    @endforeach
                </select>
                <div class="form-helper">Only active users are listed.</div>
                @error('assignee_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="checker_id">Checker</label>
                <select id="checker_id" name="checker_id" class="form-control {{ $errors->has('checker_id') ? 'is-invalid' : '' }}">
                    <option value="">Select checker (optional)</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('checker_id') === (string) $user->id)>
                            {{ $user->name }} ({{ $user->role->name ?? 'No role' }})
                        </option>
                    @endforeach
                </select>
                <div class="form-helper">Recommmeded: a user with checker/manager role.</div>
                @error('checker_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-section-title">Task Settings</div>

        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label" for="priority">Priority <span class="req">*</span></label>
                <select id="priority" name="priority" class="form-control" required>
                    @foreach(['LOW', 'MEDIUM', 'HIGH'] as $p)
                        <option value="{{ $p }}" @selected(old('priority', 'MEDIUM') === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" class="form-control {{ $errors->has('due_date') ? 'is-invalid' : '' }}"
                       value="{{ old('due_date') }}" min="{{ date('Y-m-d') }}">
                @error('due_date') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="attachment">Attachment</label>
            <div class="upload-zone" onclick="document.getElementById('attachment').click()">
                <i data-lucide="paperclip"></i>
                <div><strong>Drop files here or click to browse</strong></div>
                <div class="caption">PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, ZIP · max 10 MB per file</div>
            </div>
            <input type="file" id="attachment" name="attachment" class="form-control" style="display:none"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip">
            <div class="form-helper" id="file-label"></div>
            @error('attachment') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:8px">
            <a href="{{ route('tasks.index') }}" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary" id="submit-btn"><i data-lucide="plus"></i> Create Task</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var fileInput = document.getElementById('attachment');
        var fileLabel = document.getElementById('file-label');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                fileLabel.textContent = this.files[0] ? 'Selected: ' + this.files[0].name + ' (' + Math.round(this.files[0].size / 1024) + ' KB)' : '';
            });
        }
        var form = document.getElementById('task-form');
        var btn = document.getElementById('submit-btn');
        form.addEventListener('submit', function () {
            if (form.checkValidity()) {
                btn.disabled = true;
                btn.innerHTML = '⟳ Creating...';
            }
        });
    });
</script>
@endpush
