<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Faker\Generator as Faker;
use Minions\Task\Task;

$factory->define(Task::class, function (Faker $faker) {
    return [
        'exception' => null,
        'status' => 'created',
    ];
});
