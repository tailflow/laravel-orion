<?php

use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\Comment;

$factory->define(Comment::class, function (Faker $faker) {
    return [
        'body' => $faker->text(),
    ];
});
