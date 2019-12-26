<?php

namespace Orion\Tests\Fixtures\App\Http\Requests;

use Orion\Http\Requests\Request;

class TagRequest extends Request
{
    /**
     * Default rules for the request.
     *
     * @return array
     */
    public function commonRules()
    {
        return [];
    }

    /**
     * Rules for the "store" (POST) endpoint.
     *
     * @return array
     */
    public function storeRules()
    {
        return ['name' => 'string|required|max:255'];
    }

    /**
     * Rules for the "update" (PATCH|PUT) endpoint.
     *
     * @return array
     */
    public function updateRules()
    {
        return ['description' => 'string|required'];
    }
}
