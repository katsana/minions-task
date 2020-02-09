<?php

namespace Minions\Task\Tests\Feature\Jobs;

use Mockery as m;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minions\Task\Task;
use Minions\Task\Tests\TestCase;
use Minions\Task\Tests\User;

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
        $eventLoop = m::mock('React\EventLoop\LoopInterface');
        $promise = m::mock('React\Promise\PromiseInterface');
        $this->instance('minions.client', $minion = m::mock('Minions\Client\Minion', [$eventLoop, []]));

        $user = \factory(User::class)->create();

        $task = factory(Task::class)->create([
            'creator_type' => get_class($user),
            'creator_id' => $user->id,
            'project' => 'server-project-id',
            'method' => 'math.add',
            'payload' => [1, 2, 3, 4, 5],
        ]);

        $minion->shouldReceive('broadcast')->once()->with('server-project-id', m::type('Minions\Client\Message'))->andReturn($promise);
        $eventLoop->shouldReceive('run')->andReturnNull();
        $promise->shouldReceive('then')->once()->with(m::type('Closure'))->andReturnSelf()
            ->shouldReceive('otherwise')->times(3)->with(m::type('Closure'))->andReturnSelf();

        $task->dispatchNow();
    }
}
