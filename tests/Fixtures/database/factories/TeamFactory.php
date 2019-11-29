<?php

use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\Team;

$factory->define(Team::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});
