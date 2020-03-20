<?php

namespace Minions\Task\Events;

use Minions\Client\ResponseInterface;
use Minions\Task\Task;

class TaskCompleted
{
    /**
     * Task model instance.
     *
     * @var \Minions\Task\Task
     */
    public $task;

    /**
     * Response instance.
     *
     * @var \Minions\Client\ResponseInterface
     */
    public $response;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task, ResponseInterface $response)
    {
        $this->task = $task;
        $this->response = $response;
    }
}
