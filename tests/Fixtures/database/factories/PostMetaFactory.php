<?php

use Carbon\Carbon;
use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\PostMeta;

$factory->define(PostMeta::class, function (Faker $faker) {
    return [
        'notes' => $faker->text()
    ];
});