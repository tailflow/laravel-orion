<?php

namespace Orion\Http\Rules;

use Illuminate\Contracts\Validation\Rule;

class WhitelistedQueryFields implements Rule
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
    public function passes($_, $fields): bool
    {
        foreach (explode(',', $fields) as $field) {
            if (in_array('*', $this->constraints, true)) {
                continue;
            }
            if (in_array($field, $this->constraints, true)) {
                continue;
            }

            if (strpos($field, '.') === false) {
                return false;
            }

            $nestedParamConstraints = array_filter(
                $this->constraints,
                function ($paramConstraint) {
                    return strpos($paramConstraint, '.*') !== false;
                }
            );

            foreach ($nestedParamConstraints as $nestedParamConstraint) {
                if (preg_match($this->convertConstraintToRegex($nestedParamConstraint), $field)) {
                    continue 2;
                }
            }

            return false;
        }

        return true;
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
