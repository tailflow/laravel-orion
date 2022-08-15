<?php

use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\Product;

$factory->define(Product::class, function (Faker $faker) {
    return [
        'title' => $faker->words(5, true),
        'description' => $faker->sentences(),
        'total_revenue' => $faker->numberBetween(0, 200)
    ];
});
