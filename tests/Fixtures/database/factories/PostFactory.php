<?php

use Carbon\Carbon;
use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\Post;

$factory->define(Post::class, function (Faker $faker) {
    return [
        'title' => $faker->words(5, true),
        'body' => $faker->text()
    ];
});

$factory->state(Post::class, 'trashed', function (Faker $faker) {
    return [
        'deleted_at' => Carbon::now()
    ];
});
