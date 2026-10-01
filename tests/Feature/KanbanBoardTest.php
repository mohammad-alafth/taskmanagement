<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanBoardTest extends TestCase
{
    use RefreshDatabase;

    private function chain(): array
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $checker = $this->makeUser('checker');

        return compact('admin', 'assignee', 'checker');
    }

    /* ------------------------------------------------------------------ */
    /* Board rendering (DragDrop PRD §8.1 / UI §17)                        */
    /* ------------------------------------------------------------------ */

    public function test_tasks_page_defaults_to_kanban_board_view(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $this->makeTask($admin, ['assignee_id' => $assignee->id, 'title' => 'Board Card Task']);

        $this->actingAs($admin)->get('/tasks')
            ->assertOk()
            ->assertSee('data-kanban-board', false)
            ->assertSee('data-kanban-column="WAITING"', false)
            ->assertSee('data-kanban-column="ON_PROCESS"', false)
            ->assertSee('data-kanban-column="ON_CHECK"', false)
            ->assertSee('data-kanban-column="DONE"', false)
            ->assertSee('Board Card Task')
            ->assertSee('view-toggle', false)
            ->assertSee('role="tablist"', false)
            ->assertSee('class="filter-bar"', false);
    }

    public function test_table_view_remains_available_as_secondary_view(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeTask($admin, ['title' => 'Table Row Task']);

        $this->actingAs($admin)->get('/tasks?view=table')
            ->assertOk()
            ->assertDontSee('data-kanban-board', false)
            ->assertSee('<th>Task ID</th>', false)
            ->assertSee('Table Row Task');
    }

    public function test_board_shows_empty_state_when_no_tasks_match(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/tasks?search=zzz-no-such-task')
            ->assertOk()
            ->assertSee('No tasks found');
    }

    public function test_status_tab_hides_other_board_columns(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeTask($admin, ['status' => 'WAITING']);
        $this->makeTask($admin, ['status' => 'DONE']);

        $this->actingAs($admin)->get('/tasks?status=DONE')
            ->assertOk()
            ->assertSee('data-kanban-column="DONE"', false)
            ->assertDontSee('data-kanban-column="WAITING"', false)
            ->assertDontSee('data-kanban-column="ON_PROCESS"', false);
    }

    public function test_board_respects_new_due_date_and_created_date_filters(): void
    {
        $admin = $this->makeUser('admin');
        $dueTomorrow = $this->makeTask($admin, ['title' => 'Due Tomorrow Task', 'due_date' => now()->addDay()]);
        $this->makeTask($admin, ['title' => 'Due Next Month Task', 'due_date' => now()->addMonth()]);

        $this->actingAs($admin)->get('/tasks?due_date='.now()->addDay()->format('Y-m-d'))
            ->assertOk()
            ->assertSee('Due Tomorrow Task')
            ->assertDontSee('Due Next Month Task');

        $this->actingAs($admin)->get('/tasks?created_date='.now()->format('Y-m-d'))
            ->assertOk()
            ->assertSee('Due Tomorrow Task');
    }

    public function test_card_exposes_valid_targets_and_change_status_fallback(): void
    {
        ['admin' => $admin, 'assignee' => $assignee, 'checker' => $checker] = $this->chain();
        $task = $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'checker_id' => $checker->id,
            'status' => 'ON_PROCESS',
            'title' => 'Fallback Task',
        ]);

        // Assignee may submit → valid target ON_CHECK + Change Status menu available
        $this->actingAs($assignee)->get('/tasks')
            ->assertOk()
            ->assertSee('data-valid-targets=\'["ON_CHECK"]\'', false)
            ->assertSee('data-change-status', false)
            ->assertSee('draggable="true"', false);

        // Checker cannot act on an ON_PROCESS task → static card, no Change Status
        $this->actingAs($checker)->get('/tasks')
            ->assertOk()
            ->assertSee('data-valid-targets=\'[]\'', false)
            ->assertSee('task-card is-static', false)
            ->assertSee('draggable="false"', false);
    }

    public function test_empty_column_drop_hint_only_shows_when_user_can_drop(): void
    {
        ['admin' => $admin, 'checker' => $checker] = $this->chain();

        // Admin: has valid transitions into ON_PROCESS → hint allowed
        $this->makeTask($admin, ['status' => 'WAITING']);
        $this->actingAs($admin)->get('/tasks')
            ->assertOk()
            ->assertSee('data-can-drop=\'["ON_PROCESS"]\'', false)
            ->assertSee('Drop a valid task here', false);

        // Checker watching only a WAITING task: no valid transition anywhere → no drop targets
        $task = Task::first();
        $task->update(['checker_id' => $checker->id]);
        $this->actingAs($checker)->get('/tasks')
            ->assertOk()
            ->assertSee('data-can-drop=\'[]\'', false)
            ->assertSee('task-card is-static', false);
    }

    /* ------------------------------------------------------------------ */
    /* Column "Load more" endpoint (DragDrop PRD §8.6)                     */
    /* ------------------------------------------------------------------ */

    public function test_column_endpoint_returns_cards_and_reports_has_more(): void
    {
        $admin = $this->makeUser('admin');
        for ($i = 1; $i <= 16; $i++) {
            $this->makeTask($admin, ['title' => "Load More Task {$i}"]);
        }

        $first = $this->actingAs($admin)->getJson('/tasks/board/column/WAITING?page=1')
            ->assertOk()
            ->assertJson(['page' => 1, 'total' => 16, 'has_more' => true]);

        $this->assertStringContainsString('Load More Task 1', $first->json('html'));
        $this->assertLessThanOrEqual(15, substr_count($first->json('html'), 'data-task-id'));

        $this->actingAs($admin)->getJson('/tasks/board/column/WAITING?page=2')
            ->assertOk()
            ->assertJson(['page' => 2, 'total' => 16, 'has_more' => false])
            ->assertJsonPath('html', fn ($html) => str_contains($html, 'Load More Task 16'));
    }

    public function test_column_endpoint_respects_scope_and_invalid_status(): void
    {
        ['admin' => $admin] = $this->chain();
        $other = $this->makeUser('staff');
        $this->makeTask($other, ['assignee_id' => $other->id, 'title' => 'Private Staff Task']);

        $staff = $this->makeUser('staff');
        $this->actingAs($staff)->getJson('/tasks/board/column/WAITING')
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('html', fn ($html) => ! str_contains($html, 'Private Staff Task'));

        $this->actingAs($admin)->getJson('/tasks/board/column/BOGUS')->assertNotFound();
    }

    /* ------------------------------------------------------------------ */
    /* Shared status endpoint — drag & drop / fallback (PRD §17, §8.2)     */
    /* ------------------------------------------------------------------ */

    public function test_uat17_patch_status_starts_task(): void
    {
        ['admin' => $admin, 'assignee' => $assignee] = $this->chain();
        $task = $this->makeTask($admin, ['assignee_id' => $assignee->id, 'status' => 'WAITING']);

        $this->actingAs($assignee)->patchJson("/tasks/{$task->id}/status", [
            'status' => 'ON_PROCESS',
            'source' => 'kanban_drag',
        ])->assertOk()->assertJson(['changed' => true, 'status' => 'ON_PROCESS']);

        $this->assertSame('ON_PROCESS', $task->fresh()->status);
        $this->assertNotNull(TaskActivity::where('task_id', $task->id)->where('action', 'start')->value('id'));
        $activity = TaskActivity::where('task_id', $task->id)->where('action', 'start')->first();
        $this->assertStringContainsString('via kanban drag', $activity->description);
    }

    public function test_uat18_patch_status_submits_and_notifies_checker(): void
    {
        ['admin' => $admin, 'assignee' => $assignee, 'checker' => $checker] = $this->chain();
        $task = $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'checker_id' => $checker->id,
            'status' => 'ON_PROCESS',
        ]);

        $this->actingAs($assignee)->patchJson("/tasks/{$task->id}/status", [
            'status' => 'ON_CHECK',
            'source' => 'kanban_drag',
        ])->assertOk()->assertJson(['changed' => true, 'status' => 'ON_CHECK']);

        $this->assertSame('ON_CHECK', $task->fresh()->status);
        $this->assertDatabaseHas('notifications', ['user_id' => $checker->id, 'type' => 'TASK_SUBMITTED']);
    }

    public function test_uat19_patch_status_approve_moves_to_done(): void
    {
        ['admin' => $admin, 'assignee' => $assignee, 'checker' => $checker] = $this->chain();
        $task = $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'checker_id' => $checker->id,
            'status' => 'ON_CHECK',
        ]);

        $this->actingAs($checker)->patchJson("/tasks/{$task->id}/status", [
            'status' => 'DONE',
            'source' => 'kanban_drag',
        ])->assertOk()->assertJson(['changed' => true, 'status' => 'DONE']);

        $fresh = $task->fresh();
        $this->assertSame('DONE', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $assignee->id, 'type' => 'TASK_APPROVED']);
    }

    public function test_uat20_patch_status_revision_requires_reason(): void
    {
        ['admin' => $admin, 'assignee' => $assignee, 'checker' => $checker] = $this->chain();
        $task = $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'checker_id' => $checker->id,
            'status' => 'ON_CHECK',
        ]);

        $this->actingAs($checker)->patchJson("/tasks/{$task->id}/status", [
            'status' => 'ON_PROCESS',
            'comment' => '',
            'source' => 'kanban_drag',
        ])->assertStatus(422)->assertJsonValidationErrors('comment');

        $this->assertSame('ON_CHECK', $task->fresh()->status);

        $this->actingAs($checker)->patchJson("/tasks/{$task->id}/status", [
            'status' => 'ON_PROCESS',
            'comment' => 'Please revise page 3.',
            'source' => 'kanban_drag',
        ])->assertOk()->assertJson(['changed' => true, 'status' => 'ON_PROCESS']);

        $fresh = $task->fresh();
        $this->assertSame('ON_PROCESS', $fresh->status);
        $this->assertNull($fresh->completed_at);
        $this->assertDatabaseHas('task_comments', ['task_id' => $task->id, 'comment' => 'Please revise page 3.']);
        $this->assertDatabaseHas('notifications', ['user_id' => $assignee->id, 'type' => 'TASK_REVISION']);
    }

    public function test_uat21_invalid_patch_transition_returns_422_with_status_context(): void
    {
        ['admin' => $admin, 'assignee' => $assignee, 'checker' => $checker] = $this->chain();
        $waiting = $this->makeTask($admin, ['assignee_id' => $assignee->id, 'status' => 'WAITING']);

        $this->actingAs($checker)->patchJson("/tasks/{$waiting->id}/status", ['status' => 'DONE'])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid status transition.',
                'current_status' => 'WAITING',
                'requested_status' => 'DONE',
            ]);

        $this->actingAs($assignee)->patchJson("/tasks/{$waiting->id}/status", ['status' => 'ON_CHECK'])
            ->assertStatus(422)
            ->assertJson(['current_status' => 'WAITING', 'requested_status' => 'ON_CHECK']);

        $this->assertSame('WAITING', $waiting->fresh()->status);
    }

    public function test_on_process_cannot_drop_straight_to_done(): void
    {
        ['admin' => $admin, 'assignee' => $assignee] = $this->chain();
        $task = $this->makeTask($admin, ['assignee_id' => $assignee->id, 'status' => 'ON_PROCESS']);

        $this->actingAs($admin)->patchJson("/tasks/{$task->id}/status", ['status' => 'DONE'])
            ->assertStatus(422)
            ->assertJson(['current_status' => 'ON_PROCESS', 'requested_status' => 'DONE']);

        $this->assertSame('ON_PROCESS', $task->fresh()->status);
    }

    public function test_patch_status_unauthorized_actor_gets_403(): void
    {
        ['admin' => $admin, 'assignee' => $assignee, 'checker' => $checker] = $this->chain();

        $onCheck = $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'checker_id' => $checker->id,
            'status' => 'ON_CHECK',
        ]);

        // Staff assignee may not approve or request revision
        $this->actingAs($assignee)->patchJson("/tasks/{$onCheck->id}/status", ['status' => 'DONE'])->assertForbidden();
        $this->actingAs($assignee)->patchJson("/tasks/{$onCheck->id}/status", [
            'status' => 'ON_PROCESS',
            'comment' => 'nope',
        ])->assertForbidden();
        $this->assertSame('ON_CHECK', $onCheck->fresh()->status);

        // Checker may not submit
        $onProcess = $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'checker_id' => $checker->id,
            'status' => 'ON_PROCESS',
        ]);
        $this->actingAs($checker)->patchJson("/tasks/{$onProcess->id}/status", ['status' => 'ON_CHECK'])->assertForbidden();

        // Reopen restricted to Manager/Admin
        $done = $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'checker_id' => $checker->id,
            'status' => 'DONE',
        ]);
        $this->actingAs($assignee)->patchJson("/tasks/{$done->id}/status", ['status' => 'ON_PROCESS'])->assertForbidden();
        $this->actingAs($checker)->patchJson("/tasks/{$done->id}/status", ['status' => 'ON_PROCESS'])->assertForbidden();
        $this->assertSame('DONE', $done->fresh()->status);
    }

    public function test_source_metadata_cannot_bypass_authorization(): void
    {
        ['admin' => $admin, 'assignee' => $assignee] = $this->chain();
        $task = $this->makeTask($admin, ['assignee_id' => $assignee->id, 'status' => 'ON_CHECK']);

        $this->actingAs($assignee)->patchJson("/tasks/{$task->id}/status", [
            'status' => 'DONE',
            'source' => 'kanban_drag',
        ])->assertForbidden();

        $this->assertSame('ON_CHECK', $task->fresh()->status);
    }

    public function test_patch_status_same_column_is_noop(): void
    {
        ['admin' => $admin, 'assignee' => $assignee] = $this->chain();
        $task = $this->makeTask($admin, ['assignee_id' => $assignee->id, 'status' => 'ON_PROCESS']);

        $this->actingAs($assignee)->patchJson("/tasks/{$task->id}/status", [
            'status' => 'ON_PROCESS',
            'source' => 'kanban_drag',
        ])->assertOk()->assertJson(['changed' => false, 'status' => 'ON_PROCESS']);

        $this->assertSame('ON_PROCESS', $task->fresh()->status);
        $this->assertSame(0, TaskActivity::count()); // makeTask never logs activity; noop must not either
    }

    public function test_patch_status_reopen_from_done(): void
    {
        ['admin' => $admin, 'assignee' => $assignee] = $this->chain();
        $task = $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'status' => 'DONE',
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)->patchJson("/tasks/{$task->id}/status", [
            'status' => 'ON_PROCESS',
            'source' => 'kanban_menu',
        ])->assertOk()->assertJson(['changed' => true, 'status' => 'ON_PROCESS']);

        $fresh = $task->fresh();
        $this->assertSame('ON_PROCESS', $fresh->status);
        $this->assertNull($fresh->completed_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $assignee->id, 'type' => 'TASK_REOPENED']);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'action' => 'reopen']);
    }

    public function test_patch_status_non_json_request_falls_back_to_redirect(): void
    {
        ['admin' => $admin, 'assignee' => $assignee] = $this->chain();
        $task = $this->makeTask($admin, ['assignee_id' => $assignee->id, 'status' => 'WAITING']);

        $this->actingAs($assignee)->patch("/tasks/{$task->id}/status", ['status' => 'ON_PROCESS'])
            ->assertRedirect(route('tasks.show', $task->id));

        $this->assertSame('ON_PROCESS', $task->fresh()->status);
    }

    public function test_every_patch_transition_writes_activity_and_notification(): void
    {
        ['admin' => $admin, 'assignee' => $assignee, 'checker' => $checker] = $this->chain();
        $task = $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'checker_id' => $checker->id,
            'status' => 'WAITING',
        ]);

        $this->actingAs($assignee)->patchJson("/tasks/{$task->id}/status", ['status' => 'ON_PROCESS']);
        $this->actingAs($assignee)->patchJson("/tasks/{$task->id}/status", ['status' => 'ON_CHECK']);
        $this->actingAs($checker)->patchJson("/tasks/{$task->id}/status", [
            'status' => 'ON_PROCESS',
            'comment' => 'Fix totals.',
        ]);
        $this->actingAs($assignee)->patchJson("/tasks/{$task->id}/status", ['status' => 'ON_PROCESS']);
        $this->actingAs($assignee)->patchJson("/tasks/{$task->id}/status", ['status' => 'ON_CHECK']);
        $this->actingAs($checker)->patchJson("/tasks/{$task->id}/status", ['status' => 'DONE']);

        $this->assertSame('DONE', $task->fresh()->status);
        $actions = TaskActivity::where('task_id', $task->id)->orderBy('id')->pluck('action')->all();
        $this->assertSame(['start', 'submit', 'request-revision', 'submit', 'approve'], $actions);

        foreach (['TASK_SUBMITTED', 'TASK_REVISION', 'TASK_APPROVED'] as $type) {
            $this->assertDatabaseHas('notifications', ['task_id' => $task->id, 'type' => $type]);
        }
    }
}
