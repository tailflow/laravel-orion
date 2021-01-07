<?php

use Carbon\Carbon;
use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\AccessKey;

$factory->define(AccessKey::class, function (Faker $faker) {
    return [
        'key' => \Illuminate\Support\Str::random(),
        'name' => $faker->words(3, true)
    ];
});

$factory->state(AccessKey::class, 'trashed', function (Faker $faker) {
    return [
        'deleted_at' => Carbon::now()
    ];
});
