<?php

namespace Minions\Task\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Minions\Task\TaskServiceProvider;
use Minions\Task\Tests\TestCase;

class TaskServiceProviderTest extends TestCase
{
    /** @test */
    public function it_register_the_configuration()
    {
        $this->assertSame('tasks', \config('minions-task.table'));
    }

    /** @test */
    public function it_can_migrate_the_table()
    {
        $this->artisan('migrate')->run();

        $this->assertTrue(Schema::hasTable('tasks'));

        $this->artisan('migrate:reset')->run();
    }


    /** @test */
    public function it_can_migrate_the_table_using_custom_table_name()
    {
        \config(['minions-task.table' => 'minion_tasks']);

        $this->artisan('migrate')->run();

        $this->assertFalse(Schema::hasTable('tasks'));
        $this->assertTrue(Schema::hasTable('minion_tasks'));

        $this->artisan('migrate:reset')->run();
    }
}
