<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function fullChain(): array
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $checker = $this->makeUser('checker');

        $task = $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'checker_id' => $checker->id,
            'status' => 'WAITING',
        ]);

        return compact('admin', 'assignee', 'checker', 'task');
    }

    public function test_uat04_start_task_moves_waiting_to_on_process(): void
    {
        ['assignee' => $assignee, 'task' => $task] = $this->fullChain();

        $this->actingAs($assignee)->post("/tasks/{$task->id}/start")
            ->assertRedirect(route('tasks.show', $task->id));

        $this->assertSame('ON_PROCESS', $task->fresh()->status);
    }

    public function test_uat05_submit_for_check_moves_on_process_to_on_check(): void
    {
        ['assignee' => $assignee, 'task' => $task] = $this->fullChain();
        $task->update(['status' => 'ON_PROCESS']);

        $this->actingAs($assignee)->post("/tasks/{$task->id}/submit-for-check");

        $this->assertSame('ON_CHECK', $task->fresh()->status);
    }

    public function test_uat06_approve_moves_on_check_to_done_and_sets_completed_at(): void
    {
        ['checker' => $checker, 'task' => $task] = $this->fullChain();
        $task->update(['status' => 'ON_CHECK']);

        $this->actingAs($checker)->post("/tasks/{$task->id}/approve");

        $fresh = $task->fresh();
        $this->assertSame('DONE', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_uat07_request_revision_returns_task_to_on_process_and_requires_reason(): void
    {
        ['checker' => $checker, 'assignee' => $assignee, 'task' => $task] = $this->fullChain();
        $task->update(['status' => 'ON_CHECK', 'completed_at' => now()]);

        // Missing reason must be rejected
        $this->actingAs($checker)->post("/tasks/{$task->id}/request-revision", ['comment' => '']);
        $this->assertSame('ON_CHECK', $task->fresh()->status);

        // With reason
        $this->actingAs($checker)->post("/tasks/{$task->id}/request-revision", [
            'comment' => 'Please revise page 3.',
        ]);

        $fresh = $task->fresh();
        $this->assertSame('ON_PROCESS', $fresh->status);
        $this->assertNull($fresh->completed_at);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $assignee->id,
            'type' => 'TASK_REVISION',
        ]);
        $this->assertSame(1, \App\Models\TaskComment::count());
    }

    public function test_uat08_invalid_transition_is_rejected_and_status_unchanged(): void
    {
        ['checker' => $checker, 'task' => $task] = $this->fullChain(); // WAITING

        // Approve directly from WAITING is not a valid transition
        $this->actingAs($checker)->post("/tasks/{$task->id}/approve");
        $this->assertSame('WAITING', $task->fresh()->status);

        // Submit from WAITING is invalid too
        $this->actingAs($task->assignee)->post("/tasks/{$task->id}/submit-for-check");
        $this->assertSame('WAITING', $task->fresh()->status);

        // No DONE → nothing on a fresh task via reopen
        $this->actingAs($this->makeUser('admin'))->post("/tasks/{$task->id}/reopen");
        $this->assertSame('WAITING', $task->fresh()->status);
    }

    public function test_uat13_every_status_change_records_activity(): void
    {
        ['assignee' => $assignee, 'checker' => $checker, 'task' => $task] = $this->fullChain();

        $this->actingAs($assignee)->post("/tasks/{$task->id}/start");
        $this->actingAs($assignee)->post("/tasks/{$task->id}/submit-for-check");
        $this->actingAs($checker)->post("/tasks/{$task->id}/approve");

        $this->assertSame('start', TaskActivity::where('task_id', $task->id)->where('action', 'start')->value('action'));
        $this->assertNotNull(TaskActivity::where('task_id', $task->id)->where('action', 'submit')->value('id'));
        $this->assertNotNull(TaskActivity::where('task_id', $task->id)->where('action', 'approve')->value('id'));
        $this->assertSame(3, TaskActivity::where('task_id', $task->id)->count());
    }

    public function test_status_transitions_notify_correct_recipients(): void
    {
        ['assignee' => $assignee, 'checker' => $checker, 'task' => $task] = $this->fullChain();
        $task->update(['status' => 'ON_PROCESS']);

        $this->actingAs($assignee)->post("/tasks/{$task->id}/submit-for-check");
        $this->assertDatabaseHas('notifications', ['user_id' => $checker->id, 'type' => 'TASK_SUBMITTED']);

        $this->actingAs($checker)->post("/tasks/{$task->id}/approve");
        $this->assertDatabaseHas('notifications', ['user_id' => $assignee->id, 'type' => 'TASK_APPROVED']);
    }

    public function test_reopen_moves_done_back_to_on_process_with_activity_and_notification(): void
    {
        ['admin' => $admin, 'assignee' => $assignee, 'task' => $task] = $this->fullChain();
        $task->update(['status' => 'DONE', 'completed_at' => now()]);

        $this->actingAs($admin)->post("/tasks/{$task->id}/reopen")
            ->assertRedirect(route('tasks.show', $task->id));

        $fresh = $task->fresh();
        $this->assertSame('ON_PROCESS', $fresh->status);
        $this->assertNull($fresh->completed_at);
        $this->assertNotNull(TaskActivity::where('task_id', $task->id)->where('action', 'reopen')->value('id'));
        $this->assertDatabaseHas('notifications', ['user_id' => $assignee->id, 'type' => 'TASK_REOPENED']);
    }

    public function test_staff_cannot_approve_or_request_revision(): void
    {
        ['assignee' => $assignee, 'task' => $task] = $this->fullChain();
        $task->update(['status' => 'ON_CHECK']);

        $this->actingAs($assignee)->post("/tasks/{$task->id}/approve")->assertForbidden();
        $this->actingAs($assignee)->post("/tasks/{$task->id}/request-revision", ['comment' => 'x'])->assertForbidden();
        $this->assertSame('ON_CHECK', $task->fresh()->status);
    }

    public function test_checker_cannot_submit_for_check(): void
    {
        ['checker' => $checker, 'task' => $task] = $this->fullChain();
        $task->update(['status' => 'ON_PROCESS']);

        $this->actingAs($checker)->post("/tasks/{$task->id}/submit-for-check")->assertForbidden();
        $this->assertSame('ON_PROCESS', $task->fresh()->status);
    }

    public function test_only_admin_manager_can_reopen(): void
    {
        ['assignee' => $assignee, 'checker' => $checker, 'task' => $task] = $this->fullChain();
        $task->update(['status' => 'DONE', 'completed_at' => now()]);

        $this->actingAs($assignee)->post("/tasks/{$task->id}/reopen")->assertForbidden();
        $this->actingAs($checker)->post("/tasks/{$task->id}/reopen")->assertForbidden();
        $this->assertSame('DONE', $task->fresh()->status);
    }

    public function test_full_workflow_happy_path(): void
    {
        ['admin' => $admin, 'assignee' => $assignee, 'checker' => $checker, 'task' => $task] = $this->fullChain();

        $this->actingAs($assignee)->post("/tasks/{$task->id}/start");
        $this->actingAs($assignee)->post("/tasks/{$task->id}/submit-for-check");
        $this->actingAs($checker)->post("/tasks/{$task->id}/request-revision", ['comment' => 'Fix totals.']);
        $this->actingAs($assignee)->post("/tasks/{$task->id}/start");
        $this->actingAs($assignee)->post("/tasks/{$task->id}/submit-for-check");
        $this->actingAs($checker)->post("/tasks/{$task->id}/approve");

        $fresh = $task->fresh();
        $this->assertSame('DONE', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertDatabaseHas('task_comments', ['task_id' => $task->id, 'comment' => 'Fix totals.']);
    }
}
