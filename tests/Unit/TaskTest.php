<?php

namespace Minions\Task\Tests\Unit;

use Minions\Task\Task;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    /** @test */
    public function it_has_proper_fillable()
    {
        $task = new Task();

        $this->assertSame(['project', 'method', 'payload'], $task->getFillable());
    }
}
