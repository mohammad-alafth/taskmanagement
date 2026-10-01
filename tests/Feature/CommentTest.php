<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_uat12_add_comment_is_stored_and_displayed(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $task = $this->makeTask($admin, ['assignee_id' => $assignee->id]);

        $response = $this->from(route('tasks.show', $task->id))
            ->actingAs($assignee)->post("/tasks/{$task->id}/comments", [
                'comment' => 'I have started working on this.',
            ]);

        $response->assertRedirect(route('tasks.show', $task->id));
        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'user_id' => $assignee->id,
            'comment' => 'I have started working on this.',
        ]);

        $this->actingAs($assignee)->get("/tasks/{$task->id}")
            ->assertOk()
            ->assertSee('I have started working on this.');
    }

    public function test_comment_requires_content(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $task = $this->makeTask($admin, ['assignee_id' => $assignee->id]);

        $response = $this->actingAs($assignee)->post("/tasks/{$task->id}/comments", ['comment' => '']);

        $response->assertSessionHasErrors('comment');
        $this->assertSame(0, \App\Models\TaskComment::count());
    }

    public function test_unrelated_staff_cannot_comment(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $outsider = $this->makeUser('staff');
        $task = $this->makeTask($admin, ['assignee_id' => $staff->id]);

        $this->actingAs($outsider)->post("/tasks/{$task->id}/comments", ['comment' => 'hi'])
            ->assertForbidden();
        $this->assertSame(0, \App\Models\TaskComment::count());
    }

    public function test_admin_can_delete_comment(): void
    {
        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $task = $this->makeTask($admin, ['assignee_id' => $assignee->id]);

        $this->actingAs($assignee)->post("/tasks/{$task->id}/comments", ['comment' => 'temp']);
        $comment = \App\Models\TaskComment::first();

        $this->actingAs($assignee)->delete("/comments/{$comment->id}")->assertForbidden();
        $this->assertNotNull($comment->fresh());

        $this->from(route('tasks.show', $task->id))
            ->actingAs($admin)->delete("/comments/{$comment->id}")
            ->assertRedirect(route('tasks.show', $task->id));
        $this->assertNull($comment->fresh());
    }
}
