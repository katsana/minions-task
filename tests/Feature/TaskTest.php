<?php

namespace Minions\Task\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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

        $app->make('config')
            ->set([
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

        $task = Task::add($user, 'server-project-id', 'math.add', [1, 2, 3, 4, 5]);

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

        $this->assertInstanceOf(Task::class, $task);
        $this->assertInstanceOf(User::class, $task->creator);
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

    /** @test */
    public function it_has_be_converted_to_message()
    {
        $user = \factory(User::class)->create();

        $task = factory(Task::class)->create([
            'creator_type' => get_class($user),
            'creator_id' => $user->id,
            'project' => 'server-project-id',
            'method' => 'math.add',
            'payload' => [1, 2, 3, 4, 5],
        ]);

        $message = $task->toMessage();

        $this->assertInstanceOf('Minions\Client\Message', $message);
        $this->assertSame('math.add', $message->method());
        $this->assertSame([1, 2, 3, 4, 5], $message->parameters());
        $this->assertEquals($task->id, $message->id());
    }


    /** @test */
    public function it_can_manually_dispatch_a_task()
    {
        Queue::fake();

        $user = \factory(User::class)->create();

        $task = factory(Task::class)->create([
            'creator_type' => get_class($user),
            'creator_id' => $user->id,
            'project' => 'server-project-id',
            'method' => 'math.add',
            'payload' => [1, 2, 3, 4, 5],
        ]);

        $task->dispatch();

        Queue::assertPushed(PerformTask::class, function ($job) use ($user) {
            return $job->task->project === 'server-project-id' && $job->task->method === 'math.add';
        });
    }


    /** @test */
    public function it_can_manually_dispatch_now_a_task()
    {
        Bus::fake();

        $user = \factory(User::class)->create();

        $task = factory(Task::class)->create([
            'creator_type' => get_class($user),
            'creator_id' => $user->id,
            'project' => 'server-project-id',
            'method' => 'math.add',
            'payload' => [1, 2, 3, 4, 5],
        ]);

        $task->dispatchNow();

        Bus::assertDispatched(PerformTask::class, function ($job) use ($user) {
            return $job->task->project === 'server-project-id' && $job->task->method === 'math.add';
        });
    }
}
