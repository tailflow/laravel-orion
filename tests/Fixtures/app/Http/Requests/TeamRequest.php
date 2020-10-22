<?php

namespace Orion\Tests\Fixtures\App\Http\Requests;

use Orion\Http\Requests\Request;

class TeamRequest extends Request
{
    /**
     * Default rules for the request.
     *
     * @return array
     */
    public function commonRules() : array
    {
        return [
            'description' => ['string', 'nullable']
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
            'name' => ['string', 'required']
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
            'name' => ['string', 'required']
        ];
    }
}