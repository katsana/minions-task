<?php

namespace Minions\Task\Events;

use Minions\Task\Task;

class TaskFailed
{
    /**
     * Task model instance.
     *
     * @var \Minions\Task\Task
     */
    public $task;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }
}
