<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadAttachmentRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function store(UploadAttachmentRequest $request, Task $task)
    {
        $this->authorize('attach', $task);

        $validated = $request->validated();

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug($task->title) . '_' . uniqid() . '.' . $extension;
        $path = $file->storeAs('task-attachments', $filename, 'private');

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'uploaded_by' => $request->user()->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        // Activity log
        $task->activities()->create([
            'user_id' => $request->user()->id,
            'action' => 'upload',
            'description' => "Uploaded attachment: {$attachment->file_name}",
        ]);

        return redirect()->route('tasks.show', $task)
            ->with('success', 'File uploaded successfully.');
    }

    public function destroy(TaskAttachment $attachment)
    {
        $this->authorize('delete', $attachment->task);

        // Remove from storage
        if (Storage::disk('private')->exists($attachment->file_path)) {
            Storage::disk('private')->delete($attachment->file_path);
        }

        $attachment->delete();

        // Activity log
        $attachment->task->activities()->create([
            'user_id' => auth()->user()->id,
            'action' => 'delete-attachment',
            'description' => "Deleted attachment: {$attachment->file_name}",
        ]);

        return back()->with('success', 'Attachment deleted successfully.');
    }

    public function download(TaskAttachment $attachment)
    {
        $this->authorize('view', $attachment->task);

        if (! Storage::disk('private')->exists($attachment->file_path)) {
            abort(404);
        }

        return Storage::disk('private')->download($attachment->file_path, $attachment->file_name);
    }
}