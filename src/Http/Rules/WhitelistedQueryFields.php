<?php

declare(strict_types=1);

namespace Orion\Http\Rules;

class WhitelistedQueryFields extends WhitelistedField
{

    /**
     * Determine if the validation rule passes.
     *
     * @param string $_
     * @param string $field
     * @return bool
     */
    public function passes($_, $fields): bool
    {
        foreach (explode(',', $fields) as $field) {
            if (!parent::passes($_, $field)) {
                return false;
            }
        }

        return true;
    }
}
