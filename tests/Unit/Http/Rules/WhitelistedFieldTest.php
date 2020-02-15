<?php

namespace Orion\Tests\Unit\Http\Rules;

use Orion\Http\Rules\WhitelistedField;
use Orion\Tests\Unit\TestCase;

class WhitelistedFieldTest extends TestCase
{
    /** @test */
    public function wildcard_pattern()
    {
        $rule = new WhitelistedField(['*']);
        $this->assertTrue($rule->passes('', 'any-field'));
    }

    /** @test */
    public function exact_match_root_level_valid_field()
    {
        $rule = new WhitelistedField(['some-field', 'another-field']);
        $this->assertTrue($rule->passes('', 'some-field'));
    }

    /** @test */
    public function exact_match_root_level_invalid_field()
    {
        $rule = new WhitelistedField(['some-field', 'another-field']);
        $this->assertFalse($rule->passes('', 'some-other-field'));
    }

    /** @test */
    public function wildcard_match_2nd_level_nested_field()
    {
        $rule = new WhitelistedField(['parent.*']);
        $this->assertTrue($rule->passes('', 'parent.some-field'));
    }

    /** @test */
    public function exact_match_2nd_level_nested_valid_field()
    {
        $rule = new WhitelistedField(['parent.some-field']);
        $this->assertTrue($rule->passes('', 'parent.some-field'));
    }

    /** @test */
    public function exact_match_2nd_level_nested_invalid_field()
    {
        $rule = new WhitelistedField(['parent.some-field']);
        $this->assertFalse($rule->passes('', 'parent.some-other-field'));
    }
}
