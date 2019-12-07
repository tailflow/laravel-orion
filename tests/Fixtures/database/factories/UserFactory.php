<?php

use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;
use Orion\Tests\Fixtures\App\Models\User;

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->safeEmail,
        'password' => Hash::make($faker->words(3, true))
    ];
});
