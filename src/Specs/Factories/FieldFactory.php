<?php

namespace Orion\Specs\Factories;

use Orion\Specs\Fields\ArrayField;
use Orion\Specs\Fields\BooleanField;
use Orion\Specs\Fields\EnumField;
use Orion\Specs\Fields\FloatField;
use Orion\Specs\Fields\IntegerField;
use Orion\Specs\Fields\ObjectField;
use Orion\Specs\Fields\StringField;

class FieldFactory
{
    public function string(): StringField
    {
        return new StringField();
    }

    public function integer(): IntegerField
    {
        return new IntegerField();
    }

    public function float(): FloatField
    {
        return new FloatField();
    }

    public function enum(): EnumField
    {
        return new EnumField();
    }

    public function bool(): BooleanField
    {
        return new BooleanField();
    }

    public function array(): ArrayField
    {
        return new ArrayField();
    }

    public function object(): ObjectField
    {
        return new ObjectField();
    }
}
