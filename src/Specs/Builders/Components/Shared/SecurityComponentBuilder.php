<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Shared;

use Orion\Specs\Builders\Components\SharedComponentBuilder;
use Orion\ValueObjects\Specs\SecuritySchemesComponent;

class SecurityComponentBuilder extends SharedComponentBuilder
{
    public function build(): SecuritySchemesComponent
    {
        $component = new SecuritySchemesComponent();
        $component->title = 'securitySchemes';
        $component->schemes = [
            'BearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer'
            ]
        ];

        if (class_exists('Laravel\\Passport\\PassportServiceProvider')) {
            $component->schemes['OAuth2'] = [
                'type' => 'oauth2',
                'flows' => [
                    'authorizationCode' => [
                        'authorizationUrl' => route('passport.authorizations.authorize'),
                        'tokenUrl' => route('passport.token')
                    ]
                ]
            ];
        }

        return $component;
    }
}
