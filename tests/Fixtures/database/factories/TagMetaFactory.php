<?php

use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\TagMeta;

$factory->define(TagMeta::class, function (Faker $faker) {
    return [
        'key' => $faker->words(5, true)
    ];
});
