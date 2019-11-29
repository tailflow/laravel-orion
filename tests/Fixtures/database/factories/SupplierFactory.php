<?php

use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\Supplier;

$factory->define(Supplier::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});
