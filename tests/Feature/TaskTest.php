<?php

namespace Minions\Task\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Minions\Task\Jobs\PerformTask;
use Minions\Task\Task;
use Minions\Task\Tests\TestCase;
use Minions\Task\Tests\User;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepare the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->make('config');

        $config->set([
            'minions.id' => 'server-project-id',
            'minions.projects' => [
                'client-project-id' => [
                    'token' => 'secret-token',
                    'signature' => 'secret-signature',
                ],
                'server-project-id' => [
                    'endpoint' => 'http://127.0.0.1:8000/rpc',
                    'token' => 'secret-token',
                    'signature' => 'secret-signature',
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_create_a_new_task()
    {
        Queue::fake();

        $user = \factory(User::class)->create();

        Task::add($user, 'server-project-id', 'math.add', [1, 2, 3, 4, 5]);

        Queue::assertPushed(PerformTask::class, function ($job) use ($user) {
            return $job->task->project === 'server-project-id' && $job->task->method === 'math.add';
        });

        $this->assertDatabaseHas('tasks', [
            'project' => 'server-project-id',
            'method' => 'math.add',
            'payload' => '[1,2,3,4,5]',
            'status' => 'created',
            'creator_id' => $user->id,
            'creator_type' => \get_class($user),
        ]);
    }

    /** @test */
    public function it_has_correct_pending_task()
    {
        $task = factory(Task::class)->make([
            'status' => 'created',
        ]);

        $this->assertTrue($task->isPending());
        $this->assertFalse($task->isCompleted());

        $task = factory(Task::class)->make([
            'status' => 'running',
        ]);

        $this->assertTrue($task->isPending());
        $this->assertFalse($task->isCompleted());
    }

    /** @test */
    public function it_has_correct_completed_task()
    {
        $task = factory(Task::class)->make([
            'status' => 'completed',
        ]);

        $this->assertFalse($task->isPending());
        $this->assertTrue($task->isCompleted());

        $task = factory(Task::class)->make([
            'status' => 'cancelled',
        ]);

        $this->assertFalse($task->isPending());
        $this->assertTrue($task->isCompleted());
    }
}
