<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeTaskStatusRequest;
use App\Services\TaskStatusService;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    /**
     * Tasks rendered per Kanban column before a "Load more" is offered (DragDrop PRD §8.6).
     */
    private const BOARD_PER_COLUMN = 15;

    private const BOARD_STATUSES = ['WAITING', 'ON_PROCESS', 'ON_CHECK', 'DONE'];

    protected $taskStatusService;

    public function __construct(TaskStatusService $taskStatusService)
    {
        $this->taskStatusService = $taskStatusService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $view = $request->query('view') === 'table' ? 'table' : 'board';

        $baseQuery = Task::query();

        if (! $user->isAdmin() && ! $user->isManager()) {
            $baseQuery->where(function ($q) use ($user) {
                $q->where('assignee_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('checker_id', $user->id);
            });
        }

        // Tab counters (PRD §8 / UI §17.2)
        $tabCounts = (clone $baseQuery)
            ->select('status', \DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        $tabCounts['ALL'] = array_sum($tabCounts);

        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        if ($view === 'table') {
            $query = $this->applyFilters(clone $baseQuery, $request);

            if ($status = $request->query('status')) {
                $query->where('status', $status);
            }

            $tasks = $query->with(['creator', 'assignee', 'checker'])
                ->latest()
                ->paginate(10)
                ->withQueryString();

            return view('tasks.index', compact('tasks', 'tabCounts', 'users', 'view'));
        }

        // Kanban board (DragDrop PRD §8.1) — default task view
        $statusFilter = $request->query('status');
        $statusFilter = in_array($statusFilter, self::BOARD_STATUSES, true) ? $statusFilter : null;
        $columnStatuses = $statusFilter ? [$statusFilter] : self::BOARD_STATUSES;

        $board = [];
        $validTargets = [];
        $boardTotal = 0;

        foreach ($columnStatuses as $status) {
            $query = $this->applyFilters(clone $baseQuery, $request)->where('status', $status);

            $items = $query->with(['creator', 'assignee', 'checker'])
                ->latest()
                ->take(self::BOARD_PER_COLUMN)
                ->get();

            foreach ($items as $task) {
                $validTargets[$task->id] = $this->taskStatusService->allowedTargets($user, $task);
            }

            $total = (clone $query)->count();

            $board[$status] = [
                'tasks' => $items,
                'total' => $total,
                'has_more' => $total > $items->count(),
                'next_page' => 2,
            ];

            $boardTotal += $total;
        }

        // Columns where the user may actually drop something (used for empty-state copy)
        $canDropInto = array_values(array_unique(array_merge(...array_values($validTargets))));

        return view('tasks.index', compact('board', 'tabCounts', 'users', 'view', 'validTargets', 'canDropInto', 'boardTotal', 'statusFilter'));
    }

    /**
     * Load more cards for one Kanban column (DragDrop PRD §8.6).
     */
    public function boardColumn(Request $request, string $status)
    {
        abort_unless(in_array($status, self::BOARD_STATUSES, true), 404);

        $user = $request->user();

        $query = Task::query();

        if (! $user->isAdmin() && ! $user->isManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('assignee_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('checker_id', $user->id);
            });
        }

        $query = $this->applyFilters($query, $request)->where('status', $status);
        $total = (clone $query)->count();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = self::BOARD_PER_COLUMN;

        $items = $query->with(['creator', 'assignee', 'checker'])
            ->latest()
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $validTargets = [];
        foreach ($items as $task) {
            $validTargets[$task->id] = $this->taskStatusService->allowedTargets($user, $task);
        }

        $html = $items->map(fn (Task $task) => view('tasks._card', [
            'task' => $task,
            'validTargets' => $validTargets[$task->id] ?? [],
        ])->render())->implode('');

        $loaded = $page * $perPage;

        return response()->json([
            'html' => $html,
            'page' => $page,
            'total' => $total,
            'has_more' => $loaded < $total,
        ]);
    }

    /**
     * Apply the shared filter set (DragDrop PRD §8.4).
     */
    private function applyFilters($query, Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($assigneeId = $request->query('assignee_id')) {
            $query->where('assignee_id', $assigneeId);
        }

        if ($checkerId = $request->query('checker_id')) {
            $query->where('checker_id', $checkerId);
        }

        if ($dueDate = $request->query('due_date')) {
            $query->whereDate('due_date', $dueDate);
        }

        if ($createdDate = $request->query('created_date')) {
            $query->whereDate('created_at', $createdDate);
        }

        return $query;
    }

    public function create()
    {
        $this->authorize('create', Task::class);

        return view('tasks.create', [
            'users' => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Task::class);

        $validated = $request->validate([
            'title' => 'required|string|min:3|max:255',
            'description' => 'required|string',
            'assignee_id' => 'required|exists:users,id',
            'checker_id' => 'nullable|exists:users,id',
            'priority' => 'required|string|in:LOW,MEDIUM,HIGH',
            'due_date' => 'nullable|date|after_or_equal:today',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpeg,png,zip|max:10240',
        ]);

        $assignee = User::find($validated['assignee_id']);
        if (! $assignee || ! $assignee->is_active) {
            return back()->withErrors(['assignee_id' => 'Assignee must be an active user.'])->withInput();
        }

        if (! empty($validated['checker_id'])) {
            $checker = User::find($validated['checker_id']);
            if (! $checker || ! $checker->is_active) {
                return back()->withErrors(['checker_id' => 'Checker must be an active user.'])->withInput();
            }
        }

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'created_by' => $request->user()->id,
            'assignee_id' => $validated['assignee_id'],
            'checker_id' => $validated['checker_id'] ?? null,
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => 'WAITING',
        ]);

        // Handle attachment upload if provided
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $extension = $file->getClientOriginalExtension();
            $filename = Str::slug($task->title) . '_' . uniqid() . '.' . $extension;
            $path = $file->storeAs('task-attachments', $filename, 'private');

            TaskAttachment::create([
                'task_id' => $task->id,
                'uploaded_by' => $request->user()->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        // Activity log
        $task->activities()->create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'description' => "Task created: {$validated['title']}",
        ]);

        // Notification
        $task->notifications()->create([
            'user_id' => $validated['assignee_id'],
            'task_id' => $task->id,
            'type' => 'TASK_ASSIGNED',
            'title' => 'Task Assigned',
            'message' => "You have been assigned task: {$validated['title']}",
        ]);

        return redirect()->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $task->load(['creator', 'assignee', 'checker', 'attachments.uploader', 'comments.user', 'activities.user']);
        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        $this->authorize('edit', $task);
        return view('tasks.edit', [
            'task' => $task,
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $this->authorize('edit', $task);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|min:3|max:255',
            'description' => 'sometimes|required|string',
            'priority' => 'sometimes|required|string|in:LOW,MEDIUM,HIGH',
            'due_date' => 'sometimes|required|date|after_or_equal:today',
            'assignee_id' => 'sometimes|nullable|exists:users,id',
            'checker_id' => 'sometimes|nullable|exists:users,id',
        ]);

        // Assignment changes are limited to Admin/Manager (PRD §4.2)
        if (! $request->user()->isAdmin() && ! $request->user()->isManager()) {
            unset($validated['assignee_id'], $validated['checker_id']);
        }

        if (isset($validated['assignee_id']) && $validated['assignee_id']) {
            $assignee = User::find($validated['assignee_id']);
            if (! $assignee || ! $assignee->is_active) {
                return back()->withErrors(['assignee_id' => 'Assignee must be an active user.'])->withInput();
            }
        }

        $changes = collect($validated)
            ->filter(fn ($value, $key) => (string) ($task->{$key} ?? '') !== (string) ($value ?? ''))
            ->all();

        $task->update($validated);

        foreach ($changes as $key => $value) {
            $task->activities()->create([
                'user_id' => $request->user()->id,
                'action' => "update-{$key}",
                'description' => ucfirst(str_replace('_', ' ', $key)).' changed by '.$request->user()->name,
            ]);
        }

        if (array_key_exists('assignee_id', $changes) && $task->assignee_id) {
            $task->notifications()->create([
                'user_id' => $task->assignee_id,
                'task_id' => $task->id,
                'type' => 'TASK_ASSIGNED',
                'title' => 'Task Assigned',
                'message' => "You have been assigned task: {$task->title}",
            ]);
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    public function start(ChangeTaskStatusRequest $request, Task $task)
    {
        $this->authorize('start', $task);

        $task = $this->applyStart($task, $request->user());

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task started successfully.');
    }

    public function submitForCheck(ChangeTaskStatusRequest $request, Task $task)
    {
        $this->authorize('submit', $task);

        $task = $this->applySubmit($task, $request->user());

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task submitted for check.');
    }

    public function approve(ChangeTaskStatusRequest $request, Task $task)
    {
        $this->authorize('approve', $task);

        $task = $this->applyApprove($task, $request->user());

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task approved and marked as done.');
    }

    public function requestRevision(ChangeTaskStatusRequest $request, Task $task)
    {
        $this->authorize('request-revision', $task);

        $comment = $request->validated('comment');

        if (empty($comment)) {
            return back()
                ->with('error', 'Revision must include a reason/comment.')
                ->withInput();
        }

        $task = $this->applyRevision($task, $request->user(), $comment);

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Revision requested successfully.');
    }

    /**
     * Reopen a DONE task back to ON_PROCESS (PRD §5.2).
     */
    public function reopen(Request $request, Task $task)
    {
        $this->authorize('reopen', $task);

        if ($task->status !== 'DONE') {
            return back()->with('error', 'Only DONE tasks can be reopened.');
        }

        $task = $this->applyReopen($task, $request->user());

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task reopened and moved back to ON PROCESS.');
    }

    /**
     * Shared status endpoint (PRD §17 Status) used by Kanban drag & drop,
     * the card "Change Status" fallback, and any non-drag client.
     *
     * Drag & drop is only an interaction layer: every drop calls this endpoint,
     * which runs the same TaskStatusService validation, policies, activity log
     * and notifications as the action buttons (DragDrop PRD §73).
     */
    public function changeStatus(ChangeTaskStatusRequest $request, Task $task)
    {
        $user = $request->user();
        $current = $task->status;
        $requested = strtoupper(trim((string) $request->input('status', '')));
        $comment = trim((string) $request->input('comment', ''));
        $source = $request->input('source'); // audit metadata only — never used for authorization

        // Drop onto the same column: no status change (DragDrop PRD §8.2)
        if ($requested === $current) {
            return $this->statusResponse($request, $task, [
                'changed' => false,
                'status' => $current,
                'message' => 'Task is already in this status.',
            ]);
        }

        // Invalid transition → 422 (PRD §18)
        if (! $this->taskStatusService->validateTransition($current, $requested)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid status transition.',
                    'current_status' => $current,
                    'requested_status' => $requested,
                ], 422);
            }

            return back()->with('error', "Invalid status transition: {$current} → {$requested}");
        }

        // Authorization → 403 (PRD §18 / §4.2)
        $ability = TaskStatusService::ACTION_FOR[$current.'>'.$requested] ?? null;

        if ($ability === null) {
            return response()->json([
                'message' => 'Invalid status transition.',
                'current_status' => $current,
                'requested_status' => $requested,
            ], 422);
        }

        $this->authorize($ability, $task);

        // Revision always requires a reason (PRD §5.3.6 / §12)
        if ($current === 'ON_CHECK' && $requested === 'ON_PROCESS' && $comment === '') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Revision must include a reason/comment.',
                    'errors' => ['comment' => ['The comment field is required when requesting a revision.']],
                ], 422);
            }

            return back()->with('error', 'Revision must include a reason/comment.')->withInput();
        }

        $task = match ($current.'>'.$requested) {
            'WAITING>ON_PROCESS' => $this->applyStart($task, $user, $source),
            'ON_PROCESS>ON_CHECK' => $this->applySubmit($task, $user, $source),
            'ON_CHECK>DONE' => $this->applyApprove($task, $user, $source),
            'ON_CHECK>ON_PROCESS' => $this->applyRevision($task, $user, $comment, $source),
            'DONE>ON_PROCESS' => $this->applyReopen($task, $user, $source),
        };

        return $this->statusResponse($request, $task, [
            'changed' => true,
            'status' => $task->status,
            'message' => match ($current.'>'.$requested) {
                'WAITING>ON_PROCESS' => 'Task started successfully.',
                'ON_PROCESS>ON_CHECK' => 'Task submitted for check.',
                'ON_CHECK>DONE' => 'Task approved and marked as done.',
                'ON_CHECK>ON_PROCESS' => 'Revision requested successfully.',
                'DONE>ON_PROCESS' => 'Task reopened and moved back to ON PROCESS.',
            },
        ]);
    }

    private function statusResponse(Request $request, Task $task, array $payload)
    {
        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect()->route('tasks.show', $task)
            ->with($payload['changed'] ? 'success' : 'error', $payload['message']);
    }

    private function applyStart(Task $task, User $user, ?string $source = null): Task
    {
        $task = $this->taskStatusService->transitionTask($task, 'ON_PROCESS');

        $task->activities()->create([
            'user_id' => $user->id,
            'action' => 'start',
            'description' => "Task started: {$task->title}".self::sourceSuffix($source),
        ]);

        return $task;
    }

    private function applySubmit(Task $task, User $user, ?string $source = null): Task
    {
        $task = $this->taskStatusService->transitionTask($task, 'ON_CHECK');

        $task->activities()->create([
            'user_id' => $user->id,
            'action' => 'submit',
            'description' => "Task submitted for check: {$task->title}".self::sourceSuffix($source),
        ]);

        if ($task->checker_id) {
            $task->notifications()->create([
                'user_id' => $task->checker_id,
                'task_id' => $task->id,
                'type' => 'TASK_SUBMITTED',
                'title' => 'Task Submitted for Check',
                'message' => "Task {$task->title} is submitted for checking",
            ]);
        }

        return $task;
    }

    private function applyApprove(Task $task, User $user, ?string $source = null): Task
    {
        $task = $this->taskStatusService->transitionTask($task, 'DONE');

        $task->activities()->create([
            'user_id' => $user->id,
            'action' => 'approve',
            'description' => "Task approved: {$task->title}".self::sourceSuffix($source),
        ]);

        if ($task->assignee_id) {
            $task->notifications()->create([
                'user_id' => $task->assignee_id,
                'task_id' => $task->id,
                'type' => 'TASK_APPROVED',
                'title' => 'Task Approved',
                'message' => "Task {$task->title} has been approved",
            ]);
        }

        return $task;
    }

    private function applyRevision(Task $task, User $user, string $comment, ?string $source = null): Task
    {
        $task = $this->taskStatusService->transitionTask($task, 'ON_PROCESS');

        // Reason is stored as a comment (PRD §12 / §52)
        $task->comments()->create([
            'user_id' => $user->id,
            'comment' => $comment,
        ]);

        $task->activities()->create([
            'user_id' => $user->id,
            'action' => 'request-revision',
            'description' => "Revision requested by {$user->name}: {$comment}".self::sourceSuffix($source),
        ]);

        if ($task->assignee_id) {
            $task->notifications()->create([
                'user_id' => $task->assignee_id,
                'task_id' => $task->id,
                'type' => 'TASK_REVISION',
                'title' => 'Revision Requested',
                'message' => "Revision requested for {$task->title}: {$comment}",
            ]);
        }

        return $task;
    }

    private function applyReopen(Task $task, User $user, ?string $source = null): Task
    {
        $task = $this->taskStatusService->transitionTask($task, 'ON_PROCESS');

        $task->activities()->create([
            'user_id' => $user->id,
            'action' => 'reopen',
            'description' => "{$user->name} reopened task: DONE → ON_PROCESS".self::sourceSuffix($source),
        ]);

        if ($task->assignee_id) {
            $task->notifications()->create([
                'user_id' => $task->assignee_id,
                'task_id' => $task->id,
                'type' => 'TASK_REOPENED',
                'title' => 'Task Reopened',
                'message' => "Task {$task->title} has been reopened and is back in progress",
            ]);
        }

        return $task;
    }

    private static function sourceSuffix(?string $source): string
    {
        if ($source === null || $source === '') {
            return '';
        }

        return ' (via '.str_replace('_', ' ', $source).')';
    }
}