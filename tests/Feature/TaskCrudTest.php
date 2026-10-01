<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    use RefreshDatabase;

    private function validTaskPayload($assigneeId, $checkerId = null): array
    {
        return [
            'title' => 'Create Monthly Financial Report',
            'description' => 'Prepare the monthly financial report for September.',
            'assignee_id' => $assigneeId,
            'checker_id' => $checkerId,
            'priority' => 'HIGH',
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ];
    }

    public function test_uat03_create_task_defaults_to_waiting_and_stores_data(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $checker = $this->makeUser('checker');

        $response = $this->actingAs($admin)->post('/tasks', $this->validTaskPayload($assignee->id, $checker->id));

        $response->assertRedirect(route('tasks.index'));

        $task = Task::first();
        $this->assertNotNull($task);
        $this->assertSame('WAITING', $task->status);
        $this->assertSame($assignee->id, $task->assignee_id);
        $this->assertSame($checker->id, $task->checker_id);
        $this->assertSame($admin->id, $task->created_by);
        $this->assertSame('HIGH', $task->priority);
        $this->assertNotNull($task->id);
    }

    public function test_create_task_logs_activity_and_notifies_assignee(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');

        $this->actingAs($admin)->post('/tasks', $this->validTaskPayload($assignee->id));

        $this->assertSame(1, TaskActivity::where('action', 'create')->count());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $assignee->id,
            'type' => 'TASK_ASSIGNED',
        ]);
    }

    public function test_create_task_validation_rejects_short_title_and_keeps_input(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');

        $response = $this->from('/tasks/create')->actingAs($admin)->post('/tasks', array_merge(
            $this->validTaskPayload($assignee->id),
            ['title' => 'ab']
        ));

        $response->assertSessionHasErrors('title');
        $response->assertSessionHasInput('description');
        $this->assertSame(0, Task::count());
    }

    public function test_create_task_rejects_inactive_assignee(): void
    {
        $admin = $this->makeUser('admin');
        $inactive = $this->makeUser('staff', ['is_active' => false]);

        $response = $this->actingAs($admin)->post('/tasks', $this->validTaskPayload($inactive->id));

        $response->assertSessionHasErrors('assignee_id');
        $this->assertSame(0, Task::count());
    }

    public function test_create_task_rejects_invalid_priority_and_past_due_date(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');

        $response = $this->actingAs($admin)->post('/tasks', array_merge(
            $this->validTaskPayload($assignee->id),
            ['priority' => 'URGENT', 'due_date' => now()->subDay()->format('Y-m-d')]
        ));

        $response->assertSessionHasErrors(['priority', 'due_date']);
    }

    public function test_staff_cannot_create_task(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->get('/tasks/create')->assertForbidden();
        $this->actingAs($staff)->post('/tasks', $this->validTaskPayload($staff->id))->assertForbidden();
    }

    public function test_edit_task_updates_and_records_activity(): void
    {
        $admin = $this->makeUser('admin');
        $task = $this->makeTask($admin);

        $response = $this->actingAs($admin)->put("/tasks/{$task->id}", [
            'title' => 'Updated Task Title',
            'description' => $task->description,
            'priority' => 'LOW',
            'due_date' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('tasks.show', $task->id));
        $this->assertSame('Updated Task Title', $task->fresh()->title);
        $this->assertSame('LOW', $task->fresh()->priority);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'action' => 'update-priority']);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'action' => 'update-title']);
    }

    public function test_admin_can_reassign_but_staff_cannot(): void
    {
        $admin = $this->makeUser('admin');
        $staffA = $this->makeUser('staff');
        $staffB = $this->makeUser('staff');
        $task = $this->makeTask($admin, ['assignee_id' => $staffA->id]);

        $this->actingAs($admin)->put("/tasks/{$task->id}", [
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'assignee_id' => $staffB->id,
        ]);
        $this->assertSame($staffB->id, $task->fresh()->assignee_id);

        // Staff (assignee) tries to reassign to themselves — field must be ignored
        $this->actingAs($staffA)->put("/tasks/{$task->id}", [
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'assignee_id' => $staffA->id,
        ]);
        $this->assertSame($staffB->id, $task->fresh()->assignee_id);
    }

    public function test_admin_can_delete_task_but_staff_cannot(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $task = $this->makeTask($admin, ['assignee_id' => $staff->id]);

        $this->actingAs($staff)->delete("/tasks/{$task->id}")->assertForbidden();
        $this->assertNotNull($task->fresh());

        $this->actingAs($admin)->delete("/tasks/{$task->id}")->assertRedirect(route('tasks.index'));
        $this->assertNull($task->fresh());
    }

    public function test_task_list_filters_by_status_search_and_priority(): void
    {
        $admin = $this->makeUser('admin');
        $matching = $this->makeTask($admin, ['title' => 'Financial Report Task', 'status' => 'WAITING', 'priority' => 'HIGH']);
        $this->makeTask($admin, ['title' => 'Website Update', 'status' => 'DONE', 'priority' => 'LOW']);

        $this->actingAs($admin)->get('/tasks?status=WAITING')
            ->assertOk()
            ->assertSee('Financial Report Task')
            ->assertDontSee('Website Update');

        $this->actingAs($admin)->get('/tasks?search=Financial')
            ->assertOk()
            ->assertSee('Financial Report Task')
            ->assertDontSee('Website Update');

        $this->actingAs($admin)->get('/tasks?search='.$matching->id)
            ->assertOk()
            ->assertSee('Financial Report Task');

        $this->actingAs($admin)->get('/tasks?priority=HIGH')
            ->assertOk()
            ->assertSee('Financial Report Task')
            ->assertDontSee('Website Update');
    }

    public function test_staff_task_list_only_shows_own_tasks(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $other = $this->makeUser('staff');

        $mine = $this->makeTask($admin, ['assignee_id' => $staff->id, 'title' => 'My Visible Task']);
        $theirs = $this->makeTask($admin, ['assignee_id' => $other->id, 'title' => 'Secret Other Task']);

        $this->actingAs($staff)->get('/tasks')
            ->assertOk()
            ->assertSee('My Visible Task')
            ->assertDontSee('Secret Other Task');
    }

    public function test_task_detail_scopes_access(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $outsider = $this->makeUser('staff');

        $task = $this->makeTask($admin, ['assignee_id' => $staff->id, 'title' => 'Scoped Task']);

        $this->actingAs($staff)->get("/tasks/{$task->id}")->assertOk();
        $this->actingAs($outsider)->get("/tasks/{$task->id}")->assertForbidden();
        $this->actingAs($admin)->get("/tasks/{$task->id}")->assertOk();
    }

    public function test_task_with_attachment_is_created_with_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('private');

        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');

        $response = $this->actingAs($admin)->post('/tasks', array_merge(
            $this->validTaskPayload($assignee->id),
            ['attachment' => UploadedFile::fake()->create('report.pdf', 512, 'application/pdf')]
        ));

        $response->assertRedirect(route('tasks.index'));
        $this->assertSame(1, \App\Models\TaskAttachment::count());
    }
}
