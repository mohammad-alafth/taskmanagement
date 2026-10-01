<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $scope = function ($query) use ($user) {
            if ($user->isAdmin() || $user->isManager()) {
                return $query;
            }

            return $query->where(function ($q) use ($user) {
                $q->where('assignee_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('checker_id', $user->id);
            });
        };

        $baseQuery = $scope(Task::query());

        $statusCounts = (clone $baseQuery)
            ->select('status', \DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $waiting = $statusCounts['WAITING'] ?? 0;
        $onProcess = $statusCounts['ON_PROCESS'] ?? 0;
        $onCheck = $statusCounts['ON_CHECK'] ?? 0;
        $done = $statusCounts['DONE'] ?? 0;

        $overdue = (clone $baseQuery)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->where('status', '!=', 'DONE')
            ->count();

        $dueSoon = (clone $baseQuery)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now(), now()->addDays(3)])
            ->where('status', '!=', 'DONE')
            ->count();

        $recentTasks = (clone $baseQuery)->with(['assignee', 'checker'])->latest()->take(6)->get();
        $dueSoonTasks = (clone $baseQuery)->with(['assignee'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now(), now()->addDays(3)])
            ->where('status', '!=', 'DONE')
            ->orderBy('due_date')
            ->take(5)
            ->get();

        // Tasks by assignee (Admin/Manager, PRD §7.2)
        $tasksByAssignee = collect();
        if ($user->isAdmin() || $user->isManager()) {
            $tasksByAssignee = Task::query()
                ->with('assignee')
                ->get()
                ->groupBy(fn ($task) => $task->assignee->name ?? 'Unassigned')
                ->map(fn ($group) => $group->count())
                ->sortDesc()
                ->take(6);
        }

        // Checker focus (PRD §7.2)
        $waitingForReview = collect();
        $returnedForRevision = collect();
        $recentlyApproved = collect();
        if ($user->isChecker() || $user->isAdmin() || $user->isManager()) {
            $waitingForReview = Task::where('checker_id', $user->id)->where('status', 'ON_CHECK')
                ->with('assignee')->orderBy('updated_at')->take(5)->get();
            $returnedForRevision = Task::where('assignee_id', $user->id)->where('status', 'ON_PROCESS')
                ->whereHas('activities', fn ($q) => $q->where('action', 'request-revision'))
                ->with('assignee')->latest()->take(5)->get();
            $recentlyApproved = Task::where('checker_id', $user->id)->where('status', 'DONE')
                ->with('assignee')->latest()->take(5)->get();
        }

        // Staff focus: tasks assigned to me first
        $myTasks = Task::where('assignee_id', $user->id)
            ->with(['creator', 'checker'])
            ->orderByRaw("FIELD(status, 'ON_PROCESS', 'ON_CHECK', 'WAITING', 'DONE')")
            ->orderBy('due_date')
            ->take(6)
            ->get();

        $activitiesQuery = TaskActivity::query();
        if (! $user->isAdmin() && ! $user->isManager()) {
            $scopedTaskIds = (clone $baseQuery)->pluck('id');
            $activitiesQuery->whereIn('task_id', $scopedTaskIds);
        }
        $recentActivities = $activitiesQuery
            ->with(['task', 'user'])
            ->latest()
            ->take(8)
            ->get();

        return view('dashboard', compact(
            'user',
            'waiting',
            'onProcess',
            'onCheck',
            'done',
            'overdue',
            'dueSoon',
            'recentTasks',
            'dueSoonTasks',
            'tasksByAssignee',
            'waitingForReview',
            'returnedForRevision',
            'recentlyApproved',
            'myTasks',
            'recentActivities'
        ));
    }
}
