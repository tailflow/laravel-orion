<?php

use Carbon\Carbon;
use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\Category;

$factory->define(Category::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});


$factory->state(Category::class, 'trashed', function (Faker $faker) {
    return [
        'deleted_at' => Carbon::now()
    ];
});