<?php

use Faker\Generator as Faker;
use Orion\Tests\Fixtures\App\Models\ModelWithoutRelations;

$factory->define(ModelWithoutRelations::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
    ];
});
