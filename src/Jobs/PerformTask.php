<?php

namespace Minions\Task\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Minions\Client\ResponseInterface;
use Minions\Exceptions\ClientHasError;
use Minions\Exceptions\RequestException;
use Minions\Exceptions\ServerHasError;
use Minions\Minion;
use Minions\Task\Events\TaskCompleted;
use Minions\Task\Events\TaskFailed;
use Minions\Task\Task;
use Minions\Task\TaskCreator;
use Throwable;

class PerformTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The task model.
     *
     * @var \Minions\Task\Task
     */
    public $task;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->task->status = 'running';
        $this->task->save();

        $promise = Minion::broadcast(
            $this->task->project, $this->task->toMessage()
        );

        $promise->then(function (ResponseInterface $response) {
            $this->markTaskCompleted($response);
        })->otherwise(function (ClientHasError $exception) {
            $this->markExceptionHasOccured($exception);
        })->otherwise(function (ServerHasError $exception) {
            $this->markExceptionHasOccured($exception);
        })->otherwise(function (Throwable $exception) {
            $this->markExceptionHasOccured($exception);
        })->done();

        Minion::run();
    }

    /**
     * Mark task compleeted.
     */
    protected function markTaskCompleted(ResponseInterface $response): void
    {
        $this->task->status = 'completed';
        $this->task->exception = null;

        $this->task->save();

        \event(new TaskCompleted($this->task, $response));

        $creator = $this->task->creator()->withTrashed()->first();

        if ($creator instanceof TaskCreator) {
            $creator->onTaskCompleted($this->task, $response->getRpcResult());
        }
    }

    /**
     * Mark exception has occured.
     *
     * @param mixed $exception
     */
    protected function markExceptionHasOccured($exception): void
    {
        $this->task->status = 'failed';

        $this->task->exception = json_encode([
            'class' => \get_class($exception),
            'message' => \optional($exception)->getMessage() ?? null,
            'data' => $exception instanceof RequestException ? $exception->getRpcErrorData() : null,
        ]);

        $this->task->save();

        \event(new TaskFailed($this->task));

        \report($exception);

        $this->delete();
    }
}
