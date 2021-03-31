<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

class InfoBuilder
{
    public function build(): array
    {
        return [
            'title' => config('orion.specs.info.title'),
            'description' => config('orion.specs.info.description'),
            'termsOfService' => config('orion.specs.info.terms_of_service'),
            'contact' => [
                'name' => config('orion.specs.info.contact.name'),
                'url' => config('orion.specs.info.contact.url'),
                'email' => config('orion.specs.info.contact.email'),
            ],
            'license' => [
                'name' => config('orion.specs.info.license.name'),
                'url' => config('orion.specs.info.license.url'),
            ],
            'version' => config('orion.specs.info.version'),
        ];
    }
}
