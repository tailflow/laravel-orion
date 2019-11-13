<?php

use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\Tag;

$factory->define(Tag::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
    ];
});
