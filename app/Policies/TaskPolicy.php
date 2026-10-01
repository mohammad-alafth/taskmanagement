<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin() ||
               $user->isManager() ||
               $task->assignee_id === $user->id ||
               $task->checker_id === $user->id ||
               $task->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function edit(User $user, Task $task): bool
    {
        return $user->isAdmin() ||
               $user->isManager() ||
               $task->assignee_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function start(User $user, Task $task): bool
    {
        return $user->isAdmin() ||
               $user->isManager() ||
               ($task->assignee_id === $user->id && $task->status === 'WAITING');
    }

    public function submit(User $user, Task $task): bool
    {
        return $user->isAdmin() ||
               $user->isManager() ||
               ($task->assignee_id === $user->id && $task->status === 'ON_PROCESS');
    }

    public function approve(User $user, Task $task): bool
    {
        return $user->isAdmin() ||
               $user->isManager() ||
               $task->checker_id === $user->id;
    }

    public function reopen(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function comment(User $user, Task $task): bool
    {
        return $user->isAdmin() ||
               $user->isManager() ||
               $task->assignee_id === $user->id ||
               $task->checker_id === $user->id;
    }

    public function attach(User $user, Task $task): bool
    {
        return $user->isAdmin() ||
               $user->isManager() ||
               $task->assignee_id === $user->id ||
               $task->checker_id === $user->id;
    }

    public function requestRevision(User $user, Task $task): bool
    {
        return $user->isAdmin() ||
               $user->isManager() ||
               $task->checker_id === $user->id;
    }
}