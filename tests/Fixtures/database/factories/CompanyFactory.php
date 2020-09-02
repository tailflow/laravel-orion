<?php

use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\Company;

$factory->define(Company::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});
