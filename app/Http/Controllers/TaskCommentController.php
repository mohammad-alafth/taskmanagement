<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskCommentRequest;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskCommentController extends Controller
{
    public function store(StoreTaskCommentRequest $request, Task $task)
    {
        $this->authorize('comment', $task);

        $validated = $request->validated();

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
        ]);

        // Activity log
        $task->activities()->create([
            'user_id' => $request->user()->id,
            'action' => 'comment',
            'description' => "Comment added by {$request->user()->name}: {$validated['comment']}",
        ]);

        // Notification to assignee and checker
        $notifyUsers = [];
        if ($task->assignee_id && $task->assignee_id != $request->user()->id) {
            $notifyUsers[] = $task->assignee_id;
        }
        if ($task->checker_id && $task->checker_id != $request->user()->id) {
            $notifyUsers[] = $task->checker_id;
        }

        foreach (array_unique($notifyUsers) as $userId) {
            $task->notifications()->create([
                'user_id' => $userId,
                'task_id' => $task->id,
                'type' => 'COMMENT',
                'title' => 'New Comment',
                'message' => "New comment on task {$task->title} by {$request->user()->name}",
            ]);
        }

        return back()->with('success', 'Comment added successfully.');
    }

    public function destroy(TaskComment $comment)
    {
        $this->authorize('delete', $comment->task);

        $comment->delete();

        // Activity log
        $comment->task->activities()->create([
            'user_id' => auth()->user()->id,
            'action' => 'delete-comment',
            'description' => "Deleted comment",
        ]);

        return back()->with('success', 'Comment deleted successfully.');
    }
}