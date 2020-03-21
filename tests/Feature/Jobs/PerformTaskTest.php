<?php

namespace Minions\Task\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Minions\Exceptions\ClientHasError;
use Minions\Exceptions\ServerHasError;
use Minions\Task\Events\TaskCompleted;
use Minions\Task\Events\TaskFailed;
use Minions\Task\Task;
use Minions\Task\Tests\TestCase;
use Minions\Task\Tests\User;
use Mockery as m;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use Throwable;

class PerformTaskTest extends TestCase
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
    public function it_can_handle_the_job()
    {
        Event::fake();

        $eventLoop = $this->app->make(LoopInterface::class);
        $this->instance('minions.client', $minion = m::mock('Minions\Client\Minion', [$eventLoop, []]));
        $originalResponse = m::mock('Minions\Client\ResponseInterface');

        $promise = new Promise(function ($resolve, $reject) use ($originalResponse) {
            return $resolve($originalResponse);
        });

        $user = \factory(User::class)->create();

        $task = \factory(Task::class)->create([
            'creator_type' => get_class($user),
            'creator_id' => $user->id,
            'project' => 'server-project-id',
            'method' => 'math.add',
            'payload' => [1, 2, 3, 4, 5],
        ]);

        $minion->shouldReceive('broadcast')->once()->with('server-project-id', m::type('Minions\Client\Message'))->andReturn($promise);
        $task->dispatchNow();

        $task->refresh();

        $this->assertSame('completed', $task->status);
        $this->assertNull($task->exception);

        Event::assertDispatched(TaskCompleted::class);
        Event::assertNotDispatched(TaskFailed::class);
    }

    /** @test */
    public function it_can_handle_the_job_when_received_client_has_error()
    {
        Event::fake();

        $eventLoop = $this->app->make(LoopInterface::class);
        $this->instance('minions.client', $minion = m::mock('Minions\Client\Minion', [$eventLoop, []]));
        $originalResponse = m::mock('Minions\Client\ResponseInterface');

        $promise = new Promise(function ($resolve, $reject) use ($originalResponse) {
            return $reject(new ClientHasError('Client has error', -32600, $originalResponse, 'math.add'));
        });

        $user = \factory(User::class)->create();

        $task = \factory(Task::class)->create([
            'creator_type' => get_class($user),
            'creator_id' => $user->id,
            'project' => 'server-project-id',
            'method' => 'math.add',
            'payload' => [1, 2, 3, 4, 5],
        ]);

        $minion->shouldReceive('broadcast')->once()->with('server-project-id', m::type('Minions\Client\Message'))->andReturn($promise);
        $originalResponse->shouldReceive('getRpcErrorData')->once()->andReturn('Client has error [-32600]');
        $task->dispatchNow();

        $task->refresh();

        $this->assertSame('failed', $task->status);
        $this->assertSame(
            '{"class":"Minions\\\\Exceptions\\\\ClientHasError","message":"Client has error","data":"Client has error [-32600]"}', $task->exception
        );

        Event::assertNotDispatched(TaskCompleted::class);
        Event::assertDispatched(TaskFailed::class);
    }

    /** @test */
    public function it_can_handle_the_job_when_received_server_has_error()
    {
        Event::fake();

        $eventLoop = $this->app->make(LoopInterface::class);
        $this->instance('minions.client', $minion = m::mock('Minions\Client\Minion', [$eventLoop, []]));
        $originalResponse = m::mock('Minions\Client\ResponseInterface');

        $promise = new Promise(function ($resolve, $reject) use ($originalResponse) {
            return $reject(new ServerHasError('Missing Signature.', -32651, $originalResponse, 'math.add'));
        });

        $user = \factory(User::class)->create();

        $task = \factory(Task::class)->create([
            'creator_type' => get_class($user),
            'creator_id' => $user->id,
            'project' => 'server-project-id',
            'method' => 'math.add',
            'payload' => [1, 2, 3, 4, 5],
        ]);

        $minion->shouldReceive('broadcast')->once()->with('server-project-id', m::type('Minions\Client\Message'))->andReturn($promise);
        $originalResponse->shouldReceive('getRpcErrorData')->once()->andReturn('Missing Signature [-32651]');
        $task->dispatchNow();

        $task->refresh();

        $this->assertSame('failed', $task->status);
        $this->assertSame(
            '{"class":"Minions\\\\Exceptions\\\\ServerHasError","message":"Missing Signature.","data":"Missing Signature [-32651]"}', $task->exception
        );

        Event::assertNotDispatched(TaskCompleted::class);
        Event::assertDispatched(TaskFailed::class);
    }

    /** @test */
    public function it_can_handle_the_job_when_received_throwable()
    {
        Event::fake();

        $eventLoop = $this->app->make(LoopInterface::class);
        $this->instance('minions.client', $minion = m::mock('Minions\Client\Minion', [$eventLoop, []]));
        $originalResponse = m::mock('Minions\Client\ResponseInterface');

        $promise = new Promise(function ($resolve, $reject) use ($originalResponse) {
            return $reject(new Throwable());
        });

        $user = \factory(User::class)->create();

        $task = \factory(Task::class)->create([
            'creator_type' => get_class($user),
            'creator_id' => $user->id,
            'project' => 'server-project-id',
            'method' => 'math.add',
            'payload' => [1, 2, 3, 4, 5],
        ]);

        $minion->shouldReceive('broadcast')->once()->with('server-project-id', m::type('Minions\Client\Message'))->andReturn($promise);
        $task->dispatchNow();

        $task->refresh();

        $this->assertSame('failed', $task->status);
        $this->assertSame(
            '{"class":"Error","message":"Cannot instantiate interface Throwable","data":null}', $task->exception
        );

        Event::assertNotDispatched(TaskCompleted::class);
        Event::assertDispatched(TaskFailed::class);
    }
}
