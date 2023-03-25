<?php

namespace Orion\Http\Rules;

use Illuminate\Contracts\Validation\Rule;

class WhitelistedQueryFields extends WhitelistedField implements Rule
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
