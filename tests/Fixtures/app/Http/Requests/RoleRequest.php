<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Requests;

use Orion\Http\Requests\Request;

class RoleRequest extends Request
{
    /**
     * Default rules for the request.
     *
     * @return array
     */
    public function commonRules() : array
    {
        return [
            'name' => ['string', 'required', 'max:255'],
        ];
    }

    /**
     * Rules for the "store" (POST) endpoint.
     *
     * @return array
     */
    public function storeRules() : array
    {
        return [
            'description' => ['string', 'required', 'min:5'],
            'pivot.custom_name' => ['string', 'required', 'min:5']
        ];
    }

    /**
     * Rules for the "update" (PATCH|PUT) endpoint.
     *
     * @return array
     */
    public function updateRules() : array
    {
        return [
            'description' => ['string', 'required', 'min:2'],
            'pivot.custom_name' => ['string', 'required', 'min:2']
        ];
    }
}