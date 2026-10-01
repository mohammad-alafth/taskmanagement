<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_page_lists_own_notifications_only(): void
    {
        $admin = $this->makeUser('admin');
        $mine = $this->makeUser('staff');
        $other = $this->makeUser('staff');

        $this->makeTask($admin, ['assignee_id' => $mine->id, 'title' => 'Notify Me Task']);
        $this->makeTask($admin, ['assignee_id' => $other->id, 'title' => 'Not For Me Task']);

        $this->actingAs($mine)->get('/notifications')
            ->assertOk()
            ->assertSee('Notify Me Task')
            ->assertDontSee('Not For Me Task');
    }

    public function test_mark_notification_read(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser('staff');
        $this->makeTask($admin, ['assignee_id' => $user->id]);

        $notification = $user->notifications()->first();
        $this->assertFalse($notification->is_read);

        $response = $this->actingAs($user)->patch("/notifications/{$notification->id}/read");
        $response->assertStatus(302);

        $this->assertTrue($user->fresh()->notifications()->first()->is_read);
        $this->assertNotNull($user->fresh()->notifications()->first()->read_at);
    }

    public function test_mark_all_notifications_read(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser('staff');
        $this->makeTask($admin, ['assignee_id' => $user->id, 'title' => 'Task A']);
        $this->makeTask($admin, ['assignee_id' => $user->id, 'title' => 'Task B']);

        $response = $this->actingAs($user)->patch('/notifications/read-all');
        $response->assertStatus(302);

        $this->assertSame(0, $user->notifications()->where('is_read', false)->count());
    }

    public function test_cannot_mark_other_users_notification(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser('staff');
        $other = $this->makeUser('staff');
        $this->makeTask($admin, ['assignee_id' => $other->id]);

        $notification = $other->notifications()->first();

        $this->actingAs($user)->patch("/notifications/{$notification->id}/read")->assertForbidden();
        $this->assertFalse($notification->fresh()->is_read);
    }

    public function test_notification_filter_unread_shows_empty_state_when_all_read(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser('staff');
        $this->makeTask($admin, ['assignee_id' => $user->id, 'title' => 'Unread One']);

        $this->actingAs($user)->get('/notifications?filter=unread')
            ->assertOk()
            ->assertSee('Unread One');

        $this->actingAs($user)->patch('/notifications/read-all');

        $this->actingAs($user)->get('/notifications?filter=unread')
            ->assertOk()
            ->assertSee("You're all caught up", false);
    }

    public function test_unread_count_badge_shows_in_layout(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser('staff');
        $this->makeTask($admin, ['assignee_id' => $user->id]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('dot-badge');
    }
}
