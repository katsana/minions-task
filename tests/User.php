<?php

namespace Minions\Task\Tests;

use Minions\Task\Concerns\HasTasks;
use Minions\Task\Task;
use Minions\Task\TaskCreator;

class User extends \Illuminate\Foundation\Auth\User implements TaskCreator
{
    use HasTasks;

    /**
     * Trigger when task is completed.
     */
    public function onTaskCompleted(Task $task, array $response): void
    {
        //
    }
}
