<?php

use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\History;

$factory->define(History::class, function (Faker $faker) {
    return [
        'code' => $faker->word,
    ];
});
