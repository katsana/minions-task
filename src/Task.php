<?php

namespace Minions\Task;

use Illuminate\Database\Eloquent\Model;
use Minions\Client\Message;

class Task extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tasks';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['project', 'method', 'payload'];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return \config('minions-task.table');
    }

    /**
     * Get all of the owning creator models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function creator()
    {
        return \tap($this->morphTo(), static function ($morphTo) {
            if (\method_exists($morphTo, 'withTrashed')) {
                $morphTo->withTrashed();
            }
        });
    }

    /**
     * Create task for Eloquent.
     *
     * @return static|null
     */
    public static function add(TaskCreator $creator, string $project, string $method, array $payload)
    {
        return $creator->tasks()->create([
            'project' => $project,
            'method' => $method,
            'payload' => $payload,
            'status' => 'created',
        ])->dispatch();
    }

    /**
     * Dispatch the task.
     *
     * @return $this
     */
    public function dispatch()
    {
        if (! $this->isCompleted()) {
            Jobs\PerformTask::dispatch($this);
        }

        return $this;
    }

    /**
     * Dispatch the task without using queue.
     *
     * @return $this
     */
    public function dispatchNow()
    {
        if (! $this->isCompleted()) {
            Jobs\PerformTask::dispatchNow($this);
        }

        return $this;
    }

    /**
     * Task is currently pending.
     */
    public function isPending(): bool
    {
        return \in_array($this->status, ['created', 'running']);
    }

    /**
     * Task is completed.
     */
    public function isCompleted(): bool
    {
        return \in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * As Minions message.
     */
    public function toMessage(): Message
    {
        return new Message(
            $this->method, $this->payload, $this->id
        );
    }
}
