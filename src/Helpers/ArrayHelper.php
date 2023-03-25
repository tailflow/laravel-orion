<?php

namespace Orion\Helpers;

class ArrayHelper
{
    /**
     * Get the max depth of an array
     *
     * @param array $array
     * @return int
     */
    public static function depth(array $array): int
    {
        $maxDepth = 0;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = self::depth($value) + 1;

                $maxDepth = max($depth, $maxDepth);
            }
        }

        return $maxDepth;
    }
}
