<?php

namespace Minions\Task;

interface TaskCreator
{
    /**
     * Task's polymorphic relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function tasks();
}
