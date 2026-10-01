<?php

namespace Tests;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function makeUser(string $roleSlug = 'staff', array $overrides = []): User
    {
        static $counter = 0;
        $counter++;

        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => ucfirst($roleSlug)]);

        return User::create(array_merge([
            'name' => ucfirst($roleSlug).' User'.$counter,
            'email' => $roleSlug.'-user'.$counter.'@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeTask(User $actor, array $overrides = []): Task
    {
        $task = Task::create(array_merge([
            'title' => 'Sample Task For Testing',
            'description' => 'Description of the sample task.',
            'created_by' => $actor->id,
            'assignee_id' => $actor->id,
            'checker_id' => null,
            'status' => 'WAITING',
            'priority' => 'MEDIUM',
            'due_date' => now()->addDays(7),
        ], $overrides));

        // Mirror controller behaviour: notify the assignee on assignment
        if ($task->assignee_id) {
            \App\Models\Notification::create([
                'user_id' => $task->assignee_id,
                'task_id' => $task->id,
                'type' => 'TASK_ASSIGNED',
                'title' => 'New Task Assigned',
                'message' => "Task {$task->title} has been assigned to you",
            ]);
        }

        return $task;
    }

    protected function csrfToken(): string
    {
        return csrf_token();
    }
}
