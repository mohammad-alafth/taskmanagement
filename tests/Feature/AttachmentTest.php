<?php

namespace Tests\Feature;

use App\Models\TaskAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function setupTask(): array
    {
        Storage::fake('private');

        $admin = $this->makeUser('admin');
        $assignee = $this->makeUser('staff');
        $outsider = $this->makeUser('staff');

        $task = $this->makeTask($admin, [
            'assignee_id' => $assignee->id,
            'title' => 'Attachment Test Task',
        ]);

        return compact('admin', 'assignee', 'outsider', 'task');
    }

    public function test_uat09_upload_valid_pdf_succeeds(): void
    {
        ['assignee' => $assignee, 'task' => $task] = $this->setupTask();

        $response = $this->actingAs($assignee)->post("/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('Monthly_Report.pdf', 512, 'application/pdf'),
        ]);

        $response->assertRedirect(route('tasks.show', $task->id));
        $this->assertSame(1, TaskAttachment::count());

        $attachment = TaskAttachment::first();
        $this->assertSame('Monthly_Report.pdf', $attachment->file_name);
        $this->assertNotEmpty($attachment->file_path);
        $this->assertSame($assignee->id, $attachment->uploaded_by);
        $this->assertNotNull($attachment->file_size);

        Storage::disk('private')->assertExists($attachment->file_path);

        // Activity recorded (PRD §13)
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'action' => 'upload']);

        // Task detail page renders the attachment block without error (uploader relation)
        $this->actingAs($assignee)->get("/tasks/{$task->id}")
            ->assertOk()
            ->assertSee('Monthly_Report.pdf')
            ->assertSee('uploaded by');
    }

    public function test_uat10_upload_invalid_file_rejected(): void
    {
        ['assignee' => $assignee, 'task' => $task] = $this->setupTask();

        $response = $this->actingAs($assignee)->post("/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, TaskAttachment::count());
    }

    public function test_upload_rejects_oversized_file(): void
    {
        ['assignee' => $assignee, 'task' => $task] = $this->setupTask();

        $response = $this->actingAs($assignee)->post("/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, TaskAttachment::count());
    }

    public function test_uat11_authorized_user_can_download(): void
    {
        ['admin' => $admin, 'assignee' => $assignee, 'task' => $task] = $this->setupTask();

        $this->actingAs($assignee)->post("/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('report.pdf', 64, 'application/pdf'),
        ]);

        $attachment = TaskAttachment::first();

        $this->actingAs($assignee)->get("/attachments/{$attachment->id}/download")->assertOk();
        $this->actingAs($admin)->get("/attachments/{$attachment->id}/download")->assertOk();
    }

    public function test_uat11_unauthorized_user_cannot_download(): void
    {
        ['assignee' => $assignee, 'task' => $task, 'outsider' => $outsider] = $this->setupTask();

        $this->actingAs($assignee)->post("/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('report.pdf', 64, 'application/pdf'),
        ]);

        $attachment = TaskAttachment::first();

        $this->actingAs($outsider)->get("/attachments/{$attachment->id}/download")->assertForbidden();
    }

    public function test_guest_cannot_download(): void
    {
        ['admin' => $admin, 'task' => $task] = $this->setupTask();

        // Attach a record directly (no authenticated request involved)
        $attachment = \App\Models\TaskAttachment::create([
            'task_id' => $task->id,
            'uploaded_by' => $admin->id,
            'file_name' => 'report.pdf',
            'file_path' => 'task-attachments/report.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $this->get("/attachments/{$attachment->id}/download")->assertRedirect('/login');
    }

    public function test_delete_attachment_permissions_and_storage_cleanup(): void
    {
        ['admin' => $admin, 'assignee' => $assignee, 'outsider' => $outsider, 'task' => $task] = $this->setupTask();

        $this->actingAs($assignee)->post("/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('report.pdf', 64, 'application/pdf'),
        ]);

        $attachment = TaskAttachment::first();
        $path = $attachment->file_path;

        // Outsider cannot delete
        $this->actingAs($outsider)->delete("/attachments/{$attachment->id}")->assertForbidden();
        $this->assertNotNull($attachment->fresh());

        // Admin can delete and file is removed from storage
        $this->from(route('tasks.show', $task->id))
            ->actingAs($admin)->delete("/attachments/{$attachment->id}")
            ->assertRedirect(route('tasks.show', $task->id));
        $this->assertNull($attachment->fresh());
        Storage::disk('private')->assertMissing($path);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'action' => 'delete-attachment']);
    }
}
