<?php

use Carbon\Carbon;
use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\Notification;

$factory->define(Notification::class, function (Faker $faker) {
    return [
        'text' => $faker->words(3, true)
    ];
});

$factory->state(Notification::class, 'trashed', function (Faker $faker) {
    return [
        'deleted_at' => Carbon::now()
    ];
});
