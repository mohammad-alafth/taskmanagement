<?php

namespace App\Services;

use App\Exceptions\InvalidTransitionException;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskStatusService
{
    /**
     * Valid transitions per PRD §5.2 / DragDrop §8.2.
     */
    public const TRANSITIONS = [
        'WAITING' => ['ON_PROCESS'],
        'ON_PROCESS' => ['ON_CHECK'],
        'ON_CHECK' => ['DONE', 'ON_PROCESS'],
        'DONE' => ['ON_PROCESS'],
    ];

    /**
     * Policy ability responsible for each transition (PRD §5.2 "Actor").
     * Map key is "{CURRENT}>{TARGET}".
     */
    public const ACTION_FOR = [
        'WAITING>ON_PROCESS' => 'start',
        'ON_PROCESS>ON_CHECK' => 'submit',
        'ON_CHECK>DONE' => 'approve',
        'ON_CHECK>ON_PROCESS' => 'request-revision',
        'DONE>ON_PROCESS' => 'reopen',
    ];

    public function validateTransition(string $currentStatus, string $requestedStatus): bool
    {
        return in_array($requestedStatus, self::TRANSITIONS[$currentStatus] ?? [], true);
    }

    /**
     * Statuses this user may legally drop/move the task into, combining the
     * transition map with policy permissions (DragDrop PRD §8.2 rule 1).
     *
     * @return array<int, string>
     */
    public function allowedTargets(User $user, Task $task): array
    {
        $targets = [];

        foreach (self::TRANSITIONS[$task->status] ?? [] as $target) {
            $ability = self::ACTION_FOR[$task->status.'>'.$target] ?? null;

            if ($ability !== null && $user->can($ability, $task)) {
                $targets[] = $target;
            }
        }

        return $targets;
    }

    /**
     * Atomically transition a task status (PRD §26).
     *
     * @throws InvalidTransitionException
     */
    public function transitionTask(Task $task, string $requestedStatus, ?string $actorRole = null): Task
    {
        $currentStatus = $task->status;

        if (! $this->validateTransition($currentStatus, $requestedStatus)) {
            throw new InvalidTransitionException($currentStatus, $requestedStatus);
        }

        return DB::transaction(function () use ($task, $currentStatus, $requestedStatus) {
            $task->status = $requestedStatus;

            if ($requestedStatus === 'DONE') {
                $task->completed_at = now();
            } elseif ($currentStatus === 'DONE' || $currentStatus === 'ON_CHECK') {
                // Reopen / revision clears completion timestamp (PRD §5.3.10)
                $task->completed_at = null;
            }

            $task->save();

            return $task;
        });
    }
}
