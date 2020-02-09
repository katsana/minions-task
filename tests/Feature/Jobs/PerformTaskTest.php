<?php

namespace Minions\Task\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minions\Client\Response;
use Minions\Task\Task;
use Minions\Task\Tests\TestCase;
use Minions\Task\Tests\User;
use Mockery as m;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

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
        $eventLoop = $this->app->make(LoopInterface::class);
        $this->instance('minions.client', $minion = m::mock('Minions\Client\Minion', [$eventLoop, []]));
        $originalResponse = m::mock('Minions\Client\ResponseInterface');



        $promise = new Promise(function ($resolve, $reject) use ($originalResponse) {
            return $resolve($originalResponse);
        });

        $user = \factory(User::class)->create();

        $task = factory(Task::class)->create([
            'creator_type' => get_class($user),
            'creator_id' => $user->id,
            'project' => 'server-project-id',
            'method' => 'math.add',
            'payload' => [1, 2, 3, 4, 5],
        ]);

        $minion->shouldReceive('broadcast')->once()->with('server-project-id', m::type('Minions\Client\Message'))->andReturn($promise);
        $originalResponse->shouldReceive('getRpcResult')->once()->andReturn(15);
        $task->dispatchNow();

        $task->refresh();

        $this->assertSame('completed', $task->status);
    }
}
