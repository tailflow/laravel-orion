<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

class InfoBuilder
{
    public function build(): array
    {
        $info = [
            'title' => config('orion.specs.info.title')
        ];

        $optionalFields = [
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

        $optionalFields = $this->resolveOptionalFields($optionalFields);

        return array_merge($info, $optionalFields);
    }

    protected function resolveOptionalFields(array $optionalFields): array
    {
        $fields = [];

        foreach ($optionalFields as $optionalField => $value) {
            if (!$value) {
                continue;
            }

            if (is_array($value)) {
                $value = $this->resolveOptionalFields($value);
                if (!$value) {
                    continue;
                }
            }

            $fields[$optionalField] = $value;
        }

        return $fields;
    }
}
