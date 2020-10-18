<?php

use Carbon\Carbon;
use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\PostImage;

$factory->define(PostImage::class, function (Faker $faker) {
    return [
        'path' => "{$faker->uuid}/{$faker->uuid}.{$faker->fileExtension}"
    ];
});

$factory->state(PostImage::class, 'trashed', function (Faker $faker) {
    return [
        'deleted_at' => Carbon::now()
    ];
});