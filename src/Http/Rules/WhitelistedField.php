<?php

namespace Orion\Http\Rules;

use Illuminate\Contracts\Validation\Rule;

class WhitelistedField implements Rule
{
    /**
     * @var array $allowedConstraints
     */
    protected $allowedConstraints;

    /**
     * ValidField constructor.
     *
     * @param $allowedConstraints
     */
    public function __construct(array $allowedConstraints)
    {
        $this->allowedConstraints = $allowedConstraints;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $_
     * @param mixed $field
     * @return bool
     */
    public function passes($_, $field)
    {
        if (in_array('*', $this->allowedConstraints, true)) {
            return true;
        }
        if (in_array($field, $this->allowedConstraints, true)) {
            return true;
        }

        if (strpos($field, '.') === false) {
            return false;
        }

        $allowedNestedParamConstraints = array_filter($field, function ($allowedParamConstraint) {
            return strpos($allowedParamConstraint, '.*') !== false;
        });

        $paramConstraintNestingLevel = substr_count($field, '.');

        foreach ($allowedNestedParamConstraints as $allowedNestedParamConstraint) {
            $allowedNestedParamConstraintNestingLevel = substr_count($allowedNestedParamConstraint, '.');
            $allowedNestedParamConstraintReduced = explode('.*', $allowedNestedParamConstraint)[0];

            for ($i = 0; $i < $allowedNestedParamConstraintNestingLevel; $i++) {
                $allowedNestedParamConstraintReduced = implode('.', array_slice(explode('.', $allowedNestedParamConstraintReduced), -$i));

                $paramConstraintReduced = $field;
                for ($k = 1; $k < $paramConstraintNestingLevel; $k++) {
                    $paramConstraintReduced = implode('.', array_slice(explode('.', $paramConstraintReduced), -$i));
                    if ($paramConstraintReduced === $allowedNestedParamConstraintReduced) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is not whitelisted.';
    }
}
