<?php

declare(strict_types=1);

namespace Orion\Specs\Fields;

class Schema
{
    public static function string(): StringField
    {
        return new StringField();
    }

    public static function integer(): IntegerField
    {
        return new IntegerField();
    }

    public static function float(): FloatField
    {
        return new FloatField();
    }

    public static function enum(): EnumField
    {
        return new EnumField();
    }

    public static function bool(): BooleanField
    {
        return new BooleanField();
    }

    public static function array(): ArrayField
    {
        return new ArrayField();
    }
}
