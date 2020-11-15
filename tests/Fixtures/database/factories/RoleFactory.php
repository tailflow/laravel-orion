<?php

use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\Role;

$factory->define(Role::class, function (Faker $faker) {
    return [
        'name' => $faker->words(3, true)
    ];
});