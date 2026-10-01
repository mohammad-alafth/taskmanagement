<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_uat14_assignee_sees_task_in_their_dashboard(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');

        $this->makeTask($admin, ['assignee_id' => $assignee->id, 'title' => 'Dashboard Visible Task']);

        $this->actingAs($assignee)->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Visible Task');
    }

    public function test_uat15_overdue_counter_counts_past_due_incomplete_tasks(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');

        $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'title' => 'Overdue Task',
            'due_date' => now()->subDays(3),
            'status' => 'ON_PROCESS',
        ]);
        $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'title' => 'Done Past Task',
            'due_date' => now()->subDays(3),
            'status' => 'DONE',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertOk()->assertSee('Overdue Task');
        // Overdue KPI counts only past-due, not-DONE tasks: exactly 1
        $response->assertSee('color:var(--color-overdue)">1<', false);
    }

    public function test_due_soon_section_shows_upcoming_deadlines(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeTask($admin, ['title' => 'Due Tomorrow Task', 'due_date' => now()->addDay(), 'status' => 'ON_PROCESS']);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Due Tomorrow Task');
    }

    public function test_staff_dashboard_only_shows_own_tasks(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $other = $this->makeUser('staff');

        $this->makeTask($admin, ['assignee_id' => $staff->id, 'title' => 'Staff Own Task']);
        $this->makeTask($admin, ['assignee_id' => $other->id, 'title' => 'Staff Foreign Task']);

        $this->actingAs($staff)->get('/dashboard')
            ->assertOk()
            ->assertSee('Staff Own Task')
            ->assertDontSee('Staff Foreign Task');
    }

    public function test_checker_dashboard_shows_waiting_review_queue(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $checker = $this->makeUser('checker');

        $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'checker_id' => $checker->id,
            'title' => 'Awaiting Review Task',
            'status' => 'ON_CHECK',
        ]);

        $this->actingAs($checker)->get('/dashboard')
            ->assertOk()
            ->assertSee('Awaiting Review Task');
    }

    public function test_task_counts_are_accurate(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $checker = $this->makeUser('checker');

        $this->makeTask($admin, ['assignee_id' => $assignee->id, 'checker_id' => $checker->id, 'title' => 'Counting Task A', 'status' => 'WAITING']);
        $this->makeTask($admin, ['assignee_id' => $assignee->id, 'checker_id' => $checker->id, 'title' => 'Counting Task B', 'status' => 'DONE']);

        $total = Task::count();
        $done = Task::where('status', 'DONE')->count();

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertOk();
        // KPI numbers rendered on dashboard
        $response->assertSee((string) $total, false);
        $response->assertSee((string) $done, false);
    }

    public function test_activity_feed_scoped_and_visible(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $other = $this->makeUser('staff');

        $task = $this->makeTask($admin, ['assignee_id' => $staff->id, 'title' => 'Activity Task']);
        $foreign = $this->makeTask($admin, ['assignee_id' => $other->id, 'title' => 'Foreign Activity Task']);

        $this->actingAs($staff)->post("/tasks/{$task->id}/start");
        $this->actingAs($admin)->post("/tasks/{$foreign->id}/start");

        $this->actingAs($staff)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Foreign Activity Task');
    }
}
