<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

class SecurityBuilder
{
    public function build(): array
    {
        $schemes = [
           ['BearerAuth' => []]
        ];

        if (class_exists('Laravel\\Passport\\PassportServiceProvider')) {
            $schemes[] = ['OAuth2' => []];
        }

        return $schemes;
    }
}
