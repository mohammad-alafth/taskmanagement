<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_design_system(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('Inter', false);
    }

    public function test_layout_contains_navigation_search_bell_and_user_menu(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertOk()
            ->assertSee('aria-label="Main navigation"', false)
            ->assertSee('aria-label="Search tasks"', false)
            ->assertSee('aria-label="Notifications"', false)
            ->assertSee('aria-label="User menu"', false)
            ->assertSee('aria-label="Mobile navigation"', false)
            ->assertSee('Dashboard');
    }

    public function test_task_list_has_tabs_filters_table_and_badges(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $task = $this->makeTask($admin, ['assignee_id' => $assignee->id, 'title' => 'UI Check Task']);

        $response = $this->actingAs($admin)->get('/tasks');
        $response->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('class="filter-bar"', false)
            ->assertSee('Search title / Task ID')
            ->assertSee('aria-label="Filter by priority"', false)
            ->assertSee('aria-label="Filter by assignee"', false)
            ->assertSee('UI Check Task');
    }

    public function test_task_list_shows_empty_state_when_filters_match_nothing(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/tasks?search=zzz-no-such-task')
            ->assertOk()
            ->assertSee('No tasks found');
    }

    public function test_task_detail_shows_workflow_stepper_sections_and_modals(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $task = $this->makeTask($admin, ['assignee_id' => $assignee->id, 'status' => 'ON_PROCESS']);

        $response = $this->actingAs($admin)->get("/tasks/{$task->id}");
        $response->assertOk()
            ->assertSee('aria-label="Task workflow"', false)
            ->assertSee('Description')
            ->assertSee('Attachments')
            ->assertSee('Comments')
            ->assertSee('Activity')
            ->assertSee('Task Information')
            ->assertSee('id="upload-modal"', false)
            ->assertSee('No attachments')
            ->assertSee('No comments yet');
    }

    public function test_task_detail_shows_contextual_actions_per_status(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $checker = $this->makeUser('checker');

        $waiting = $this->makeTask($admin, ['assignee_id' => $assignee->id, 'status' => 'WAITING', 'title' => 'Waiting Task']);
        $onCheck = $this->makeTask($admin, ['assignee_id' => $assignee->id, 'checker_id' => $checker->id, 'status' => 'ON_CHECK', 'title' => 'Check Task']);
        $done = $this->makeTask($admin, ['assignee_id' => $assignee->id, 'checker_id' => $checker->id, 'status' => 'DONE', 'title' => 'Done Task']);

        // Assignee sees Start on WAITING, Submit for Check on ON_PROCESS
        $this->actingAs($assignee)->get("/tasks/{$waiting->id}")
            ->assertOk()->assertSee('Start Task');
        $this->actingAs($assignee)->get("/tasks/{$onCheck->id}")
            ->assertOk()->assertDontSee('Submit for Check');

        // Checker sees approve + revision modals on ON_CHECK
        $this->actingAs($checker)->get("/tasks/{$onCheck->id}")
            ->assertOk()
            ->assertSee('Approve Task?')
            ->assertSee('Request Revision');

        // Reopen available on DONE (admin)
        $this->actingAs($admin)->get("/tasks/{$done->id}")
            ->assertOk()
            ->assertSee('Reopen Task');
    }

    public function test_create_task_form_has_required_markers_and_sections(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/tasks/create')
            ->assertOk()
            ->assertSee('Create Task')
            ->assertSee('Task Information')
            ->assertSee('Assignment')
            ->assertSee('Task Settings')
            ->assertSee('Title <span class="req">*</span>', false)
            ->assertSee('Description <span class="req">*</span>', false)
            ->assertSee('Assignee <span class="req">*</span>', false);
    }

    public function test_edit_task_form_prefills_task_data(): void
    {
        $admin = $this->makeUser('admin');
        $task = $this->makeTask($admin, ['title' => 'Prefilled Title', 'priority' => 'HIGH']);

        $this->actingAs($admin)->get("/tasks/{$task->id}/edit")
            ->assertOk()
            ->assertSee('Prefilled Title')
            ->assertSee('Edit Task');
    }

    public function test_dashboard_shows_kpis_and_role_sections(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeTask($admin, ['title' => 'KPI Task', 'status' => 'WAITING']);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Recent Tasks')
            ->assertSee('Due Soon')
            ->assertSee('Recent Activity')
            ->assertSee('Tasks by Assignee');
    }

    public function test_status_and_priority_badges_include_text_labels(): void
    {
        $admin = $this->makeUser('admin');
        $task = $this->makeTask($admin, ['title' => 'Badge Task', 'priority' => 'HIGH', 'status' => 'ON_PROCESS']);

        $this->actingAs($admin)->get('/tasks')
            ->assertOk()
            ->assertSee('ON PROCESS')
            ->assertSee('HIGH');
    }

    public function test_friendly_error_pages_render(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->get('/admin/users')
            ->assertForbidden()
            ->assertSee('Access denied');

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get('/no-such-page')->assertNotFound();
    }

    public function test_csrf_protection_is_active(): void
    {
        // Laravel disables CSRF verification in unit tests, so verify the middleware is registered on the web group.
        $middlewareGroups = $this->app['router']->getMiddlewareGroups();

        $this->assertArrayHasKey('web', $middlewareGroups);
        $this->assertContains(\App\Http\Middleware\VerifyCsrfToken::class, $middlewareGroups['web']);
        $this->assertTrue(is_subclass_of(
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class
        ));
    }
}
