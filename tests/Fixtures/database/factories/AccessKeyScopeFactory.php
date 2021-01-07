<?php

use Carbon\Carbon;
use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\AccessKeyScope;

$factory->define(AccessKeyScope::class, function (Faker $faker) {
    return [
        'scope' => \Illuminate\Support\Str::slug($faker->words(3, true))
    ];
});

$factory->state(AccessKeyScope::class, 'trashed', function (Faker $faker) {
    return [
        'deleted_at' => Carbon::now()
    ];
});
