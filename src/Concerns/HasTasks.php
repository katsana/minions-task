<?php

namespace Minions\Task\Concerns;

use Minions\Task\Task;

trait HasTasks
{
    /**
     * Task's polymorphic relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function tasks()
    {
        return $this->morphMany(Task::class, 'creator');
    }
}
