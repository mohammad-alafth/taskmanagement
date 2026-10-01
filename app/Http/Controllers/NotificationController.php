<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = Notification::where('user_id', $request->user()->id)->with('task');

        if ($filter === 'unread') {
            $query->where('is_read', false);
        } elseif ($filter === 'task') {
            $query->whereIn('type', ['TASK_ASSIGNED', 'TASK_SUBMITTED', 'TASK_REVISION', 'TASK_APPROVED', 'TASK_REOPENED']);
        } elseif ($filter === 'review') {
            $query->whereIn('type', ['TASK_SUBMITTED', 'TASK_REVISION']);
        }

        $notifications = $query->latest()->paginate(15)->withQueryString();
        $unreadCount = Notification::where('user_id', $request->user()->id)->where('is_read', false)->count();

        return view('notifications.index', compact('notifications', 'unreadCount', 'filter'));
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
