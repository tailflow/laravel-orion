<?php

use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\Post;

$factory->define(Post::class, function (Faker $faker) {
    return [
        'title' => $faker->words(5, true),
        'body' => $faker->text()
    ];
});
