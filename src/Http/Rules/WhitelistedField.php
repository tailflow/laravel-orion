<?php

namespace Orion\Http\Rules;

use Illuminate\Contracts\Validation\Rule;

class WhitelistedField implements Rule
{
    /**
     * @var array $constraints
     */
    protected $constraints;

    /**
     * ValidField constructor.
     *
     * @param $constraints
     */
    public function __construct(array $constraints)
    {
        $this->constraints = $constraints;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $_
     * @param string $field
     * @return bool
     */
    public function passes($_, $field) : bool
    {
        if (in_array('*', $this->constraints, true)) {
            return true;
        }
        if (in_array($field, $this->constraints, true)) {
            return true;
        }

        if (strpos($field, '.') === false) {
            return false;
        }

        $nestedParamConstraints = array_filter($this->constraints, function ($paramConstraint) {
            return strpos($paramConstraint, '.*') !== false;
        });

        foreach ($nestedParamConstraints as $nestedParamConstraint) {
            if (preg_match($this->convertConstraintToRegex($nestedParamConstraint), $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $constraint
     * @return string
     */
    protected function convertConstraintToRegex(string $constraint): string
    {
        return '/'.str_replace('.*', '\.(\w+)', $constraint).'/';
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :input field is not whitelisted.';
    }
}
